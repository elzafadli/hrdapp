<?php

namespace App\Http\Controllers;

use App\Models\EmpBpjs;
use App\Models\EmpLoan;
use App\Models\PotonganKaryawan;
use App\Models\PotonganKaryawanDetail;
use App\Services\LatenessDeductionService;
use Illuminate\Http\Request;
use App\Models\EmpData;
use App\Models\EmpAllowance;
use App\Models\PayrollComponent;
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
    public function index(Request $request, LatenessDeductionService $service)
    {
        // 1. Ambil seluruh periode yang tersimpan di payroll_results (urutkan tahun & bulan descending)
        $hasStatusColumn = \Illuminate\Support\Facades\Schema::hasColumn('payroll_results', 'status');

        $periodsQuery = DB::table('payroll_results')
            ->select(
                'year',
                'month',
                DB::raw('COUNT(DISTINCT emp_id) as total_employees'),
                DB::raw("SUM(CASE WHEN type IN ('allowance', 'thr') THEN amount ELSE 0 END) as total_allowance"),
                DB::raw("SUM(CASE WHEN type IN ('deduction', 'bpjs') THEN amount ELSE 0 END) as total_deduction"),
                DB::raw('MAX(updated_at) as last_processed_at')
            );

        if ($hasStatusColumn) {
            $periodsQuery->addSelect(
                DB::raw("CASE WHEN SUM(CASE WHEN status != 'final' THEN 1 ELSE 0 END) > 0 THEN 'draft' ELSE 'final' END as status")
            );
        }

        $periods = $periodsQuery
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $periods = $periods->map(function ($p) use ($service, $hasStatusColumn) {
            $cutoff = $service->getCutoffPeriod((int) $p->year, (int) $p->month);
            $p->start_date = $cutoff['start_date'];
            $p->end_date = $cutoff['end_date'];
            $p->cutoff_range = $cutoff['label'] ?? ($cutoff['start_date']->translatedFormat('d M Y') . ' s/d ' . $cutoff['end_date']->translatedFormat('d M Y'));
            $p->total_gross = (float) $p->total_allowance;
            $p->total_deductions = (float) $p->total_deduction;
            $p->total_thp = (float) $p->total_allowance - (float) $p->total_deduction;
            $dateObj = \Carbon\Carbon::createFromDate((int) $p->year, (int) $p->month, 1);
            $p->period_label = $dateObj->translatedFormat('F Y');
            $p->status = $hasStatusColumn ? ($p->status ?? 'draft') : 'draft';
            return $p;
        });

        $latestPeriod = $periods->first();
        $currentMonth = $latestPeriod ? (int) $latestPeriod->month : (int) now()->month;
        $currentYear  = $latestPeriod ? (int) $latestPeriod->year : (int) now()->year;

        return view('payroll_results.index', compact(
            'periods',
            'currentMonth',
            'currentYear'
        ));
    }

    public function show(Request $request, LatenessDeductionService $service)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        // Ambil data rincian payroll untuk periode yang dipilih
        $results = PayrollResult::with(['employee.project.company', 'employee.jabatan', 'payrollComponent', 'payrollSetting'])
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $groupedResults = $results->groupBy('emp_id');

        $payrollColumns = collect();
        if ($groupedResults->isNotEmpty()) {
            $payrollColumns = $results->pluck('payrollComponent')->unique('id')->filter();

            // Filter columns: keep all allowances, keep THR only if has non-zero values
            $payrollColumns = $payrollColumns->filter(function ($column) use ($results) {
                if ($column->type !== 'thr') {
                    return true;
                }
                return $results->contains(function ($result) use ($column) {
                    return $result->payroll_component_id == $column->id && $result->amount > 0;
                });
            });
        }

        // Hitung ringkasan Header KPI
        $totalEmployees = $groupedResults->count();
        $totalGross = (float) $results->whereIn('type', ['allowance', 'thr'])->sum('amount');
        $totalDeductions = (float) $results->whereIn('type', ['deduction', 'bpjs'])->sum('amount');
        $totalNet = $totalGross - $totalDeductions;
        $cutoffPeriod = $service->getCutoffPeriod($year, $month);
        $activeTab = $request->input('tab', 'detail');

        $hasStatusColumn = \Illuminate\Support\Facades\Schema::hasColumn('payroll_results', 'status');
        $periodStatus = 'draft';
        if ($hasStatusColumn) {
            $isDraft = PayrollResult::where('month', $month)->where('year', $year)->where('status', '!=', 'final')->exists();
            $periodStatus = $isDraft ? 'draft' : 'final';
        }

        return view('payroll_results.show', compact(
            'results',
            'month',
            'year',
            'payrollColumns',
            'groupedResults',
            'totalEmployees',
            'totalGross',
            'totalDeductions',
            'totalNet',
            'cutoffPeriod',
            'activeTab',
            'periodStatus'
        ));
    }

    public function create(Request $request)
    {
        // Unifikasi: redirect langsung ke halaman index dengan query parameter & auto-focus
        return redirect()->route('payroll-results.index', array_merge($request->all(), ['focus' => 'process']));
    }

    public function destroyPeriod(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2020|max:2099',
        ]);

        $month = (int) $request->month;
        $year  = (int) $request->year;

        // Jika data yang dihapus berstatus final, kembalikan saldo pinjaman karyawan terlebih dahulu
        $hasStatusColumn = \Illuminate\Support\Facades\Schema::hasColumn('payroll_results', 'status');
        if ($hasStatusColumn) {
            $isFinal = PayrollResult::where('month', $month)->where('year', $year)->where('status', 'final')->exists();
            if ($isFinal) {
                $currentDate = \Carbon\Carbon::createFromDate($year, $month, 15)->endOfMonth();
                $loanComponentIds = PayrollComponent::where('name', 'like', '%Pinjaman%')->pluck('id');
                $loanResults = PayrollResult::where('month', $month)
                    ->where('year', $year)
                    ->whereIn('payroll_component_id', $loanComponentIds)
                    ->where('amount', '>', 0)
                    ->get();

                foreach ($loanResults as $lr) {
                    $empLoan = EmpLoan::where('emp_id', $lr->emp_id)
                        ->where(function ($query) use ($currentDate) {
                            $query->whereDate('start_date', '<=', $currentDate)
                                ->where(function ($q) use ($currentDate) {
                                    $q->whereNull('end_date')
                                        ->orWhereDate('end_date', '>=', $currentDate);
                                });
                        })
                        ->latest('id')
                        ->first();

                    if ($empLoan) {
                        $currRemaining = (float) ($empLoan->remaining_amount ?? 0);
                        $restoredRemaining = min((float) $empLoan->amount, $currRemaining + (float) $lr->amount);
                        $restoredPaid = max(0.0, (float) ($empLoan->paid_amount ?? 0) - (float) $lr->amount);

                        if (\Illuminate\Support\Facades\Schema::hasColumn('emp_loans', 'paid_amount')) {
                            $empLoan->paid_amount = $restoredPaid;
                        }
                        if (\Illuminate\Support\Facades\Schema::hasColumn('emp_loans', 'remaining_amount')) {
                            $empLoan->remaining_amount = $restoredRemaining;
                        }
                        if ($restoredRemaining > 0) {
                            $empLoan->status = 'open';
                        }
                        $empLoan->save();
                    }
                }
            }
        }

        $deleted = PayrollResult::where('month', $month)
            ->where('year', $year)
            ->delete();

        $monthName = \DateTime::createFromFormat('!m', $month)->format('F');
        return redirect()->route('payroll-results.index')
            ->with('success', "Data hasil payroll periode {$monthName} {$year} berhasil dihapus ({$deleted} baris data).");
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
            return redirect()->route('payroll-results.index', ['month' => $month, 'year' => $year])
                ->with('warning', 'Payroll is already being processed for this period. Please wait.');
        }

        // Set session flag to prevent duplicate submission
        session()->put($processKey, true);

        $hasStatusColumn = \Illuminate\Support\Facades\Schema::hasColumn('payroll_results', 'status');

        DB::transaction(function () use ($month, $year, $isThr, $hasStatusColumn) {
            // Get Active Setting
            $setting = PayrollSetting::where('is_aktif', true)
                ->with('details.component')->first();

            if (!$setting) {
                // throw new \Exception('No active payroll setting found.'); // Or handle gracefully
                return;
            }

            // Jika periode yang diproses ulang sebelumnya berstatus final, kembalikan saldo pinjaman terlebih dahulu
            if ($hasStatusColumn) {
                $isPreviouslyFinal = PayrollResult::where('month', $month)->where('year', $year)->where('status', 'final')->exists();
                if ($isPreviouslyFinal) {
                    $currentDate = \Carbon\Carbon::createFromDate($year, $month, 15)->endOfMonth();
                    $loanComponentIds = PayrollComponent::where('name', 'like', '%Pinjaman%')->pluck('id');
                    $loanResults = PayrollResult::where('month', $month)
                        ->where('year', $year)
                        ->whereIn('payroll_component_id', $loanComponentIds)
                        ->where('amount', '>', 0)
                        ->get();

                    foreach ($loanResults as $lr) {
                        $empLoan = EmpLoan::where('emp_id', $lr->emp_id)
                            ->where(function ($query) use ($currentDate) {
                                $query->whereDate('start_date', '<=', $currentDate)
                                    ->where(function ($q) use ($currentDate) {
                                        $q->whereNull('end_date')
                                            ->orWhereDate('end_date', '>=', $currentDate);
                                    });
                            })
                            ->latest('id')
                            ->first();

                        if ($empLoan) {
                            $currRemaining = (float) ($empLoan->remaining_amount ?? 0);
                            $restoredRemaining = min((float) $empLoan->amount, $currRemaining + (float) $lr->amount);
                            $restoredPaid = max(0.0, (float) ($empLoan->paid_amount ?? 0) - (float) $lr->amount);

                            if (\Illuminate\Support\Facades\Schema::hasColumn('emp_loans', 'paid_amount')) {
                                $empLoan->paid_amount = $restoredPaid;
                            }
                            if (\Illuminate\Support\Facades\Schema::hasColumn('emp_loans', 'remaining_amount')) {
                                $empLoan->remaining_amount = $restoredRemaining;
                            }
                            if ($restoredRemaining > 0) {
                                $empLoan->status = 'open';
                            }
                            $empLoan->save();
                        }
                    }
                }
            }

            // Cleanup existing results for this period and setting to avoid duplicates
            PayrollResult::where('month', $month)
                ->where('year', $year)
                ->where('payroll_setting_id', $setting->id)
                ->delete();

            // Pastikan potongan keterlambatan sinkron ke Potongan Karyawan jika belum ada
            $potonganHeader = PotonganKaryawan::firstOrCreate([
                'year'  => $year,
                'month' => $month,
            ]);
            $lateComp = app(LatenessDeductionService::class)->getLateComponent();
            if ($lateComp) {
                $hasLateDetail = PotonganKaryawanDetail::where('header_id', $potonganHeader->id)
                    ->where('payroll_component_id', $lateComp->id)
                    ->exists();
                if (!$hasLateDetail) {
                    app(LatenessDeductionService::class)->syncToPotonganKaryawan($potonganHeader);
                }
            }

            // Ambil seluruh rincian Potongan Karyawan untuk periode ini
            $allPotonganDetails = PotonganKaryawanDetail::where('header_id', $potonganHeader->id)
                ->get()
                ->groupBy('employee_id');

            $employees = EmpData::all();

            foreach ($employees as $employee) {
                // Rincian Potongan Karyawan (Deduction) spesifik untuk karyawan ini
                $potonganDetails = $allPotonganDetails->get($employee->id, collect())->keyBy('payroll_component_id');

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

                        $amount = $empAllowance && $empAllowance->value > 0 ? $empAllowance->value : $detail->base_amount;
                        // Ensure amount is numeric (default to 0 if null)
                        $amount = $amount ?? 0;

                        $totalAllowance += $amount;
                        if ($isThr && $detail->is_thr === 1) {

                            $totalThr += $amount;
                        }

                        if (strtolower($detail->component->name) != 'thr') {
                            $item = [
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
                            if ($hasStatusColumn) {
                                $item['status'] = 'draft';
                            }
                            $allowanceResults[] = $item;
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
                        $item = [
                            'month' => $month,
                            'year' => $year,
                            'emp_id' => $employee->id,
                            'payroll_setting_id' => $setting->id,
                            'payroll_component_id' => $detail->payroll_component_id,
                            'amount' => $totalThr,
                            'type' => 'thr',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        if ($hasStatusColumn) {
                            $item['status'] = 'draft';
                        }
                        $thrResults[] = $item;
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

                        $amount = $detail->base_amount * (($detail->value / 100) ?? 0);
                        
                        $totalSubsidiBpjs += $amount;
                        $item = [
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
                        if ($hasStatusColumn) {
                            $item['status'] = 'draft';
                        }
                        $subsidiBpjsResults[] = $item;
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

                        $item = [
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
                        if ($hasStatusColumn) {
                            $item['status'] = 'draft';
                        }
                        $bpjsResults[] = $item;
                    }
                }

                // Save BPJS
                if (!empty($bpjsResults)) {
                    PayrollResult::insert($bpjsResults);
                }

                // 4. Hitung cicilan pinjaman (HANYA menghitung nilai potongan, saldo di-update nanti saat Finalisasi)
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

                        $amount = 0;
                        if ($empLoan) {
                            $balance = $empLoan->calculateBalanceForPeriod($year, $month);
                            if ($balance['saldo_before'] > 0) {
                                // Cek apakah ada input nilai di Potongan Karyawan
                                $potonganDetail = $potonganDetails ? $potonganDetails->get($detail->payroll_component_id) : null;
                                if ($potonganDetail && $potonganDetail->value !== null && $potonganDetail->value !== '') {
                                    $amount = min((float) $potonganDetail->value, $balance['saldo_before']);
                                } else {
                                    $amount = $balance['installment_due'];
                                }
                            }
                        }

                        $item = [
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
                        if ($hasStatusColumn) {
                            $item['status'] = 'draft';
                        }
                        $loanResults[] = $item;
                    }
                }

                // Save Loan Results
                if (!empty($loanResults)) {
                    PayrollResult::insert($loanResults);
                }

                // 5. Process Potongan Karyawan (Employee Deductions, termasuk Keterlambatan)
                $potonganKaryawanResults = [];

                // Loop through deduction components and check if potongan exists
                foreach ($setting->details as $detail) {
                    // Skip pinjaman (handled in section 4) and pph21 (handled in section 6)
                    $componentName = strtolower($detail->component->name);
                    if ($detail->component->type == 'deduction' &&
                        stripos($componentName, 'pinjaman') === false &&
                        $componentName != 'pph21') {

                        // Check if potongan exists for this component
                        $potonganDetail = $potonganDetails->get($detail->payroll_component_id);
                        $amount = $potonganDetail ? $potonganDetail->value : 0;

                        $item = [
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
                        if ($hasStatusColumn) {
                            $item['status'] = 'draft';
                        }
                        $potonganKaryawanResults[] = $item;
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

                        $item = [
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
                        if ($hasStatusColumn) {
                            $item['status'] = 'draft';
                        }
                        $pph21Results[] = $item;
                    }
                }
                }

                // Save PPH 21
                if (!empty($pph21Results)) {
                    PayrollResult::insert($pph21Results);
                }

            }
        });

        // Set status 'draft' secara default jika kolom status sudah ada di tabel payroll_results
        if ($hasStatusColumn) {
            PayrollResult::where('month', $month)
                ->where('year', $year)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '');
                })
                ->update(['status' => 'draft']);
        }

        // Clear session flag after successful processing
        $processKey = 'payroll_process_' . $month . '_' . $year . '_' . ($isThr ? 'thr' : 'regular');
        session()->forget($processKey);

        return redirect()->route('payroll-results.show', ['month' => $month, 'year' => $year])
            ->with('success', 'Payroll berhasil diproses dalam status Draft untuk periode ' . \DateTime::createFromFormat('!m', $month)->format('F') . ' ' . $year . '. Silakan periksa rincian sebelum difinalisasi.');
    }

    /**
     * Memperbarui status seluruh payroll result pada suatu periode (Draft <-> Final).
     * Saat status diubah ke Final, saldo pinjaman karyawan akan dipotong otomatis.
     * Jika dikembalikan ke Draft, potongan saldo pinjaman akan dikembalikan ke saldo awal.
     */
    public function updatePeriodStatus(Request $request)
    {
        $request->validate([
            'month'  => 'required|integer|min:1|max:12',
            'year'   => 'required|integer|min:2020|max:2099',
            'status' => 'required|in:draft,final',
        ]);

        $month  = (int) $request->month;
        $year   = (int) $request->year;
        $targetStatus = $request->status;

        $hasStatusColumn = \Illuminate\Support\Facades\Schema::hasColumn('payroll_results', 'status');
        if (!$hasStatusColumn) {
            return back()->with('warning', 'Kolom status belum dimigrasi di database. Silakan jalankan perintah migrasi manual: php artisan migrate.');
        }

        $monthLabel = \DateTime::createFromFormat('!m', $month)->format('F');

        // Cek status saat ini
        $isCurrentDraft = PayrollResult::where('month', $month)->where('year', $year)->where('status', '!=', 'final')->exists();
        $currentStatus = $isCurrentDraft ? 'draft' : 'final';

        if ($currentStatus === $targetStatus) {
            return back()->with('info', "Payroll periode {$monthLabel} {$year} sudah berstatus {$targetStatus}.");
        }

        DB::transaction(function () use ($month, $year, $targetStatus, $currentStatus) {
            // Update status seluruh data payroll pada periode ini
            PayrollResult::where('month', $month)
                ->where('year', $year)
                ->update(['status' => $targetStatus]);

            $currentDate = \Carbon\Carbon::createFromDate($year, $month, 15)->endOfMonth();

            // Ambil seluruh rincian pemotongan pinjaman pada periode ini yang memiliki nilai > 0
            $loanComponentIds = PayrollComponent::where('name', 'like', '%Pinjaman%')->pluck('id');
            $loanResults = PayrollResult::where('month', $month)
                ->where('year', $year)
                ->whereIn('payroll_component_id', $loanComponentIds)
                ->where('amount', '>', 0)
                ->get();

            if ($targetStatus === 'final' && $currentStatus === 'draft') {
                // FINALISASI: Potong sisa saldo pinjaman (remaining_amount) dan tutup pinjaman jika lunas
                foreach ($loanResults as $lr) {
                    $empLoan = EmpLoan::where('emp_id', $lr->emp_id)
                        ->where('status', 'open')
                        ->where(function ($query) use ($currentDate) {
                            $query->whereDate('start_date', '<=', $currentDate)
                                ->where(function ($q) use ($currentDate) {
                                    $q->whereNull('end_date')
                                        ->orWhereDate('end_date', '>=', $currentDate);
                                });
                        })
                        ->first();

                    if ($empLoan) {
                        $currRemaining = $empLoan->remaining_amount !== null ? (float) $empLoan->remaining_amount : (float) $empLoan->amount;
                        $newRemaining = max(0.0, $currRemaining - (float) $lr->amount);
                        $newPaid = min((float) $empLoan->amount, (float) ($empLoan->paid_amount ?? 0) + (float) $lr->amount);

                        if (\Illuminate\Support\Facades\Schema::hasColumn('emp_loans', 'paid_amount')) {
                            $empLoan->paid_amount = $newPaid;
                        }
                        if (\Illuminate\Support\Facades\Schema::hasColumn('emp_loans', 'remaining_amount')) {
                            $empLoan->remaining_amount = $newRemaining;
                        }
                        if ($newRemaining <= 0) {
                            $empLoan->status = 'closed'; // Lunas
                        }
                        $empLoan->save();
                    }
                }
            } elseif ($targetStatus === 'draft' && $currentStatus === 'final') {
                // REVERT KE DRAFT: Kembalikan saldo pinjaman yang sebelumnya terpotong
                foreach ($loanResults as $lr) {
                    $empLoan = EmpLoan::where('emp_id', $lr->emp_id)
                        ->where(function ($query) use ($currentDate) {
                            $query->whereDate('start_date', '<=', $currentDate)
                                ->where(function ($q) use ($currentDate) {
                                    $q->whereNull('end_date')
                                        ->orWhereDate('end_date', '>=', $currentDate);
                                });
                        })
                        ->latest('id')
                        ->first();

                    if ($empLoan) {
                        $currRemaining = $empLoan->remaining_amount !== null ? (float) $empLoan->remaining_amount : 0.0;
                        $restoredRemaining = min((float) $empLoan->amount, $currRemaining + (float) $lr->amount);
                        $restoredPaid = max(0.0, (float) ($empLoan->paid_amount ?? 0) - (float) $lr->amount);

                        if (\Illuminate\Support\Facades\Schema::hasColumn('emp_loans', 'paid_amount')) {
                            $empLoan->paid_amount = $restoredPaid;
                        }
                        if (\Illuminate\Support\Facades\Schema::hasColumn('emp_loans', 'remaining_amount')) {
                            $empLoan->remaining_amount = $restoredRemaining;
                        }
                        if ($restoredRemaining > 0) {
                            $empLoan->status = 'open'; // Buka kembali pinjaman
                        }
                        $empLoan->save();
                    }
                }
            }
        });

        $statusLabel = $targetStatus === 'final'
            ? 'Final (Saldo pinjaman karyawan berhasil dipotong & slip gaji dirilis ke ESS)'
            : 'Draft (Disembunyikan dari ESS & pemotongan saldo pinjaman dikembalikan)';

        return back()->with('success', "Status payroll periode {$monthLabel} {$year} berhasil diubah menjadi: {$statusLabel}.");
    }

    public function slip($emp_id, $month, $year)
    {
        $user = auth()->user();
        $isBackoffice = $user && ($user->canAccessFinanceBackoffice() || $user->isSuperAdmin());

        // Employee Isolation: Karyawan biasa HANYA boleh membuka slip miliknya sendiri!
        if (!$isBackoffice) {
            $currentEmp = $user?->employee ?? $user?->getEmployeeRecord();
            if (!$currentEmp || (int)$currentEmp->id !== (int)$emp_id) {
                abort(403, 'Akses ditolak. Anda tidak berhak melihat data slip gaji karyawan lain.');
            }

            // Pastikan statusnya harus final jika diakses oleh karyawan
            if (\Illuminate\Support\Facades\Schema::hasColumn('payroll_results', 'status')) {
                $hasNonFinal = PayrollResult::where('emp_id', $emp_id)
                    ->where('month', (int)$month)
                    ->where('year', (int)$year)
                    ->where('status', '!=', 'final')
                    ->exists();

                if ($hasNonFinal) {
                    abort(403, 'Slip gaji untuk periode ini belum berstatus final / belum dipublikasikan oleh HRD.');
                }
            }
        }

        $employee = EmpData::findOrFail($emp_id);

        $query = PayrollResult::with(['payrollComponent', 'payrollSetting'])
            ->where('emp_id', $emp_id)
            ->where('month', $month)
            ->where('year', $year);

        // Jika bukan backoffice, pastikan query hanya mengambil status final
        if (!$isBackoffice && \Illuminate\Support\Facades\Schema::hasColumn('payroll_results', 'status')) {
            $query->where('status', 'final');
        }

        $results = $query->get();

        if ($results->isEmpty()) {
            return redirect()->route('payroll-results.index')->with('error', 'No payroll data found for this employee.');
        }

        // Group by type for the view
        $allowances = $results->filter(fn($r) =>
            $r->payrollComponent &&
            (
                $r->payrollComponent->type === 'allowance' ||
                ($r->payrollComponent->type === 'thr' && $r->amount > 0)
            )
        );
        $subsidies = collect(); // Remove subsidi from payslip
        $bpjs = collect(); // Move BPJS to deductions
        $deductions = $results->filter(fn($r) => $r->payrollComponent && in_array($r->payrollComponent->type, ['deduction', 'bpjs']));

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

        // Split columns by type
        $allowanceColumns = $payrollColumns->whereIn('type', ['allowance', 'thr']);
        $deductionColumns = $payrollColumns->whereIn('type', ['deduction', 'bpjs']);

        // Calculate total columns: 4 (No,Company,Project,Employee) + allowances + 1 (TotalGaji) + deductions + 2 (TotalPotongan + TakeHomePay)
        $totalColumns = 4 + $allowanceColumns->count() + 1 + $deductionColumns->count() + 2;

        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document title
        $monthName = \DateTime::createFromFormat('!m', $month)->format('F');
        $lastColumnLetter = $this->getColumnLetter($totalColumns);
        $sheet->setCellValue('A1', "Payroll Results - {$monthName} {$year}");
        $sheet->mergeCells('A1:' . $lastColumnLetter . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set header row
        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Company');
        $sheet->setCellValue('C3', 'Project');
        $sheet->setCellValue('D3', 'Employee Name');

        $column = 'E';
        // Allowance columns
        foreach ($allowanceColumns as $col) {
            $sheet->setCellValue($column . '3', $col->name);
            $column++;
        }
        // Total Gaji column
        $totalGajiCol = $column;
        $sheet->setCellValue($column . '3', 'Total Gaji');
        $column++;

        // Deduction columns
        foreach ($deductionColumns as $col) {
            $sheet->setCellValue($column . '3', $col->name);
            $column++;
        }
        // Total Potongan column
        $totalPotonganCol = $column;
        $sheet->setCellValue($column . '3', 'Total Potongan');
        $column++;
        // Take Home Pay column
        $takeHomePayCol = $column;
        $sheet->setCellValue($column . '3', 'Take Home Pay');

        // Style header row
        $sheet->getStyle('A3:' . $lastColumnLetter . '3')->getFont()->setBold(true);
        $sheet->getStyle('A3:' . $lastColumnLetter . '3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2EFDA');
        $sheet->getStyle('A3:' . $lastColumnLetter . '3')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Apply right alignment to numeric columns (from E onwards)
        $sheet->getStyle('E3:' . $lastColumnLetter . '3')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Calculate totals for footer
        $grandTotalGaji = 0;
        $grandTotalPotongan = 0;
        $allowanceTotals = [];
        $deductionTotals = [];

        foreach ($allowanceColumns as $col) {
            $allowanceTotals[$col->id] = 0;
        }
        foreach ($deductionColumns as $col) {
            $deductionTotals[$col->id] = 0;
        }

        // Fill data
        $row = 4;
        $no = 1;
        foreach ($groupedResults as $empId => $empResults) {
            $employee = $empResults->first()->employee;

            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $employee->project->company->code ?? 'N/A');
            $sheet->setCellValue('C' . $row, $employee->project->name ?? 'N/A');
            $sheet->setCellValue('D' . $row, $employee->name ?? 'N/A');

            $totalGaji = 0;
            $totalPotongan = 0;

            // Fill allowance columns
            $column = 'E';
            foreach ($allowanceColumns as $col) {
                $result = $empResults->firstWhere('payroll_component_id', $col->id);
                $amount = $result ? $result->amount : 0;
                $sheet->setCellValue($column . $row, $amount);
                $sheet->getStyle($column . $row)->getNumberFormat()
                    ->setFormatCode('#,##0.00');
                $sheet->getStyle($column . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $totalGaji += $amount;
                $allowanceTotals[$col->id] += $amount;
                $column++;
            }

            // Total Gaji
            $sheet->setCellValue($column . $row, $totalGaji);
            $sheet->getStyle($column . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
            $sheet->getStyle($column . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($column . $row)->getFont()->setBold(true);
            $sheet->getStyle($column . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E2EFDA');
            $column++;

            // Fill deduction columns
            foreach ($deductionColumns as $col) {
                $result = $empResults->firstWhere('payroll_component_id', $col->id);
                $amount = $result ? $result->amount : 0;
                $sheet->setCellValue($column . $row, $amount);
                $sheet->getStyle($column . $row)->getNumberFormat()
                    ->setFormatCode('#,##0.00');
                $sheet->getStyle($column . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $totalPotongan += $amount;
                $deductionTotals[$col->id] += $amount;
                $column++;
            }

            // Total Potongan
            $sheet->setCellValue($column . $row, $totalPotongan);
            $sheet->getStyle($column . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
            $sheet->getStyle($column . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($column . $row)->getFont()->setBold(true);
            $sheet->getStyle($column . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FCE4D6');
            $column++;

            // Take Home Pay
            $takeHomePay = $totalGaji - $totalPotongan;
            $sheet->setCellValue($column . $row, $takeHomePay);
            $sheet->getStyle($column . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
            $sheet->getStyle($column . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($column . $row)->getFont()->setBold(true);
            $sheet->getStyle($column . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D9E1F2');

            // Style data row
            $sheet->getStyle('A' . $row . ':' . $lastColumnLetter . $row)->getBorders()
                ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $grandTotalGaji += $totalGaji;
            $grandTotalPotongan += $totalPotongan;

            $row++;
            $no++;
        }

        // Add totals row
        $sheet->setCellValue('A' . $row, 'Total');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row . ':D' . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $column = 'E';
        // Fill allowance totals
        foreach ($allowanceColumns as $col) {
            $sheet->setCellValue($column . $row, $allowanceTotals[$col->id]);
            $sheet->getStyle($column . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
            $sheet->getStyle($column . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($column . $row)->getFont()->setBold(true);
            $column++;
        }

        // Grand Total Gaji
        $sheet->setCellValue($column . $row, $grandTotalGaji);
        $sheet->getStyle($column . $row)->getNumberFormat()
            ->setFormatCode('#,##0.00');
        $sheet->getStyle($column . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle($column . $row)->getFont()->setBold(true);
        $sheet->getStyle($column . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2EFDA');
        $column++;

        // Fill deduction totals
        foreach ($deductionColumns as $col) {
            $sheet->setCellValue($column . $row, $deductionTotals[$col->id]);
            $sheet->getStyle($column . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
            $sheet->getStyle($column . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($column . $row)->getFont()->setBold(true);
            $column++;
        }

        // Grand Total Potongan
        $sheet->setCellValue($column . $row, $grandTotalPotongan);
        $sheet->getStyle($column . $row)->getNumberFormat()
            ->setFormatCode('#,##0.00');
        $sheet->getStyle($column . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle($column . $row)->getFont()->setBold(true);
        $sheet->getStyle($column . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FCE4D6');
        $column++;

        // Grand Take Home Pay
        $sheet->setCellValue($column . $row, $grandTotalGaji - $grandTotalPotongan);
        $sheet->getStyle($column . $row)->getNumberFormat()
            ->setFormatCode('#,##0.00');
        $sheet->getStyle($column . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle($column . $row)->getFont()->setBold(true);
        $sheet->getStyle($column . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9E1F2');

        // Style totals row
        $sheet->getStyle('A' . $row . ':' . $lastColumnLetter . $row)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Auto size columns
        foreach (range('A', $lastColumnLetter) as $col) {
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

    public function deductions(Request $request)
    {
        return redirect()->route('payroll-results.show', array_merge($request->all(), ['tab' => 'bpjs']));
    }

    public function exportDeductions(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $results = PayrollResult::with(['employee.project.company', 'payrollComponent', 'payrollSetting'])
            ->where('month', $month)
            ->where('year', $year)
            ->whereHas('payrollComponent', fn($q) => $q->whereIn('type', ['subsidi', 'bpjs']))
            ->get();

        $groupedResults = $results->groupBy('emp_id');

        if ($groupedResults->isEmpty()) {
            return redirect()->back()->with('error', 'No data to export.');
        }

        $payrollColumns = $groupedResults->first()->map(fn($d) => $d->payrollComponent);

        // Split columns by type
        $subsidiColumns = $payrollColumns->where('type', 'subsidi');
        $bpjsColumns = $payrollColumns->where('type', 'bpjs');

        // Calculate total columns
        $totalColumns = 4 + $subsidiColumns->count() + ($subsidiColumns->isNotEmpty() ? 1 : 0) + $bpjsColumns->count() + 1;

        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document title
        $monthName = \DateTime::createFromFormat('!m', $month)->format('F');
        $lastColumnLetter = $this->getColumnLetter($totalColumns);
        $sheet->setCellValue('A1', "Payroll BPJS - {$monthName} {$year}");
        $sheet->mergeCells('A1:' . $lastColumnLetter . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set header row
        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Company');
        $sheet->setCellValue('C3', 'Project');
        $sheet->setCellValue('D3', 'Employee Name');

        $column = 'E';
        // Subsidi columns
        foreach ($subsidiColumns as $col) {
            $sheet->setCellValue($column . '3', $col->name);
            $column++;
        }
        // Total Subsidi column
        if ($subsidiColumns->isNotEmpty()) {
            $totalSubsidiCol = $column;
            $sheet->setCellValue($column . '3', 'Total Subsidi');
            $column++;
        }

        // BPJS columns
        foreach ($bpjsColumns as $col) {
            $sheet->setCellValue($column . '3', $col->name);
            $column++;
        }
        // Total Potongan column
        $totalPotonganCol = $column;
        $sheet->setCellValue($column . '3', 'Total Potongan');

        // Style header row
        $sheet->getStyle('A3:' . $lastColumnLetter . '3')->getFont()->setBold(true);
        $sheet->getStyle('A3:' . $lastColumnLetter . '3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2EFDA');
        $sheet->getStyle('A3:' . $lastColumnLetter . '3')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Apply right alignment to numeric columns (from E onwards)
        $sheet->getStyle('E3:' . $lastColumnLetter . '3')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Calculate totals for footer
        $grandTotalSubsidi = 0;
        $grandTotalPotongan = 0;
        $subsidiTotals = [];
        $bpjsTotals = [];

        foreach ($subsidiColumns as $col) {
            $subsidiTotals[$col->id] = 0;
        }
        foreach ($bpjsColumns as $col) {
            $bpjsTotals[$col->id] = 0;
        }

        // Fill data
        $row = 4;
        $no = 1;
        foreach ($groupedResults as $empId => $empResults) {
            $employee = $empResults->first()->employee;

            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $employee->project->company->code ?? 'N/A');
            $sheet->setCellValue('C' . $row, $employee->project->name ?? 'N/A');
            $sheet->setCellValue('D' . $row, $employee->name ?? 'N/A');

            $totalSubsidi = 0;
            $totalPotongan = 0;

            // Fill subsidi columns
            $column = 'E';
            foreach ($subsidiColumns as $col) {
                $result = $empResults->firstWhere('payroll_component_id', $col->id);
                $amount = $result ? $result->amount : 0;
                $sheet->setCellValue($column . $row, $amount);
                $sheet->getStyle($column . $row)->getNumberFormat()
                    ->setFormatCode('#,##0.00');
                $sheet->getStyle($column . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $totalSubsidi += $amount;
                $subsidiTotals[$col->id] += $amount;
                $column++;
            }

            // Total Subsidi
            if ($subsidiColumns->isNotEmpty()) {
                $sheet->setCellValue($column . $row, $totalSubsidi);
                $sheet->getStyle($column . $row)->getNumberFormat()
                    ->setFormatCode('#,##0.00');
                $sheet->getStyle($column . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle($column . $row)->getFont()->setBold(true);
                $sheet->getStyle($column . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFF2CC');
                $column++;
            }

            // Fill BPJS columns
            foreach ($bpjsColumns as $col) {
                $result = $empResults->firstWhere('payroll_component_id', $col->id);
                $amount = $result ? $result->amount : 0;
                $sheet->setCellValue($column . $row, $amount);
                $sheet->getStyle($column . $row)->getNumberFormat()
                    ->setFormatCode('#,##0.00');
                $sheet->getStyle($column . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $totalPotongan += $amount;
                $bpjsTotals[$col->id] += $amount;
                $column++;
            }

            // Total Potongan
            $sheet->setCellValue($column . $row, $totalPotongan);
            $sheet->getStyle($column . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
            $sheet->getStyle($column . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($column . $row)->getFont()->setBold(true);
            $sheet->getStyle($column . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FCE4D6');

            // Style data row
            $sheet->getStyle('A' . $row . ':' . $lastColumnLetter . $row)->getBorders()
                ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $grandTotalSubsidi += $totalSubsidi;
            $grandTotalPotongan += $totalPotongan;

            $row++;
            $no++;
        }

        // Add totals row
        $sheet->setCellValue('A' . $row, 'Total');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row . ':D' . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $column = 'E';
        // Fill subsidi totals
        foreach ($subsidiColumns as $col) {
            $sheet->setCellValue($column . $row, $subsidiTotals[$col->id]);
            $sheet->getStyle($column . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
            $sheet->getStyle($column . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($column . $row)->getFont()->setBold(true);
            $column++;
        }

        // Grand Total Subsidi
        if ($subsidiColumns->isNotEmpty()) {
            $sheet->setCellValue($column . $row, $grandTotalSubsidi);
            $sheet->getStyle($column . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
            $sheet->getStyle($column . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($column . $row)->getFont()->setBold(true);
            $sheet->getStyle($column . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FFF2CC');
            $column++;
        }

        // Fill BPJS totals
        foreach ($bpjsColumns as $col) {
            $sheet->setCellValue($column . $row, $bpjsTotals[$col->id]);
            $sheet->getStyle($column . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
            $sheet->getStyle($column . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($column . $row)->getFont()->setBold(true);
            $column++;
        }

        // Grand Total Potongan
        $sheet->setCellValue($column . $row, $grandTotalPotongan);
        $sheet->getStyle($column . $row)->getNumberFormat()
            ->setFormatCode('#,##0.00');
        $sheet->getStyle($column . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle($column . $row)->getFont()->setBold(true);
        $sheet->getStyle($column . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FCE4D6');

        // Style totals row
        $sheet->getStyle('A' . $row . ':' . $lastColumnLetter . $row)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Auto size columns
        foreach (range('A', $lastColumnLetter) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set filename
        $filename = "payroll_deductions_{$month}_{$year}.xlsx";

        // Stream file
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

    /**
     * Hitung & sinkronkan denda keterlambatan langsung ke Potongan Karyawan untuk Payroll.
     */
    public function syncLate(Request $request, LatenessDeductionService $service)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2000|max:2100',
        ]);

        $month = (int) $request->month;
        $year  = (int) $request->year;

        $header = PotonganKaryawan::firstOrCreate([
            'year'  => $year,
            'month' => $month,
        ]);

        $calc = $service->syncToPotonganKaryawan($header);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Potongan keterlambatan periode ' . $calc['period']['label'] . ' berhasil disinkronkan ke Potongan Karyawan.',
                'data'    => $calc,
            ]);
        }

        return back()->with('success', 'Potongan keterlambatan berhasil disinkronkan ke Potongan Karyawan.');
    }
}
