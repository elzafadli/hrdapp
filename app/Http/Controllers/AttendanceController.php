<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\EmpData;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AttendanceController extends Controller
{
    /**
     * Dapatkan employee untuk user yang sedang login.
     */
    protected function getEmployee()
    {
        $user = auth()->user();
        return $user->employee
            ?? EmpData::where('user_id', $user->id)->first()
            ?? EmpData::where('name', $user->name)->first()
            ?? EmpData::first();
    }

    /**
     * Dapatkan informasi shift hari ini untuk karyawan.
     */
    protected function getTodayShift($employee, $date = null)
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::today();
        $isHoliday = Holiday::isHoliday($targetDate);

        if (!$employee) {
            return [
                'name'        => 'Reguler',
                'start_time'  => '08:00',
                'end_time'    => '17:00',
                'is_work_day' => !$isHoliday && !in_array($targetDate->dayOfWeekIso, [6, 7]),
                'label'       => $isHoliday ? 'Libur Nasional' : (in_array($targetDate->dayOfWeekIso, [6, 7]) ? 'Day Off • 00:00 - 23:59' : 'Reguler • 08:00 - 17:00'),
            ];
        }

        $schedule = $employee->getWorkScheduleForDate($targetDate);
        $dayOfWeek = $targetDate->dayOfWeekIso; // 1: Senin ... 7: Minggu
        $scheduleDay = $schedule ? $schedule->days->where('day_of_week', $dayOfWeek)->first() : null;

        $isWorkDay = ($scheduleDay ? $scheduleDay->is_work_day : !in_array($dayOfWeek, [6, 7])) && !$isHoliday;
        $startTime = $scheduleDay && $scheduleDay->start_time ? substr($scheduleDay->start_time, 0, 5) : '08:00';
        $endTime = $scheduleDay && $scheduleDay->end_time ? substr($scheduleDay->end_time, 0, 5) : '17:00';

        $shiftLabel = $isHoliday
            ? 'Libur Nasional • 00:00 - 23:59'
            : (!$isWorkDay ? 'Day Off • 00:00 - 23:59' : ($schedule ? $schedule->name : 'Reguler') . ' • ' . $startTime . ' - ' . $endTime);

        return [
            'name'        => $schedule ? $schedule->name : 'Reguler',
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'is_work_day' => $isWorkDay,
            'label'       => $shiftLabel,
        ];
    }

    /**
     * Hitung jarak dua titik koordinat GPS (meter) dengan formula Haversine.
     */
    protected function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // Meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }

    /**
     * Tampilan Halaman Presensi Masuk (Check-In).
     */
    public function checkInView(Request $request)
    {
        $employee = $this->getEmployee();
        $today = Carbon::today()->toDateString();
        $shift = $this->getTodayShift($employee);
        $officeSetting = AttendanceSetting::getActiveSetting();

        $todayAttendance = null;
        if ($employee && Schema::hasTable('attendances')) {
            $todayAttendance = Attendance::where('emp_id', $employee->id)->where('date', $today)->first();
        }

        return view('ess.attendance.check-in', compact('employee', 'shift', 'officeSetting', 'todayAttendance'));
    }

    /**
     * Tampilan Halaman Presensi Keluar (Check-Out).
     */
    public function checkOutView(Request $request)
    {
        $employee = $this->getEmployee();
        $today = Carbon::today()->toDateString();
        $shift = $this->getTodayShift($employee);
        $officeSetting = AttendanceSetting::getActiveSetting();

        $todayAttendance = null;
        if ($employee && Schema::hasTable('attendances')) {
            $todayAttendance = Attendance::where('emp_id', $employee->id)->where('date', $today)->first();
        }

        return view('ess.attendance.check-out', compact('employee', 'shift', 'officeSetting', 'todayAttendance'));
    }

    /**
     * Simpan Presensi Masuk (Check-In).
     */
    public function storeCheckIn(Request $request)
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Data karyawan tidak ditemukan.'], 404);
        }

        if (!Schema::hasTable('attendances')) {
            return response()->json(['success' => false, 'message' => 'Tabel presensi belum dimigrasi. Silakan jalankan php artisan migrate.'], 500);
        }

        $request->validate([
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes'     => 'nullable|string|max:500',
        ]);

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $lat = $request->filled('latitude') ? (float) $request->input('latitude') : null;
        $lng = $request->filled('longitude') ? (float) $request->input('longitude') : null;

        $shift = $this->getTodayShift($employee);
        $shiftStart = Carbon::parse($today . ' ' . $shift['start_time']);
        $isLate = $now->gt($shiftStart);

        $attendance = Attendance::firstOrNew([
            'emp_id' => $employee->id,
            'date'   => $today,
        ]);

        if ($attendance->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan presensi masuk hari ini pada pukul ' . substr($attendance->check_in_time, 0, 5) . '.',
            ], 422);
        }

        $attendance->check_in_time = $now->toTimeString();
        $attendance->check_in_lat = $lat;
        $attendance->check_in_lng = $lng;
        $attendance->check_in_distance = 0;
        $attendance->check_in_status = $isLate ? 'late' : 'on_time';
        $attendance->check_in_notes = $request->input('notes');
        $attendance->status = $isLate ? 'late' : 'present';
        $attendance->save();

        return response()->json([
            'success'  => true,
            'message'  => 'Presensi masuk berhasil dicatat pada pukul ' . $now->format('H:i:s') . ' (' . ($isLate ? 'Terlambat' : 'Tepat Waktu') . ').',
            'time'     => $now->format('H:i:s'),
            'status'   => $isLate ? 'late' : 'on_time',
        ]);
    }

    /**
     * Simpan Presensi Keluar (Check-Out).
     */
    public function storeCheckOut(Request $request)
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Data karyawan tidak ditemukan.'], 404);
        }

        if (!Schema::hasTable('attendances')) {
            return response()->json(['success' => false, 'message' => 'Tabel presensi belum dimigrasi. Silakan jalankan php artisan migrate.'], 500);
        }

        $request->validate([
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes'     => 'nullable|string|max:500',
        ]);

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $attendance = Attendance::where('emp_id', $employee->id)->where('date', $today)->first();
        if (!$attendance || !$attendance->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum melakukan presensi masuk hari ini. Silakan presensi masuk terlebih dahulu.',
            ], 422);
        }

        if ($attendance->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan presensi keluar hari ini pada pukul ' . substr($attendance->check_out_time, 0, 5) . '.',
            ], 422);
        }

        $lat = $request->filled('latitude') ? (float) $request->input('latitude') : null;
        $lng = $request->filled('longitude') ? (float) $request->input('longitude') : null;

        $shift = $this->getTodayShift($employee);
        $shiftEnd = Carbon::parse($today . ' ' . $shift['end_time']);
        $isEarly = $now->lt($shiftEnd);

        $checkInDateTime = Carbon::parse($today . ' ' . $attendance->check_in_time);
        $durationMinutes = $checkInDateTime->diffInMinutes($now);

        $attendance->check_out_time = $now->toTimeString();
        $attendance->check_out_lat = $lat;
        $attendance->check_out_lng = $lng;
        $attendance->check_out_distance = 0;
        $attendance->check_out_status = $isEarly ? 'early_leave' : 'normal';
        $attendance->check_out_notes = $request->input('notes');
        $attendance->work_duration_minutes = $durationMinutes;
        $attendance->save();

        $hours = floor($durationMinutes / 60);
        $mins = $durationMinutes % 60;

        return response()->json([
            'success'  => true,
            'message'  => "Presensi keluar berhasil dicatat pada pukul {$now->format('H:i:s')} (Durasi kerja: {$hours} jam {$mins} menit).",
            'time'     => $now->format('H:i:s'),
            'duration' => "{$hours} Jam {$mins} Menit",
        ]);
    }

    /**
     * Riwayat Presensi Karyawan.
     */
    public function history(Request $request)
    {
        $employee = $this->getEmployee();
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        $schedule = $employee ? $employee->getWorkScheduleForDate() : \App\Models\WorkSchedule::getDefault();
        $todayShift = $this->getTodayShift($employee);
        $officeSetting = AttendanceSetting::getActiveSetting();

        $attendances = collect();
        if ($employee && Schema::hasTable('attendances')) {
            $attendances = Attendance::where('emp_id', $employee->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->orderBy('date', 'desc')
                ->paginate(31);
        }

        return view('ess.attendance.history', compact('employee', 'attendances', 'month', 'year', 'schedule', 'todayShift', 'officeSetting'));
    }
}

