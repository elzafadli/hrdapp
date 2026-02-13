<?php

namespace App\Http\Controllers;

use App\Models\EmpBpjs;
use App\Models\EmpLoan;
use App\Models\PotonganKaryawan;
use App\Models\PotonganKaryawanDetail;
use Illuminate\Http\Request;
use App\Models\EmpData;
use App\Models\EmpAllowance;
use App\Models\PayrollResult;
use App\Models\PayrollSetting;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollResultController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $results = PayrollResult::with(['employee.project.company', 'payrollComponent', 'payrollSetting'])
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $groupedResults = $results->groupBy('emp_id');

        $payrollColumns = collect();
        if ($groupedResults->isNotEmpty()) {
            $payrollColumns = $groupedResults->first()->map(fn($d) => $d->payrollComponent);
        }

        return view('payroll_results.index', compact('results', 'month', 'year', 'payrollColumns', 'groupedResults'));
    }

    public function create(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $results = PayrollResult::with(['employee', 'payrollComponent', 'payrollSetting'])
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $groupedResults = $results->groupBy('emp_id');

        $payrollColumns = collect();
        if ($groupedResults->isNotEmpty()) {
            $payrollColumns = $groupedResults->first()->map(fn($d) => $d->payrollComponent);
        }

        return view('payroll_results.process', compact('month', 'year', 'payrollColumns', 'groupedResults'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2099',
        ]);

        $month = $request->input('month');
        $year = $request->input('year');
        $isThr = $request->input('is_thr', false);

        // Check if data already exists for this period
        $existingData = PayrollResult::where('month', $month)
            ->where('year', $year);

        // Prevent duplicate submission using session token
        $processKey = 'payroll_process_' . $month . '_' . $year . '_' . ($isThr ? 'thr' : 'regular');
        if (session()->get($processKey)) {
            return redirect()->route('payroll-results.create', ['month' => $month, 'year' => $year])
                ->with('warning', 'Payroll is already being processed for this period. Please wait.');
        }

        // Set session flag to prevent duplicate submission
        session()->put($processKey, true);

        DB::transaction(function () use ($month, $year, $isThr) {
            // Get Active Setting
            $setting = PayrollSetting::where('is_aktif', true)
                ->with('details.component')->first();

            if (!$setting) {
                // throw new \Exception('No active payroll setting found.'); // Or handle gracefully
                return;
            }

            // Cleanup existing results for this period and setting to avoid duplicates
            PayrollResult::where('month', $month)
                ->where('year', $year)
                ->where('payroll_setting_id', $setting->id)
                ->delete();

            $employees = EmpData::all();

            foreach ($employees as $employee) {
                $totalAllowance = 0;
                $totalSubsidiBpjs = 0;
                $totalThr = 0;
                // Temporary storage for allowance components to save later
                $allowanceResults = [];
                $thrResults = [];

                // 1. Process Allowances
                foreach ($setting->details as $detail) {
                    if ($detail->component->type == 'allowance') {
                        // Check if employee has specific allowance value
                        $empAllowance = EmpAllowance::where('employee_id', $employee->id)
                            ->where('payroll_component_id', $detail->payroll_component_id)
                            ->first();

                        $amount = $empAllowance ? $empAllowance->value : $detail->base_amount;
                        // Ensure amount is numeric (default to 0 if null)
                        $amount = $amount ?? 0;

                        $totalAllowance += $amount;
                        if ($isThr && $detail->is_thr === 1) {

                            $totalThr += $amount;
                        }

                        if (strtolower($detail->component->name) != 'thr') {
                            $allowanceResults[] = [
                                'month' => $month,
                                'year' => $year,
                                'emp_id' => $employee->id,
                                'payroll_setting_id' => $setting->id,
                                'payroll_component_id' => $detail->payroll_component_id,
                                'amount' => $amount,
                                'type' => 'allowance',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }

                // Save Allowances
                if (!empty($allowanceResults)) {
                    PayrollResult::insert($allowanceResults);
                }
                
                // 2. Process THR
                foreach ($setting->details as $detail) {
                    if ($detail->component->type == 'thr') {

                        $thrResults[] = [
                            'month' => $month,
                            'year' => $year,
                            'emp_id' => $employee->id,
                            'payroll_setting_id' => $setting->id,
                            'payroll_component_id' => $detail->payroll_component_id,
                            'amount' => $totalThr,
                            'type' => 'allowance',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Save THR
                if (!empty($thrResults)) {
                    PayrollResult::insert($thrResults);
                }

                // 2. Process Subdusi BPJS / Others (using totalAllowance)
                $subsidiBpjsResults = [];
                foreach ($setting->details as $detail) {
                    if ($detail->component->type == 'subsidi') {
                        $empBpjs = EmpBpjs::where('employee_id', $employee->id)
                            ->where('payroll_component_id', $detail->payroll_component_id)
                            ->first();

                        $amount = 0;

                        if (!empty($empBpjs->value)) {
                            if ($totalAllowance > $detail->base_amount) {
                                $amount = $totalAllowance * (($detail->value / 100) ?? 0);
                            } else {
                                $amount = $detail->base_amount * (($detail->value / 100) ?? 0);
                            }
                        }

                        $totalSubsidiBpjs += $amount;
                        $subsidiBpjsResults[] = [
                            'month' => $month,
                            'year' => $year,
                            'emp_id' => $employee->id,
                            'payroll_setting_id' => $setting->id,
                            'payroll_component_id' => $detail->payroll_component_id,
                            'amount' => $amount,
                            'type' => 'subsidi',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Save BPJS
                if (!empty($subsidiBpjsResults)) {
                    PayrollResult::insert($subsidiBpjsResults);
                }

                // 3. Process BPJS / Others (using totalAllowance)
                $bpjsResults = [];
                foreach ($setting->details as $detail) {
                    if ($detail->component->type == 'bpjs') {

                        $amount = $detail->base_amount * (($detail->value / 100) ?? 0);

                        $bpjsResults[] = [
                            'month' => $month,
                            'year' => $year,
                            'emp_id' => $employee->id,
                            'payroll_setting_id' => $setting->id,
                            'payroll_component_id' => $detail->payroll_component_id,
                            'amount' => $amount,
                            'type' => 'deduction',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }


                // Save BPJS
                if (!empty($bpjsResults)) {
                    PayrollResult::insert($bpjsResults);
                }

                // 4. hitung pinjaman
                $loanResults = [];
                foreach ($setting->details as $detail) {
                    if (stripos(strtolower($detail->component->name), 'pinjaman') !== false) {
                        // Check active loan in emp_loans table within date range
                        $currentDate = \Carbon\Carbon::createFromDate($year, $month, 15)->endOfMonth();

                        $empLoan = EmpLoan::where('emp_id', $employee->id)
                            ->where('status', 'open')
                            ->where(function ($query) use ($currentDate) {
                                $query->whereDate('start_date', '<=', $currentDate)
                                    ->where(function ($q) use ($currentDate) {
                                        $q->whereNull('end_date')
                                            ->orWhereDate('end_date', '>=', $currentDate);
                                    });
                            })
                            ->first();

                        // If there is an active loan, take the installment amount
                        $amount = $empLoan ? $empLoan->installment_amount : 0;


                        $loanResults[] = [
                            'month' => $month,
                            'year' => $year,
                            'emp_id' => $employee->id,
                            'payroll_setting_id' => $setting->id,
                            'payroll_component_id' => $detail->payroll_component_id,
                            'amount' => $amount,
                            'type' => 'deduction',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Save Loan Results
                if (!empty($loanResults)) {
                    PayrollResult::insert($loanResults);
                }

                // 5. Process Potongan Karyawan (Employee Deductions)
                $potonganKaryawanResults = [];
                $potonganHeader = PotonganKaryawan::where('month', $month)
                    ->where('year', $year)
                    ->first();

                if ($potonganHeader) {
                    $potonganDetails = PotonganKaryawanDetail::where('header_id', $potonganHeader->id)
                        ->where('employee_id', $employee->id)
                        ->get();

                    foreach ($potonganDetails as $detail) {
                        // Only add if value is greater than 0
                        $potonganKaryawanResults[] = [
                            'month' => $month,
                            'year' => $year,
                            'emp_id' => $employee->id,
                            'payroll_setting_id' => $setting->id,
                            'payroll_component_id' => $detail->payroll_component_id,
                            'amount' => $detail->value,
                            'type' => 'deduction',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Save Potongan Karyawan Results
                if (!empty($potonganKaryawanResults)) {
                    PayrollResult::insert($potonganKaryawanResults);
                }

                // 5. Calculate PPH 21 from $totalAllowance using Tarif TER
                $pph21Results = [];

                // Check if PPH 21 already exists in potonganKaryawanResults
                $hasPph21InPotongan = false;
                if (!empty($potonganKaryawanResults)) {
                    foreach ($potonganKaryawanResults as $potongan) {
                        $component = $setting->details->firstWhere('payroll_component_id', $potongan['payroll_component_id']);
                        if ($component && strtolower($component->component->name) == 'pph21') {
                            $hasPph21InPotongan = true;
                            break;
                        }
                    }
                }

                // Skip PPH 21 calculation if already exists in potongan karyawan
                if (!$hasPph21InPotongan) {
                    foreach ($setting->details as $detail) {
                        if (strtolower($detail->component->name) == 'pph21') {
                        // Get rate from Tarif TER
                        $terRate = 0;
                        if ($employee->status_ptkp) {
                            $terRow = DB::table('tarif_ter')
                                ->where('status_ptkp', $employee->status_ptkp)
                                ->where('penghasilan_min', '<=', $totalAllowance + $totalSubsidiBpjs)
                                ->where('penghasilan_max', '>=', $totalAllowance + $totalSubsidiBpjs)
                                ->first();

                            if ($terRow) {
                                $terRate = $terRow->tarif_ter;
                            }
                        }

                        // Calculate PPh 21 Amount based on TER rate
                        $amount = ($totalAllowance + $totalSubsidiBpjs) * ($terRate / 100);

                        $pph21Results[] = [
                            'month' => $month,
                            'year' => $year,
                            'emp_id' => $employee->id,
                            'payroll_setting_id' => $setting->id,
                            'payroll_component_id' => $detail->payroll_component_id,
                            'amount' => $amount,
                            'type' => 'deduction',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                }

                // Save PPH 21
                if (!empty($pph21Results)) {
                    PayrollResult::insert($pph21Results);
                }

            }
        });

        // Clear session flag after successful processing
        $processKey = 'payroll_process_' . $month . '_' . $year . '_' . ($isThr ? 'thr' : 'regular');
        session()->forget($processKey);

        return redirect()->route('payroll-results.create', ['month' => $month, 'year' => $year])->with('success', 'Payroll processed successfully.');
    }

    public function slip($emp_id, $month, $year)
    {
        $employee = EmpData::findOrFail($emp_id);

        $results = PayrollResult::with(['payrollComponent', 'payrollSetting'])
            ->where('emp_id', $emp_id)
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        if ($results->isEmpty()) {
            return redirect()->route('payroll-results.index')->with('error', 'No payroll data found for this employee.');
        }

        // Group by type for the view
        $allowances = $results->where('type', 'allowance');
        $subsidies = $results->where('type', 'subsidi');
        // Assuming BPJS can be grouped or handled separately if needed, but for now let's see.
        // The process method uses 'bpjs' as type too.
        $bpjs = collect(); // Empty collection if needed elsewhere, but moving to deductions
        $deductions = $results->whereIn('type', ['deduction']);

        return view('payroll_results.slip', compact('employee', 'month', 'year', 'results', 'allowances', 'subsidies', 'bpjs', 'deductions'));
    }

    public function export(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $results = PayrollResult::with(['employee.project.company', 'payrollComponent', 'payrollSetting'])
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $groupedResults = $results->groupBy('emp_id');

        if ($groupedResults->isEmpty()) {
            return redirect()->back()->with('error', 'No data to export.');
        }

        $payrollColumns = $groupedResults->first()->map(fn($d) => $d->payrollComponent);

        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document title
        $monthName = \DateTime::createFromFormat('!m', $month)->format('F');
        $sheet->setCellValue('A1', "Payroll Results - {$monthName} {$year}");
        $sheet->mergeCells('A1:' . $this->getColumnLetter($payrollColumns->count() + 3) . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set header row
        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Company');
        $sheet->setCellValue('C3', 'Project');
        $sheet->setCellValue('D3', 'Employee Name');
        $column = 'E';
        foreach ($payrollColumns as $col) {
            $sheet->setCellValue($column . '3', $col->name);
            $column++;
        }

        // Style header row
        $lastColumn = $this->getColumnLetter($payrollColumns->count() + 4);
        $sheet->getStyle('A3:' . $lastColumn . '3')->getFont()->setBold(true);
        $sheet->getStyle('A3:' . $lastColumn . '3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2EFDA');
        $sheet->getStyle('A3:' . $lastColumn . '3')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Fill data
        $row = 4;
        $no = 1;
        foreach ($groupedResults as $empId => $empResults) {
            $employee = $empResults->first()->employee;

            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $employee->project->company->code ?? 'N/A');
            $sheet->setCellValue('C' . $row, $employee->project->name ?? 'N/A');
            $sheet->setCellValue('D' . $row, $employee->name ?? 'N/A');

            $column = 'E';
            foreach ($payrollColumns as $col) {
                $result = $empResults->firstWhere('payroll_component_id', $col->id);
                $sheet->setCellValue($column . $row, $result ? $result->amount : 0);
                $sheet->getStyle($column . $row)->getNumberFormat()
                    ->setFormatCode('#,##0.00');
                $column++;
            }

            // Style data row
            $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->getBorders()
                ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $row++;
            $no++;
        }

        // Auto size columns
        foreach (range('A', $lastColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set filename
        $filename = "payroll_results_{$month}_{$year}.xlsx";

        // Stream the file
        return new StreamedResponse(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    private function getColumnLetter($index)
    {
        $letter = '';
        while ($index > 0) {
            $temp = ($index - 1) % 26;
            $letter = chr(65 + $temp) . $letter;
            $index = floor(($index - $temp) / 26);
        }
        return $letter;
    }
}
