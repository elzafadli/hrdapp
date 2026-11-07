<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmpData;
use App\Models\LeaveRequestDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class TimelineController extends Controller
{
    /**
     * Tampilan utama timeline aktivitas perusahaan & karyawan.
     * Menampilkan ulang tahun dan cuti/izin/sakit yang sudah di-approve.
     * Lembur dikecualikan sesuai instruksi.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->loadMissing(['employee.jabatan', 'employee.company', 'employee.project.company']);
        }
        $employee = $user?->employee;
        $defaultCompany = $employee?->effective_company ?? Company::first();

        // Filter tab: 'all', 'leave', 'birthday'
        $filterTab = $request->input('tab', 'all');

        // Rentang waktu timeline: 45 hari ke belakang hingga 7 hari ke depan
        $today = Carbon::today();
        $startDate = $today->copy()->subDays(45);
        $endDate = $today->copy()->addDays(7);

        // 1. Ambil pengajuan Cuti, Cuti Khusus, Izin, Sakit yang SUDAH APPROVE / COMPLETED
        // Lembur (OvertimeRequest) TIDAK dimasukkan
        $leavePosts = collect();
        if (Schema::hasTable('leave_request_dates') && Schema::hasTable('leave_requests')) {
            $leaveDates = LeaveRequestDate::with([
                'leaveRequest.employee.jabatan',
                'leaveRequest.employee.company',
                'leaveRequest.employee.project.company',
                'leaveRequest.specialLeaveType',
                'leaveRequest.permitType',
            ])
            ->whereHas('leaveRequest', function ($q) {
                $q->whereIn('status', ['approved', 'completed'])
                  ->whereIn('leave_type', ['annual', 'special', 'permit', 'sick']);
            })
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('date')
            ->get();

            // Grouping berdasarkan tanggal
            $groupedLeaves = $leaveDates->groupBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

            foreach ($groupedLeaves as $dateKey => $items) {
                $dateObj = Carbon::parse($dateKey);

                // Group by employee agar karyawan yang mengajukan rentang hari tidak terduplikasi per hari
                $empGroups = $items->groupBy('emp_id');
                $employeesList = [];

                foreach ($empGroups as $empId => $empItems) {
                    $firstItem = $empItems->first();
                    $emp = $firstItem->leaveRequest?->employee;
                    if (!$emp) {
                        continue;
                    }

                    $leaveReq = $firstItem->leaveRequest;
                    $badges = [];

                    if ($leaveReq->leave_type === 'annual') {
                        $badges[] = [
                            'type' => 'annual',
                            'label' => 'Cuti Tahunan',
                            'dot_class' => 'bg-rose-500',
                            'badge_class' => 'bg-rose-50 text-rose-700 border-rose-100',
                        ];
                    } elseif ($leaveReq->leave_type === 'special') {
                        $badges[] = [
                            'type' => 'special',
                            'label' => 'Cuti Khusus',
                            'dot_class' => 'bg-blue-500',
                            'badge_class' => 'bg-blue-50 text-blue-700 border-blue-100',
                        ];
                        if ($leaveReq->specialLeaveType) {
                            $badges[] = [
                                'type' => 'special_sub',
                                'label' => $leaveReq->specialLeaveType->name,
                                'dot_class' => 'bg-sky-500',
                                'badge_class' => 'bg-sky-50 text-sky-700 border-sky-100',
                            ];
                        }
                    } elseif ($leaveReq->leave_type === 'permit') {
                        $badges[] = [
                            'type' => 'permit',
                            'label' => 'Izin',
                            'dot_class' => 'bg-amber-500',
                            'badge_class' => 'bg-amber-50 text-amber-700 border-amber-100',
                        ];
                        if ($leaveReq->permitType) {
                            $badges[] = [
                                'type' => 'permit_sub',
                                'label' => $leaveReq->permitType->name,
                                'dot_class' => 'bg-amber-600',
                                'badge_class' => 'bg-amber-50 text-amber-800 border-amber-200',
                            ];
                        }
                    } elseif ($leaveReq->leave_type === 'sick') {
                        $badges[] = [
                            'type' => 'sick',
                            'label' => 'Sakit',
                            'dot_class' => 'bg-purple-500',
                            'badge_class' => 'bg-purple-50 text-purple-700 border-purple-100',
                        ];
                    }

                    $employeesList[] = [
                        'employee' => $emp,
                        'name' => $emp->name,
                        'initials' => $this->getInitials($emp->name),
                        'avatar_color' => $this->getAvatarColorClass($emp->name),
                        'photo' => $emp->photo_url ?? null,
                        'jabatan' => $emp->jabatan?->jabatan ?? 'Karyawan',
                        'company' => $emp->effective_company ?? $defaultCompany,
                        'badges' => $badges,
                    ];
                }

                if (!empty($employeesList)) {
                    $postCompany = $employeesList[0]['company'] ?? $defaultCompany;
                    $leavePosts->push([
                        'type' => 'leave',
                        'date' => $dateObj,
                        'date_key' => $dateKey,
                        'is_today' => $dateObj->isToday(),
                        'relative_time' => $this->formatRelativeTime($dateObj),
                        'company' => $postCompany,
                        'employees' => $employeesList,
                        'count' => count($employeesList),
                    ]);
                }
            }
        }

        // 2. Ambil data Ulang Tahun Karyawan
        $birthdayPosts = collect();
        if (Schema::hasTable('emp_data') && Schema::hasColumn('emp_data', 'birth_date')) {
            $allEmployees = EmpData::with(['jabatan', 'company', 'project.company'])
                ->whereNotNull('birth_date')
                ->get();

            // Cek setiap tanggal dari 45 hari lalu s/d 7 hari ke depan
            $checkDates = [];
            for ($d = $endDate->copy(); $d->gte($startDate); $d->subDay()) {
                $checkDates[] = $d->copy();
            }

            foreach ($checkDates as $targetDate) {
                $bdayEmployees = [];
                foreach ($allEmployees as $emp) {
                    $bDate = Carbon::parse($emp->birth_date);
                    if ($bDate->month === $targetDate->month && $bDate->day === $targetDate->day) {
                        $age = $targetDate->year - $bDate->year;
                        $badges = [
                            [
                                'type' => 'birthday',
                                'label' => ($targetDate->isToday() ? 'Berulang Tahun Hari Ini' : 'Ulang Tahun'),
                                'dot_class' => 'bg-rose-500',
                                'badge_class' => 'bg-rose-50 text-rose-700 border-rose-100',
                            ]
                        ];
                        if ($age > 0) {
                            $badges[] = [
                                'type' => 'age',
                                'label' => "Ulang Tahun ke-{$age}",
                                'dot_class' => 'bg-amber-500',
                                'badge_class' => 'bg-amber-50 text-amber-700 border-amber-100',
                            ];
                        }

                        $bdayEmployees[] = [
                            'employee' => $emp,
                            'name' => $emp->name,
                            'initials' => $this->getInitials($emp->name),
                            'avatar_color' => $this->getAvatarColorClass($emp->name),
                            'photo' => $emp->photo_url ?? null,
                            'jabatan' => $emp->jabatan?->jabatan ?? 'Karyawan',
                            'company' => $emp->effective_company ?? $defaultCompany,
                            'badges' => $badges,
                            'is_today' => $targetDate->isToday(),
                        ];
                    }
                }

                if (!empty($bdayEmployees)) {
                    $postCompany = $bdayEmployees[0]['company'] ?? $defaultCompany;
                    $birthdayPosts->push([
                        'type' => 'birthday',
                        'date' => $targetDate,
                        'date_key' => $targetDate->format('Y-m-d'),
                        'is_today' => $targetDate->isToday(),
                        'relative_time' => $this->formatRelativeTime($targetDate),
                        'company' => $postCompany,
                        'employees' => $bdayEmployees,
                        'count' => count($bdayEmployees),
                    ]);
                }
            }
        }

        // Gabungkan berdasarkan filter yang dipilih
        if ($filterTab === 'leave') {
            $feed = $leavePosts;
        } elseif ($filterTab === 'birthday') {
            $feed = $birthdayPosts;
        } else {
            $feed = $leavePosts->concat($birthdayPosts)->sortByDesc(function ($item) {
                return $item['date']->timestamp;
            })->values();
        }

        // Hitung statistik ringkas untuk header / widget
        $todayLeaveCount = $leavePosts->where('is_today', true)->sum('count');
        $todayBdayCount = $birthdayPosts->where('is_today', true)->sum('count');

        return view('timeline.index', compact(
            'feed',
            'filterTab',
            'defaultCompany',
            'employee',
            'todayLeaveCount',
            'todayBdayCount'
        ));
    }

    /**
     * Hitung teks waktu relatif yang elegan (e.g. Hari ini, sehari yang lalu, 2 hari yang lalu).
     */
    protected function formatRelativeTime(Carbon $date): string
    {
        $today = Carbon::today();
        if ($date->isToday()) {
            return 'Hari ini';
        }
        if ($date->isYesterday()) {
            return 'sehari yang lalu';
        }
        $diff = $today->diffInDays($date->startOfDay(), false);
        if ($diff < 0) {
            $absDays = abs($diff);
            return "{$absDays} hari yang lalu";
        }
        if ($diff === 1) {
            return 'Besok';
        }
        return "Dalam {$diff} hari";
    }

    /**
     * Ambil 2 huruf inisial dari nama karyawan.
     */
    protected function getInitials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }

    /**
     * Berikan warna avatar lembut yang serasi berdasarkan nama karyawan.
     */
    protected function getAvatarColorClass(string $name): string
    {
        $colors = [
            'bg-emerald-100 text-emerald-700 border-emerald-200',
            'bg-blue-100 text-blue-700 border-blue-200',
            'bg-violet-100 text-violet-700 border-violet-200',
            'bg-amber-100 text-amber-700 border-amber-200',
            'bg-rose-100 text-rose-700 border-rose-200',
            'bg-teal-100 text-teal-700 border-teal-200',
            'bg-sky-100 text-sky-700 border-sky-200',
        ];
        $idx = abs(crc32($name)) % count($colors);
        return $colors[$idx];
    }
}
