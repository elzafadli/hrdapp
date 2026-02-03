<?php

namespace App\Http\Controllers;

use App\Models\EmpBpjs;
use App\Models\EmpLoan;
use Illuminate\Http\Request;
use App\Models\EmpData;
use App\Models\EmpAllowance;
use App\Models\PayrollResult;
use App\Models\PayrollSetting;
use Illuminate\Support\Facades\DB;

class PayrollResultController extends Controller
{
    public function index(Request $request)
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

        return view('payroll_results.index', compact('results', 'month', 'year', 'payrollColumns', 'groupedResults'));
    }

    public function process(Request $request)
    {
        $month = now()->month;
        $year = now()->year;

        DB::transaction(function () use ($month, $year) {
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
                // Temporary storage for allowance components to save later
                $allowanceResults = [];

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

                // Save Allowances
                if (!empty($allowanceResults)) {
                    PayrollResult::insert($allowanceResults);
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
                        $empBpjs = EmpBpjs::where('employee_id', $employee->id)
                            ->where('payroll_component_id', $detail->component->payroll_reference)
                            ->first();

                        $amount = 0;

                        if (!empty($empBpjs->value)) {
                            if ($totalAllowance > $detail->base_amount) {
                                $amount = $totalAllowance * (($detail->value / 100) ?? 0);
                            } else {
                                $amount = $detail->base_amount * (($detail->value / 100) ?? 0);
                            }
                        }

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
                    if (strtolower($detail->component->name) == 'pinjaman') {
                        // Check active loan in emp_loans table
                        $empLoan = EmpLoan::where('emp_id', $employee->id)
                            ->where('status', 'open')
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

                // 5. Calculate PPH 21 from $totalAllowance using Tarif TER
                $pph21Results = [];
                foreach ($setting->details as $detail) {
                    if (strtolower($detail->component->name) == 'pph 21') {
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

                // Save PPH 21
                if (!empty($pph21Results)) {
                    PayrollResult::insert($pph21Results);
                }

            }
        });

        return redirect()->route('payroll-results.index')->with('success', 'Payroll processed successfully.');
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
        $deductions = $results->whereIn('type', ['deduction', 'bpjs']);

        return view('payroll_results.slip', compact('employee', 'month', 'year', 'results', 'allowances', 'subsidies', 'bpjs', 'deductions'));
    }
}

