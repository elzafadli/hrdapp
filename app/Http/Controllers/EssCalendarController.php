<?php

namespace App\Http\Controllers;

use App\Models\ApprovalSetting;
use App\Models\EmpData;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestDate;
use App\Models\OvertimeRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class EssCalendarController extends Controller
{
    /**
     * Dapatkan employee untuk user yang sedang login.
     */
    protected function getEmployee()
    {
        $user = Auth::user();
        return $user?->employee
            ?? EmpData::where('user_id', $user?->id)->first()
            ?? EmpData::where('name', $user?->name)->first()
            ?? EmpData::first();
    }

    /**
     * Tampilan utama kalender ESS dengan grid bulanan & filter instan client-side / AJAX.
     */
    public function index(Request $request)
    {
        $employee = $this->getEmployee();
        $user     = Auth::user();

        // Parameter Bulan & Tahun
        $year  = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        if ($month < 1) {
            $month = 12;
            $year--;
        } elseif ($month > 12) {
            $month = 1;
            $year++;
        }

        // Cek Hak Akses Approver / Supervisor
        $isApprover = false;
        try {
            if ($employee?->jabatan_id) {
                $isApprover = ApprovalSetting::isJabatanAnApprover((int) $employee->jabatan_id);
            }
            if (in_array($user?->role, ['admin', 'superadmin', 'hrd', 'project_manager'])) {
                $isApprover = true;
            }
        } catch (\Throwable $e) {
            $isApprover = false;
        }

        // Scope Awal: 'my' (Pengajuan Saya) atau 'team' (Pengajuan Tim)
        $scope = $request->input('scope', 'my');
        if (!$isApprover) {
            $scope = 'my';
        }

        $filterType   = $request->input('type', 'all');
        $filterStatus = $request->input('status', 'all');

        // Rentang Waktu Grid Kalender (Senin s/d Minggu)
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth()->endOfDay();
        $startOfGrid  = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $endOfGrid    = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        // Ambil Data Hari Libur Nasional
        $holidays = collect();
        if (Schema::hasTable('holidays')) {
            $holidays = Holiday::whereBetween('holiday_date', [
                $startOfGrid->toDateString(),
                $endOfGrid->toDateString()
            ])->get()->keyBy(function ($h) {
                return Carbon::parse($h->holiday_date)->format('Y-m-d');
            });
        }

        // 1. Ambil Pengajuan Cuti, Cuti Khusus, Izin, Sakit dari LeaveRequest & LeaveRequestDate
        $eventsByDate = [];
        $uniqueSubmissions = [];

        if (Schema::hasTable('leave_requests')) {
            $leaveQuery = LeaveRequestDate::with([
                'leaveRequest.employee.jabatan',
                'leaveRequest.specialLeaveType',
                'leaveRequest.approvals.jabatan',
                'leaveRequest.approvals.approver',
                'leaveRequest.files',
            ])->whereBetween('date', [
                $startOfGrid->toDateString(),
                $endOfGrid->toDateString()
            ]);

            // Jika bukan approver/admin, batasi hanya data dirinya sendiri
            if (!$isApprover && $employee) {
                $leaveQuery->where('emp_id', $employee->id);
            }

            $leaveDates = $leaveQuery->get();

            foreach ($leaveDates as $ld) {
                $lr = $ld->leaveRequest;
                if (!$lr) continue;

                $dateStr = Carbon::parse($ld->date)->format('Y-m-d');
                $eventPayload = $this->formatLeaveEvent($lr, $ld, $employee);
                $eventsByDate[$dateStr][] = $eventPayload;

                $submissionKey = 'leave_' . $lr->id;
                if (!isset($uniqueSubmissions[$submissionKey])) {
                    $uniqueSubmissions[$submissionKey] = [
                        'type'        => $lr->leave_type,
                        'status'      => $lr->status,
                        'is_mine'     => $eventPayload['is_mine'],
                        'days'        => (float) ($lr->total_days ?? 1),
                        'hours'       => 0,
                        'is_in_month' => Carbon::parse($lr->full_day_start_date ?? $ld->date)->month === $month,
                    ];
                }
            }
        }

        // 2. Ambil Pengajuan Lembur dari OvertimeRequest
        if (Schema::hasTable('overtime_requests')) {
            $otQuery = OvertimeRequest::with([
                'employee.jabatan',
                'approvals.jabatan',
                'approvals.approver',
                'files',
            ])->whereBetween('date', [
                $startOfGrid->toDateString(),
                $endOfGrid->toDateString()
            ]);

            // Jika bukan approver/admin, batasi hanya data dirinya sendiri
            if (!$isApprover && $employee) {
                $otQuery->where('emp_id', $employee->id);
            }

            $overtimes = $otQuery->get();

            foreach ($overtimes as $ot) {
                $dateStr = Carbon::parse($ot->date)->format('Y-m-d');
                $eventPayload = $this->formatOvertimeEvent($ot, $employee);
                $eventsByDate[$dateStr][] = $eventPayload;

                $submissionKey = 'ot_' . $ot->id;
                if (!isset($uniqueSubmissions[$submissionKey])) {
                    $uniqueSubmissions[$submissionKey] = [
                        'type'        => 'overtime',
                        'status'      => $ot->status,
                        'is_mine'     => $eventPayload['is_mine'],
                        'days'        => 0,
                        'hours'       => (float) ($ot->total_hours ?? 0),
                        'is_in_month' => Carbon::parse($ot->date)->month === $month,
                    ];
                }
            }
        }

        // 3. Susun Grid Sel Kalender (Hari per Hari)
        $calendarGrid = [];
        $cursor = $startOfGrid->copy();

        while ($cursor->lte($endOfGrid)) {
            $dateKey = $cursor->format('Y-m-d');
            $dayEvents = $eventsByDate[$dateKey] ?? [];

            usort($dayEvents, function ($a, $b) {
                return strcmp($a['type_label'], $b['type_label']);
            });

            $holidayItem = $holidays->get($dateKey);

            $calendarGrid[] = [
                'date'             => $dateKey,
                'day_number'       => $cursor->day,
                'is_current_month' => $cursor->month === $month,
                'is_today'         => $cursor->isToday(),
                'is_weekend'       => in_array($cursor->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]),
                'day_name'         => $cursor->locale('id')->isoFormat('dddd'),
                'holiday'          => $holidayItem ? [
                    'name'            => $holidayItem->name,
                    'is_cuti_bersama' => (bool) $holidayItem->is_cuti_bersama,
                    'description'     => $holidayItem->description,
                ] : null,
                'events'           => $dayEvents,
                'events_count'     => count($dayEvents),
            ];

            $cursor->addDay();
        }

        // 4. Kalkulasi Statistik Default Bulan Terpilih
        $stats = [
            'total_submissions' => 0,
            'approved'          => 0,
            'pending'           => 0,
            'rejected'          => 0,
            'total_leave_days'  => 0.0,
            'total_ot_hours'    => 0.0,
        ];

        foreach ($uniqueSubmissions as $item) {
            if (!$item['is_in_month']) continue;

            $stats['total_submissions']++;

            if (in_array($item['status'], ['approved', 'completed'])) {
                $stats['approved']++;
                $stats['total_leave_days'] += $item['days'];
                $stats['total_ot_hours'] += $item['hours'];
            } elseif ($item['status'] === 'pending') {
                $stats['pending']++;
            } elseif (in_array($item['status'], ['rejected', 'cancelled'])) {
                $stats['rejected']++;
            }
        }

        // Data Navigasi Bulan
        $currentMonthName = $startOfMonth->locale('id')->isoFormat('MMMM Y');
        $prevMonth = $startOfMonth->copy()->subMonth();
        $nextMonth = $startOfMonth->copy()->addMonth();

        // JIKA REQUEST VIA AJAX / JSON (Untuk navigasi bulan mulus tanpa reload)
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'year'             => $year,
                'month'            => $month,
                'currentMonthName' => $currentMonthName,
                'prevMonth'        => ['month' => $prevMonth->month, 'year' => $prevMonth->year],
                'nextMonth'        => ['month' => $nextMonth->month, 'year' => $nextMonth->year],
                'calendarGrid'     => $calendarGrid,
                'stats'            => $stats,
            ]);
        }

        return view('ess.calendar.index', [
            'employee'         => $employee,
            'isApprover'       => $isApprover,
            'year'             => $year,
            'month'            => $month,
            'currentMonthName' => $currentMonthName,
            'prevMonth'        => ['month' => $prevMonth->month, 'year' => $prevMonth->year],
            'nextMonth'        => ['month' => $nextMonth->month, 'year' => $nextMonth->year],
            'scope'            => $scope,
            'filterType'       => $filterType,
            'filterStatus'     => $filterStatus,
            'calendarGrid'     => $calendarGrid,
            'stats'            => $stats,
        ]);
    }

    /**
     * Format payload LeaveRequest menjadi format rapi untuk event kalender & modal detail.
     */
    protected function formatLeaveEvent(LeaveRequest $lr, LeaveRequestDate $ld, ?EmpData $currentEmp): array
    {
        $typeMeta = match ($lr->leave_type) {
            'annual'  => [
                'label'      => 'Cuti Tahunan',
                'icon'       => 'fa-umbrella-beach',
                'badge_bg'   => 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100',
                'chip_color' => 'blue',
                'accent'     => 'from-blue-500 to-indigo-600',
                'route_show' => route('leave.show', $lr->id),
            ],
            'special' => [
                'label'      => 'Cuti Khusus',
                'icon'       => 'fa-sparkles',
                'badge_bg'   => 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100',
                'chip_color' => 'purple',
                'accent'     => 'from-purple-500 to-fuchsia-600',
                'route_show' => route('special-leave.show', $lr->id),
            ],
            'permit'  => [
                'label'      => 'Izin',
                'icon'       => 'fa-file-signature',
                'badge_bg'   => 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100',
                'chip_color' => 'amber',
                'accent'     => 'from-amber-500 to-orange-600',
                'route_show' => route('permit.show', $lr->id),
            ],
            'sick'    => [
                'label'      => 'Sakit',
                'icon'       => 'fa-notes-medical',
                'badge_bg'   => 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100',
                'chip_color' => 'rose',
                'accent'     => 'from-rose-500 to-red-600',
                'route_show' => route('sick.show', $lr->id),
            ],
            default   => [
                'label'      => 'Pengajuan Cuti',
                'icon'       => 'fa-calendar-check',
                'badge_bg'   => 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100',
                'chip_color' => 'slate',
                'accent'     => 'from-slate-500 to-gray-600',
                'route_show' => route('leave.show', $lr->id),
            ],
        };

        $statusMeta = match ($lr->status) {
            'completed', 'approved' => [
                'label' => 'Disetujui',
                'dot'   => 'bg-emerald-500',
                'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'icon'  => 'fa-circle-check text-emerald-600',
            ],
            'pending'   => [
                'label' => 'Menunggu Persetujuan',
                'dot'   => 'bg-amber-400 animate-pulse',
                'badge' => 'bg-amber-100 text-amber-800 border-amber-200',
                'icon'  => 'fa-clock text-amber-600',
            ],
            'rejected'  => [
                'label' => 'Ditolak',
                'dot'   => 'bg-rose-500',
                'badge' => 'bg-rose-100 text-rose-800 border-rose-200',
                'icon'  => 'fa-circle-xmark text-rose-600',
            ],
            'cancelled' => [
                'label' => 'Dibatalkan',
                'dot'   => 'bg-gray-400',
                'badge' => 'bg-gray-100 text-gray-700 border-gray-200',
                'icon'  => 'fa-ban text-gray-500',
            ],
            default     => [
                'label' => ucfirst($lr->status),
                'dot'   => 'bg-slate-400',
                'badge' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon'  => 'fa-circle text-slate-500',
            ],
        };

        // Format approval steps
        $approvals = [];
        foreach ($lr->approvals as $app) {
            $approvals[] = [
                'level'        => $app->level,
                'jabatan'      => $app->jabatan?->jabatan ?? ('Level ' . $app->level),
                'status'       => $app->status,
                'approver_name'=> $app->approver?->name ?? '-',
                'notes'        => $app->notes ?? '-',
                'action_at'    => $app->updated_at ? $app->updated_at->locale('id')->isoFormat('D MMM Y, HH:mm') : null,
            ];
        }

        // Format file lampiran
        $files = [];
        foreach ($lr->files as $file) {
            $files[] = [
                'id'        => $file->id,
                'file_name' => $file->file_name ?? 'Lampiran Dokumen',
                'url'       => $file->url,
                'size'      => $file->file_size ? number_format($file->file_size / 1024, 1) . ' KB' : null,
            ];
        }

        $startDateFormatted = $lr->full_day_start_date ? Carbon::parse($lr->full_day_start_date)->locale('id')->isoFormat('D MMM Y') : null;
        $endDateFormatted   = $lr->full_day_end_date ? Carbon::parse($lr->full_day_end_date)->locale('id')->isoFormat('D MMM Y') : null;

        $dateRangeDisplay = $startDateFormatted;
        if ($endDateFormatted && $endDateFormatted !== $startDateFormatted) {
            $dateRangeDisplay .= ' - ' . $endDateFormatted;
        }

        $isHalfDay = $ld->day_type === 'half_day';
        $dayTypeLabel = $isHalfDay ? ('Setengah Hari (' . ($ld->half_day_type === 'first_half' ? 'Awal Shift' : 'Akhir Shift') . ')') : 'Hari Penuh';

        return [
            'id'                 => 'leave_' . $lr->id,
            'raw_id'             => $lr->id,
            'request_number'     => $lr->request_number ?? ('CUTI-' . $lr->id),
            'type'               => $lr->leave_type,
            'type_label'         => $typeMeta['label'],
            'type_icon'          => $typeMeta['icon'],
            'type_badge_bg'      => $typeMeta['badge_bg'],
            'type_chip_color'    => $typeMeta['chip_color'],
            'type_accent'        => $typeMeta['accent'],
            'route_show'         => $typeMeta['route_show'],
            'status'             => $lr->status,
            'status_label'       => $statusMeta['label'],
            'status_dot'         => $statusMeta['dot'],
            'status_badge'       => $statusMeta['badge'],
            'status_icon'        => $statusMeta['icon'],
            'emp_name'           => $lr->employee?->name ?? 'Karyawan',
            'emp_nip'            => $lr->employee?->nip ?? '-',
            'emp_jabatan'        => $lr->employee?->jabatan?->jabatan ?? '-',
            'date_current'       => Carbon::parse($ld->date)->locale('id')->isoFormat('dddd, D MMMM Y'),
            'date_range_display' => $dateRangeDisplay,
            'duration_display'   => ($lr->total_days ?? 1) . ' Hari Kerja',
            'duration_numeric'   => (float) ($lr->total_days ?? 1),
            'day_type_label'     => $dayTypeLabel,
            'reason'             => $lr->reason ?? 'Tidak ada keterangan khusus.',
            'special_type_name'  => $lr->specialLeaveType?->name,
            'rejection_reason'   => $lr->rejection_reason,
            'approvals'          => $approvals,
            'files'              => $files,
            'is_mine'            => $currentEmp && $lr->emp_id === $currentEmp->id,
        ];
    }

    /**
     * Format payload OvertimeRequest menjadi format rapi untuk event kalender & modal detail.
     */
    protected function formatOvertimeEvent(OvertimeRequest $ot, ?EmpData $currentEmp): array
    {
        $statusMeta = match ($ot->status) {
            'completed', 'approved' => [
                'label' => 'Disetujui',
                'dot'   => 'bg-emerald-500',
                'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'icon'  => 'fa-circle-check text-emerald-600',
            ],
            'pending'   => [
                'label' => 'Menunggu Persetujuan',
                'dot'   => 'bg-amber-400 animate-pulse',
                'badge' => 'bg-amber-100 text-amber-800 border-amber-200',
                'icon'  => 'fa-clock text-amber-600',
            ],
            'rejected'  => [
                'label' => 'Ditolak',
                'dot'   => 'bg-rose-500',
                'badge' => 'bg-rose-100 text-rose-800 border-rose-200',
                'icon'  => 'fa-circle-xmark text-rose-600',
            ],
            'cancelled' => [
                'label' => 'Dibatalkan',
                'dot'   => 'bg-gray-400',
                'badge' => 'bg-gray-100 text-gray-700 border-gray-200',
                'icon'  => 'fa-ban text-gray-500',
            ],
            default     => [
                'label' => ucfirst($ot->status),
                'dot'   => 'bg-slate-400',
                'badge' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon'  => 'fa-circle text-slate-500',
            ],
        };

        // Format approval steps
        $approvals = [];
        foreach ($ot->approvals as $app) {
            $approvals[] = [
                'level'        => $app->level,
                'jabatan'      => $app->jabatan?->jabatan ?? ('Level ' . $app->level),
                'status'       => $app->status,
                'approver_name'=> $app->approver?->name ?? '-',
                'notes'        => $app->notes ?? '-',
                'action_at'    => $app->updated_at ? $app->updated_at->locale('id')->isoFormat('D MMM Y, HH:mm') : null,
            ];
        }

        // Format file lampiran
        $files = [];
        foreach ($ot->files as $file) {
            $files[] = [
                'id'        => $file->id,
                'file_name' => $file->file_name ?? 'Lampiran Lembur',
                'url'       => $file->url,
                'size'      => $file->file_size ? number_format($file->file_size / 1024, 1) . ' KB' : null,
            ];
        }

        $dateFormatted = Carbon::parse($ot->date)->locale('id')->isoFormat('dddd, D MMMM Y');
        $startTime = $ot->start_time ? substr($ot->start_time, 0, 5) : '00:00';
        $endTime   = $ot->end_time ? substr($ot->end_time, 0, 5) : '00:00';

        $durationDisplay = ($ot->total_hours ?? 0) . ' Jam (' . $startTime . ' - ' . $endTime . ')';

        return [
            'id'                 => 'ot_' . $ot->id,
            'raw_id'             => $ot->id,
            'request_number'     => $ot->request_number ?? ('LBR-' . $ot->id),
            'type'               => 'overtime',
            'type_label'         => 'Lembur',
            'type_icon'          => 'fa-business-time',
            'type_badge_bg'      => 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100',
            'type_chip_color'    => 'emerald',
            'type_accent'        => 'from-emerald-500 to-teal-600',
            'route_show'         => route('overtime.show', $ot->id),
            'status'             => $ot->status,
            'status_label'       => $statusMeta['label'],
            'status_dot'         => $statusMeta['dot'],
            'status_badge'       => $statusMeta['badge'],
            'status_icon'        => $statusMeta['icon'],
            'emp_name'           => $ot->employee?->name ?? 'Karyawan',
            'emp_nip'            => $ot->employee?->nip ?? '-',
            'emp_jabatan'        => $ot->employee?->jabatan?->jabatan ?? '-',
            'date_current'       => $dateFormatted,
            'date_range_display' => $dateFormatted,
            'duration_display'   => $durationDisplay,
            'duration_numeric'   => (float) ($ot->total_hours ?? 0),
            'day_type_label'     => $ot->day_type === 'holiday' ? 'Hari Libur / Off' : 'Hari Kerja',
            'reason'             => $ot->reason ?? 'Lembur pekerjaan operasional.',
            'special_type_name'  => null,
            'overtime_task'      => $ot->task_description,
            'activity_type'      => $ot->activity_type,
            'rejection_reason'   => $ot->rejection_reason,
            'approvals'          => $approvals,
            'files'              => $files,
            'is_mine'            => $currentEmp && $ot->emp_id === $currentEmp->id,
        ];
    }
}
