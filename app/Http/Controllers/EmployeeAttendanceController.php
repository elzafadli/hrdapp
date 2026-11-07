<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\EmpData;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestDate;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EmployeeAttendanceController extends Controller
{
    /**
     * Tampilan Rekap Presensi Karyawan & Monitoring Klarifikasi Terlambat.
     */
    public function index(Request $request)
    {
        // 1. Filter Tanggal & Bulan (Periode Penggajian Cut-Off Tanggal 25)
        $selectedMonth = $request->input('month');
        $selectedYear = (int) $request->input('year', date('Y'));

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
        } elseif ($selectedMonth) {
            // Cut-off tanggal 25: dari 25 bulan sebelumnya s/d 25 bulan yang dipilih
            $targetDate = Carbon::createFromDate($selectedYear, (int) $selectedMonth, 25);
            $startDate = $targetDate->copy()->subMonth()->day(25)->startOfDay();
            $endDate = $targetDate->copy()->day(25)->endOfDay();
        } else {
            // Default ke periode cut-off penggajian berjalan (25 bulan sebelumnya s/d 25 bulan sekarang)
            $now = Carbon::today();
            if ($now->day < 25) {
                $startDate = $now->copy()->subMonth()->day(25)->startOfDay();
                $endDate = $now->copy()->day(25)->endOfDay();
                $selectedMonth = (string) $now->month;
            } else {
                $startDate = $now->copy()->day(25)->startOfDay();
                $endDate = $now->copy()->addMonth()->day(25)->endOfDay();
                $selectedMonth = (string) $now->copy()->addMonth()->month;
            }
        }

        $startDateStr = $startDate->toDateString();
        $endDateStr = $endDate->toDateString();

        // 2. Query Karyawan
        $employeeQuery = EmpData::with(['jabatan', 'project', 'company'])->orderBy('name');

        if ($request->filled('employee_id')) {
            $employeeQuery->where('id', $request->input('employee_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $employeeQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nik_karyawan', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        $employees = $employeeQuery->paginate(100)->withQueryString();
        $empIds = $employees->pluck('id')->toArray();

        // 3. Pre-fetch Data Attendances & Leave Requests untuk optimasi query (Anti N+1)
        $attendances = collect();
        if (Schema::hasTable('attendances') && !empty($empIds)) {
            $attendances = Attendance::whereIn('emp_id', $empIds)
                ->whereBetween('date', [$startDateStr, $endDateStr])
                ->orderBy('date', 'asc')
                ->get()
                ->groupBy('emp_id');
        }

        $leaveDates = collect();
        if (Schema::hasTable('leave_request_dates') && !empty($empIds)) {
            $leaveDates = LeaveRequestDate::with(['leaveRequest.approvals'])
                ->whereIn('emp_id', $empIds)
                ->whereBetween('date', [$startDateStr, $endDateStr])
                ->get()
                ->groupBy('emp_id');
        }

        // Pre-fetch hari libur nasional dalam rentang tanggal
        $holidayDates = Holiday::getHolidayDatesBetween($startDate, $endDate);
        $defaultSchedule = WorkSchedule::getDefault();

        // 4. Kalkulasi Rekapitulasi per Karyawan
        $recapData = [];

        foreach ($employees as $emp) {
            $empAtts = $attendances->get($emp->id, collect());
            $empLeaves = $leaveDates->get($emp->id, collect())->keyBy(function ($ld) {
                return Carbon::parse($ld->date)->toDateString();
            });

            // Hitung hari kerja
            $schedule = $emp->getWorkScheduleForDate($startDate) ?? $defaultSchedule;
            if ($schedule) {
                $workingDays = (int) $schedule->calculateWorkingDays($startDate, $endDate);
            } else {
                $workingDays = 0;
                $period = CarbonPeriod::create($startDate, $endDate);
                foreach ($period as $date) {
                    if (!in_array($date->toDateString(), $holidayDates) && $date->dayOfWeekIso <= 5) {
                        $workingDays++;
                    }
                }
            }

            // Jumlah Tepat Waktu
            $onTimeCount = $empAtts->filter(function ($att) {
                return $att->check_in_status === 'on_time' || ($att->check_in_time && $att->check_in_status !== 'late' && $att->status !== 'late');
            })->count();

            // Keterlambatan
            $lateAtts = $empAtts->filter(function ($att) {
                return $att->check_in_status === 'late' || $att->status === 'late';
            });

            $lateDetails = [];
            $klarifikasiCount = 0;
            $tidakKlarifikasiCount = 0;

            foreach ($lateAtts as $late) {
                $dateStr = Carbon::parse($late->date)->toDateString();
                $leaveDateRecord = $empLeaves->get($dateStr);
                $leaveReq = $leaveDateRecord?->leaveRequest;

                // Fallback jika tidak ada record di leave_request_dates tapi ada di leave_requests
                if (!$leaveReq) {
                    $leaveReq = LeaveRequest::with('approvals')
                        ->where('emp_id', $emp->id)
                        ->where(function ($q) use ($dateStr) {
                            $q->where(function ($sub) use ($dateStr) {
                                $sub->whereDate('full_day_start_date', '<=', $dateStr)
                                    ->whereDate('full_day_end_date', '>=', $dateStr);
                            })->orWhere(function ($sub) use ($dateStr) {
                                $sub->whereDate('half_day_start_date', '<=', $dateStr)
                                    ->whereDate('half_day_end_date', '>=', $dateStr);
                            });
                        })->first();
                }

                $hasClarification = $leaveReq !== null;
                $izinCuti = '';
                $statusText = '';
                $statusBadge = 'none';

                if ($hasClarification) {
                    $klarifikasiCount++;

                    // Format kode Izin / Cuti (SI 123, SC 123, dll)
                    $prefix = match ($leaveReq->leave_type) {
                        'permit'  => 'SI',
                        'annual'  => 'SC',
                        'special' => 'SCK',
                        'sick'    => 'SK',
                        default   => 'IZ',
                    };

                    $reqNum = $leaveReq->request_number ?? '';
                    if (!empty($reqNum) && preg_match('/^(\d+)/', $reqNum, $matches)) {
                        $seq = ltrim($matches[1], '0');
                        $izinCuti = "{$prefix} " . (!empty($seq) ? $seq : $matches[1]);
                    } elseif (!empty($reqNum)) {
                        $izinCuti = "{$prefix} " . $reqNum;
                    } else {
                        $izinCuti = "{$prefix} " . $leaveReq->id;
                    }

                    // Status
                    if (in_array($leaveReq->status, ['completed', 'approved'])) {
                        $statusText = 'Ok';
                        $statusBadge = 'success';
                    } elseif ($leaveReq->status === 'pending') {
                        $pendingApproval = $leaveReq->getCurrentPendingApproval();
                        $level = $pendingApproval ? $pendingApproval->level : 1;
                        $statusText = "Aproval {$level}";
                        $statusBadge = 'warning';
                    } elseif ($leaveReq->status === 'rejected') {
                        $statusText = 'Ditolak';
                        $statusBadge = 'danger';
                    } else {
                        $statusText = ucfirst($leaveReq->status);
                        $statusBadge = 'secondary';
                    }
                } else {
                    $tidakKlarifikasiCount++;
                    // Sesuai contoh pada gambar: jika belum klarifikasi, kolom Izin / Cuti dan Status dikosongkan
                    $izinCuti = '';
                    $statusText = '';
                    $statusBadge = 'none';
                }

                $lateDetails[] = [
                    'nama'              => $emp->name,
                    'nid'               => (string) ($emp->nik_karyawan ?: $emp->id),
                    'tgl_telat'         => Carbon::parse($late->date)->format('d/m/Y'),
                    'tgl_raw'           => $dateStr,
                    'izin_cuti'         => $izinCuti,
                    'full_doc_number'   => $leaveReq?->request_number ?? '',
                    'status'            => $statusText,
                    'status_badge'      => $statusBadge,
                    'has_clarification' => $hasClarification,
                    'check_in_time'     => $late->check_in_time ? substr($late->check_in_time, 0, 5) : '-',
                    'notes'             => $late->check_in_notes ?: ($leaveReq?->reason ?: '-'),
                ];
            }

            $recapData[] = (object) [
                'employee'              => $emp,
                'nama'                  => $emp->name,
                'nid'                   => (string) ($emp->nik_karyawan ?: $emp->id),
                'jumlah_hari_kerja'     => $workingDays,
                'jumlah_tepat_waktu'    => $onTimeCount,
                'klarifikasi_terlambat' => $klarifikasiCount,
                'tidak_klarifikasi'     => $tidakKlarifikasiCount,
                'late_details'          => $lateDetails,
            ];
        }

        // List semua karyawan untuk dropdown filter
        $allEmployees = EmpData::orderBy('name')->pluck('name', 'id')->toArray();

        return view('employee_attendance.index', compact(
            'employees',
            'recapData',
            'startDateStr',
            'endDateStr',
            'selectedMonth',
            'selectedYear',
            'allEmployees'
        ));
    }

    /**
     * Endpoint API JSON untuk mengambil detail keterlambatan karyawan (opsional).
     */
    public function getLateDetail(Request $request, EmpData $employee)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $lateAtts = Attendance::where('emp_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('check_in_status', 'late')->orWhere('status', 'late');
            })
            ->orderBy('date', 'asc')
            ->get();

        $empLeaves = LeaveRequestDate::with(['leaveRequest.approvals'])
            ->where('emp_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(fn($ld) => Carbon::parse($ld->date)->toDateString());

        $details = [];
        foreach ($lateAtts as $late) {
            $dateStr = Carbon::parse($late->date)->toDateString();
            $leaveReq = $empLeaves->get($dateStr)?->leaveRequest;

            $hasClarification = $leaveReq !== null;
            $izinCuti = '';
            $statusText = '';
            $statusBadge = 'none';

            if ($hasClarification) {
                $prefix = match ($leaveReq->leave_type) {
                    'permit'  => 'SI',
                    'annual'  => 'SC',
                    'special' => 'SCK',
                    'sick'    => 'SK',
                    default   => 'IZ',
                };

                $reqNum = $leaveReq->request_number ?? '';
                if (!empty($reqNum) && preg_match('/^(\d+)/', $reqNum, $matches)) {
                    $seq = ltrim($matches[1], '0');
                    $izinCuti = "{$prefix} " . (!empty($seq) ? $seq : $matches[1]);
                } elseif (!empty($reqNum)) {
                    $izinCuti = "{$prefix} " . $reqNum;
                } else {
                    $izinCuti = "{$prefix} " . $leaveReq->id;
                }

                if (in_array($leaveReq->status, ['completed', 'approved'])) {
                    $statusText = 'Ok';
                    $statusBadge = 'success';
                } elseif ($leaveReq->status === 'pending') {
                    $pendingApproval = $leaveReq->getCurrentPendingApproval();
                    $level = $pendingApproval ? $pendingApproval->level : 1;
                    $statusText = "Aproval {$level}";
                    $statusBadge = 'warning';
                } elseif ($leaveReq->status === 'rejected') {
                    $statusText = 'Ditolak';
                    $statusBadge = 'danger';
                } else {
                    $statusText = ucfirst($leaveReq->status);
                    $statusBadge = 'secondary';
                }
            }

            $details[] = [
                'nama'              => $employee->name,
                'nid'               => (string) ($employee->nik_karyawan ?: $employee->id),
                'tgl_telat'         => Carbon::parse($late->date)->format('d/m/Y'),
                'tgl_raw'           => $dateStr,
                'izin_cuti'         => $izinCuti,
                'status'            => $statusText,
                'status_badge'      => $statusBadge,
                'has_clarification' => $hasClarification,
                'check_in_time'     => $late->check_in_time ? substr($late->check_in_time, 0, 5) : '-',
                'notes'             => $late->check_in_notes ?: ($leaveReq?->reason ?: '-'),
            ];
        }

        return response()->json([
            'employee' => [
                'name' => $employee->name,
                'nid'  => (string) ($employee->nik_karyawan ?: $employee->id),
            ],
            'details'  => $details,
        ]);
    }
}

