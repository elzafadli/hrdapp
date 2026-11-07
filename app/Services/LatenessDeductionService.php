<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\EmpData;
use App\Models\PayrollComponent;
use App\Models\PayrollSetting;
use App\Models\PayrollSettingDetail;
use App\Models\PotonganKaryawan;
use App\Models\PotonganKaryawanDetail;
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

            // Filter presensi yang terlambat (check_in_status === 'late' atau status === 'late' atau jam masuk > 08:30)
            $lateRecords = [];
            foreach ($empAtts as $att) {
                $isLate = false;
                $checkInTime = $att->check_in_time ? substr($att->check_in_time, 0, 8) : null;

                if ($att->check_in_status === 'late' || $att->status === 'late') {
                    $isLate = true;
                } elseif (!empty($checkInTime)) {
                    $timeShort = substr($checkInTime, 0, 5);
                    if ($timeShort > '08:30') {
                        $isLate = true;
                    }
                }

                if ($isLate) {
                    $lateRecords[] = [
                        'id'            => $att->id,
                        'date'          => $att->date ? Carbon::parse($att->date)->format('Y-m-d') : null,
                        'date_label'    => $att->date ? Carbon::parse($att->date)->translatedFormat('d M Y') : '-',
                        'check_in_time' => $checkInTime,
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
     * Sinkronkan hasil kalkulasi keterlambatan langsung ke tabel potongan_karyawan_details.
     */
    public function syncToPotonganKaryawan(
        PotonganKaryawan $potonganKaryawan,
        ?string $customStartDate = null,
        ?string $customEndDate = null,
        ?float $customRate = null
    ): array {
        $calc = $this->calculate(
            (int) $potonganKaryawan->year,
            (int) $potonganKaryawan->month,
            $customStartDate,
            $customEndDate,
            $customRate
        );

        $componentId = $calc['component_id'];
        $updatedCount = 0;

        foreach ($calc['deductions'] as $empId => $data) {
            $val = $data['deduction_amount'];

            // Jika nilai > 0 atau sudah ada record sebelumnya, updateOrCreate
            if ($val > 0) {
                PotonganKaryawanDetail::updateOrCreate(
                    [
                        'header_id'            => $potonganKaryawan->id,
                        'employee_id'          => $empId,
                        'payroll_component_id' => $componentId,
                    ],
                    [
                        'value' => $val,
                    ]
                );
                $updatedCount++;
            }
        }

        $calc['synced_count'] = $updatedCount;
        return $calc;
    }
}
