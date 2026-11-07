<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\EmpData;
use App\Models\EmpLoan;
use App\Models\PayrollComponent;
use App\Models\PayrollSetting;
use App\Models\PayrollSettingDetail;
use App\Models\PotonganKaryawan;
use App\Models\PotonganKaryawanDetail;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class LatenessDeductionService
{
    /**
     * Dapatkan rentang tanggal cut-off presensi berdasarkan tahun & bulan.
     * Default cut-off: tanggal 25 bulan sebelumnya s/d tanggal 25 bulan berjalan.
     */
    public function getCutoffPeriod(int $year, int $month, ?int $startDay = null, ?int $endDay = null): array
    {
        $setting = PayrollSetting::where('is_aktif', 1)->first() ?? PayrollSetting::first();

        if ($startDay === null) {
            $startDay = ($setting && Schema::hasColumn('payroll_settings', 'cutoff_start_day') && !empty($setting->cutoff_start_day))
                ? (int) $setting->cutoff_start_day
                : 25;
        }

        if ($endDay === null) {
            $endDay = ($setting && Schema::hasColumn('payroll_settings', 'cutoff_end_day') && !empty($setting->cutoff_end_day))
                ? (int) $setting->cutoff_end_day
                : 25;
        }

        // Tanggal Akhir: Tanggal cut-off bulan berjalan (e.g. 25 Okt 2026 23:59:59)
        $endDate = Carbon::createFromDate($year, $month, $endDay)->endOfDay();

        // Tanggal Awal: Tanggal cut-off bulan sebelumnya (e.g. 25 Sep 2026 00:00:00)
        $startDate = Carbon::createFromDate($year, $month, $startDay)->subMonth()->startOfDay();

        return [
            'start_day'            => $startDay,
            'end_day'              => $endDay,
            'start_date'           => $startDate,
            'end_date'             => $endDate,
            'start_date_formatted' => $startDate->format('Y-m-d'),
            'end_date_formatted'   => $endDate->format('Y-m-d'),
            'label'                => $startDate->translatedFormat('d M Y') . ' s/d ' . $endDate->translatedFormat('d M Y'),
        ];
    }

    /**
     * Dapatkan nominal denda per keterlambatan dari Master Setting Payroll.
     * Default fallback: Rp 15.000.
     */
    public function getLateRate(?PayrollSetting $setting = null): float
    {
        if (!$setting) {
            $setting = PayrollSetting::where('is_aktif', 1)->first() ?? PayrollSetting::first();
        }

        $component = $this->getLateComponent();

        if ($setting && $component) {
            $detail = PayrollSettingDetail::where('payroll_setting_id', $setting->id)
                ->where('payroll_component_id', $component->id)
                ->first();

            if ($detail && (float) $detail->base_amount > 0) {
                return (float) $detail->base_amount;
            }
        }

        return 15000.0;
    }

    /**
     * Dapatkan Master Component untuk Keterlambatan.
     */
    public function getLateComponent(): ?PayrollComponent
    {
        return PayrollComponent::where('name', 'like', '%Keterlambatan%')
            ->orWhere('id', 14)
            ->first();
    }

    /**
     * Dapatkan Master Component untuk Pinjaman Karyawan.
     */
    public function getLoanComponent(): ?PayrollComponent
    {
        return PayrollComponent::where('name', 'like', '%Pinjaman%')
            ->orWhere('id', 13)
            ->first();
    }

    /**
     * Hitung akumulasi cicilan pinjaman karyawan yang aktif pada bulan dan tahun tersebut.
     */
    public function calculateLoans(int $year, int $month): array
    {
        $currentDate = Carbon::createFromDate($year, $month, 15)->endOfMonth();
        $component = $this->getLoanComponent();
        $componentId = $component ? $component->id : 13;

        // Ambil pinjaman aktif yang masih berjalan pada periode ini
        $activeLoans = EmpLoan::with(['employee.jabatan', 'project'])
            ->where('status', 'open')
            ->where(function ($query) use ($currentDate) {
                $query->whereDate('start_date', '<=', $currentDate)
                    ->where(function ($q) use ($currentDate) {
                        $q->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $currentDate);
                    });
            })
            ->get();

        $employees = EmpData::with('jabatan')->get();
        $results = [];
        $totalLoanEmployees = 0;
        $totalLoanAmount = 0;

        foreach ($employees as $emp) {
            $empLoans = $activeLoans->where('emp_id', $emp->id);
            $installmentTotal = 0;
            $saldoBeforeTotal = 0;
            $saldoAfterTotal = 0;
            $loanList = [];

            foreach ($empLoans as $loan) {
                $balance = $loan->calculateBalanceForPeriod($year, $month);
                $saldoBefore = $balance['saldo_before'];

                // Hanya proses jika saldo pinjaman masih ada
                if ($saldoBefore > 0) {
                    $installment = $balance['installment_due'];
                    $installmentTotal += $installment;
                    $saldoBeforeTotal += $saldoBefore;
                    $saldoAfterTotal += $balance['saldo_after'];

                    $loanList[] = [
                        'id'                    => $loan->id,
                        'loan_amount'           => (float) $loan->amount,
                        'formatted_loan'        => number_format((float) $loan->amount, 2, ',', '.'),
                        'total_paid_before'     => $balance['total_paid_before'],
                        'formatted_paid_before' => number_format($balance['total_paid_before'], 2, ',', '.'),
                        'saldo_before'          => $saldoBefore,
                        'formatted_saldo_before'=> number_format($saldoBefore, 2, ',', '.'),
                        'installment_amount'    => $installment,
                        'formatted_inst'        => number_format($installment, 2, ',', '.'),
                        'saldo_after'           => $balance['saldo_after'],
                        'formatted_saldo_after' => number_format($balance['saldo_after'], 2, ',', '.'),
                        'is_paid_off'           => $balance['is_paid_off'],
                        'duration'              => $loan->duration,
                        'start_date'            => $loan->start_date ? Carbon::parse($loan->start_date)->translatedFormat('d M Y') : '-',
                        'end_date'              => $loan->end_date ? Carbon::parse($loan->end_date)->translatedFormat('d M Y') : '-',
                        'description'           => $loan->description,
                    ];
                }
            }

            if ($installmentTotal > 0) {
                $totalLoanEmployees++;
                $totalLoanAmount += $installmentTotal;
            }

            $results[$emp->id] = [
                'employee_id'           => $emp->id,
                'name'                  => $emp->name,
                'jabatan'               => $emp->jabatan->jabatan ?? '-',
                'has_active_loan'       => count($loanList) > 0,
                'installment_total'     => $installmentTotal,
                'formatted_amount'      => number_format($installmentTotal, 2, '.', ''),
                'saldo_before_total'    => $saldoBeforeTotal,
                'formatted_saldo_before'=> number_format($saldoBeforeTotal, 2, ',', '.'),
                'saldo_after_total'     => $saldoAfterTotal,
                'formatted_saldo_after' => number_format($saldoAfterTotal, 2, ',', '.'),
                'loans'                 => $loanList,
            ];
        }

        return [
            'success'                   => true,
            'component_id'              => $componentId,
            'component_name'            => $component ? $component->name : 'Potongan Pinjaman/Lain-lain',
            'summary'                   => [
                'total_loan_employees'   => $totalLoanEmployees,
                'total_loan_amount'      => $totalLoanAmount,
                'formatted_total'        => number_format($totalLoanAmount, 2, ',', '.'),
            ],
            'deductions'                => $results,
        ];
    }

    /**
     * Hitung akumulasi keterlambatan presensi dan denda potongan per karyawan.
     */
    public function calculate(
        int $year,
        int $month,
        ?string $customStartDate = null,
        ?string $customEndDate = null,
        ?float $customRate = null
    ): array {
        // Tentukan periode cut-off
        $periodInfo = $this->getCutoffPeriod($year, $month);
        $startDate = $customStartDate ? Carbon::parse($customStartDate)->startOfDay() : $periodInfo['start_date'];
        $endDate   = $customEndDate ? Carbon::parse($customEndDate)->endOfDay() : $periodInfo['end_date'];

        // Tentukan tarif denda
        $rate = $customRate !== null && $customRate >= 0 ? $customRate : $this->getLateRate();

        // Cari master component
        $component = $this->getLateComponent();
        $componentId = $component ? $component->id : 14;

        // Ambil seluruh karyawan
        $employees = EmpData::with('jabatan')->get();

        // Ambil presensi di rentang tanggal cut-off
        $attendances = Attendance::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date', 'asc')
            ->orderBy('check_in_time', 'asc')
            ->get()
            ->groupBy('emp_id');

        $results = [];
        $totalLateEmployees = 0;
        $totalLateEvents = 0;
        $totalDeductionAmount = 0;

        foreach ($employees as $emp) {
            $empAtts = $attendances->get($emp->id, collect());

            // Filter presensi yang terlambat berdasarkan status presensi atau jadwal kerja karyawan
            $lateRecords = [];
            foreach ($empAtts as $att) {
                $isLate = false;
                $checkInTime = $att->check_in_time ? substr($att->check_in_time, 0, 8) : null;
                $attDate = $att->date ? Carbon::parse($att->date) : null;

                // Tentukan batas toleransi keterlambatan dari Jadwal Kerja (WorkSchedule)
                $lateLimit = '08:30';
                $scheduleName = 'Reguler';
                if ($attDate) {
                    $schedule = $emp->getWorkScheduleForDate($attDate) ?? WorkSchedule::getDefault();
                    if ($schedule) {
                        $scheduleName = $schedule->name;
                        $dayOfWeek = $attDate->dayOfWeekIso;
                        $scheduleDay = $schedule->days ? $schedule->days->where('day_of_week', $dayOfWeek)->first() : null;
                        if ($scheduleDay && !empty($scheduleDay->late_tolerance_time)) {
                            $lateLimit = substr($scheduleDay->late_tolerance_time, 0, 5);
                        } elseif ($scheduleDay && !empty($scheduleDay->start_time)) {
                            $tolMin = $schedule->late_tolerance_minutes ?? 30;
                            $lateLimit = Carbon::parse($scheduleDay->start_time)->addMinutes($tolMin)->format('H:i');
                        }
                    }
                }

                if ($att->check_in_status === 'late' || $att->status === 'late') {
                    $isLate = true;
                } elseif (!empty($checkInTime)) {
                    $timeShort = substr($checkInTime, 0, 5);
                    if ($timeShort > $lateLimit) {
                        $isLate = true;
                    }
                }

                if ($isLate) {
                    $lateRecords[] = [
                        'id'            => $att->id,
                        'date'          => $attDate ? $attDate->format('Y-m-d') : null,
                        'date_label'    => $attDate ? $attDate->translatedFormat('d M Y') : '-',
                        'check_in_time' => $checkInTime,
                        'late_limit'    => $lateLimit,
                        'schedule_name' => $scheduleName,
                        'status'        => $att->check_in_status ?? $att->status,
                        'notes'         => $att->check_in_notes,
                    ];
                }
            }

            $lateCount = count($lateRecords);
            $deductionAmount = $lateCount * $rate;

            if ($lateCount > 0) {
                $totalLateEmployees++;
                $totalLateEvents += $lateCount;
                $totalDeductionAmount += $deductionAmount;
            }

            $results[$emp->id] = [
                'employee_id'      => $emp->id,
                'name'             => $emp->name,
                'jabatan'          => $emp->jabatan->jabatan ?? '-',
                'late_count'       => $lateCount,
                'rate'             => $rate,
                'deduction_amount' => $deductionAmount,
                'formatted_amount' => number_format($deductionAmount, 2, '.', ''),
                'records'          => $lateRecords,
            ];
        }

        return [
            'success'                   => true,
            'year'                      => $year,
            'month'                     => $month,
            'rate'                      => $rate,
            'rate_formatted'            => number_format($rate, 0, ',', '.'),
            'component_id'              => $componentId,
            'component_name'            => $component ? $component->name : 'Keterlambatan',
            'period'                    => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date'   => $endDate->format('Y-m-d'),
                'label'      => $startDate->translatedFormat('d M Y') . ' s/d ' . $endDate->translatedFormat('d M Y'),
            ],
            'summary'                   => [
                'total_employees'        => $employees->count(),
                'total_late_employees'   => $totalLateEmployees,
                'total_late_events'      => $totalLateEvents,
                'total_deduction_amount' => $totalDeductionAmount,
                'formatted_total'        => number_format($totalDeductionAmount, 2, ',', '.'),
            ],
            'deductions'                => $results,
        ];
    }

    /**
     * Hitung sekaligus Keterlambatan dan Pinjaman Karyawan.
     */
    public function calculateAll(
        int $year,
        int $month,
        ?string $customStartDate = null,
        ?string $customEndDate = null,
        ?float $customRate = null
    ): array {
        $late = $this->calculate($year, $month, $customStartDate, $customEndDate, $customRate);
        $loans = $this->calculateLoans($year, $month);

        return [
            'success'   => true,
            'year'      => $year,
            'month'     => $month,
            'period'    => $late['period'],
            'late'      => $late,
            'loans'     => $loans,
            // Format kompatibilitas dengan handler sebelumnya
            'component_id' => $late['component_id'],
            'loan_component_id' => $loans['component_id'],
            'summary'   => [
                'total_late_events'      => $late['summary']['total_late_events'],
                'total_late_employees'   => $late['summary']['total_late_employees'],
                'total_late_amount'      => $late['summary']['total_deduction_amount'],
                'total_loan_employees'   => $loans['summary']['total_loan_employees'],
                'total_loan_amount'      => $loans['summary']['total_loan_amount'],
                'grand_total_amount'     => $late['summary']['total_deduction_amount'] + $loans['summary']['total_loan_amount'],
                'formatted_grand_total'  => number_format($late['summary']['total_deduction_amount'] + $loans['summary']['total_loan_amount'], 2, ',', '.'),
            ],
            'deductions' => $late['deductions'],
            'loan_deductions' => $loans['deductions'],
        ];
    }

    /**
     * Sinkronkan hasil kalkulasi keterlambatan & pinjaman langsung ke tabel potongan_karyawan_details.
     */
    public function syncToPotonganKaryawan(
        PotonganKaryawan $potonganKaryawan,
        ?string $customStartDate = null,
        ?string $customEndDate = null,
        ?float $customRate = null
    ): array {
        $all = $this->calculateAll(
            (int) $potonganKaryawan->year,
            (int) $potonganKaryawan->month,
            $customStartDate,
            $customEndDate,
            $customRate
        );

        $lateComponentId = $all['late']['component_id'];
        $loanComponentId = $all['loans']['component_id'];
        $updatedCount = 0;

        // 1. Simpan Keterlambatan
        foreach ($all['late']['deductions'] as $empId => $data) {
            $val = $data['deduction_amount'];
            if ($val > 0) {
                PotonganKaryawanDetail::updateOrCreate(
                    [
                        'header_id'            => $potonganKaryawan->id,
                        'employee_id'          => $empId,
                        'payroll_component_id' => $lateComponentId,
                    ],
                    [
                        'value' => $val,
                    ]
                );
                $updatedCount++;
            }
        }

        // 2. Simpan Pinjaman Karyawan
        foreach ($all['loans']['deductions'] as $empId => $data) {
            $val = $data['installment_total'];
            if ($val > 0) {
                PotonganKaryawanDetail::updateOrCreate(
                    [
                        'header_id'            => $potonganKaryawan->id,
                        'employee_id'          => $empId,
                        'payroll_component_id' => $loanComponentId,
                    ],
                    [
                        'value' => $val,
                    ]
                );
                $updatedCount++;
            }
        }

        $all['synced_count'] = $updatedCount;
        return $all;
    }
}
