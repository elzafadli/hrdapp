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

        $isWorkDayFallback = !$isHoliday && !in_array($targetDate->dayOfWeekIso, [6, 7]);
        if (!$employee) {
            return [
                'name'                   => 'Reguler',
                'start_time'             => '08:00',
                'end_time'               => '17:00',
                'late_tolerance_minutes' => 30,
                'late_tolerance_time'    => $isWorkDayFallback ? '08:30' : null,
                'is_work_day'            => $isWorkDayFallback,
                'label'                  => $isHoliday ? 'Libur Nasional' : (!$isWorkDayFallback ? 'Day Off (Hari Libur)' : 'Reguler • 08:00 - 17:00'),
            ];
        }

        $schedule = $employee->getWorkScheduleForDate($targetDate);
        $dayOfWeek = $targetDate->dayOfWeekIso; // 1: Senin ... 7: Minggu
        $scheduleDay = $schedule ? $schedule->days->where('day_of_week', $dayOfWeek)->first() : null;

        $isWorkDay = ($scheduleDay ? $scheduleDay->is_work_day : !in_array($dayOfWeek, [6, 7])) && !$isHoliday;
        $startTime = $scheduleDay && $scheduleDay->start_time ? substr($scheduleDay->start_time, 0, 5) : '08:00';
        $endTime = $scheduleDay && $scheduleDay->end_time ? substr($scheduleDay->end_time, 0, 5) : '17:00';

        $lateToleranceMinutes = $schedule ? ($schedule->late_tolerance_minutes ?? 30) : 30;
        $lateToleranceTime = null;
        if ($isWorkDay) {
            if ($scheduleDay && !empty($scheduleDay->late_tolerance_time)) {
                $lateToleranceTime = substr($scheduleDay->late_tolerance_time, 0, 5);
            } else {
                $lateToleranceTime = Carbon::parse($startTime)->addMinutes($lateToleranceMinutes)->format('H:i');
            }
        }

        if ($isHoliday) {
            $shiftLabel = 'Libur Nasional';
        } elseif (!$isWorkDay) {
            $shiftLabel = 'Day Off (Hari Libur)';
        } else {
            $schedName = $schedule ? $schedule->name : 'Reguler';
            $timeRange = $startTime . ' - ' . $endTime;
            $shiftLabel = str_contains($schedName, $timeRange) ? $schedName : ($schedName . ' • ' . $timeRange);
        }

        return [
            'name'                   => $schedule ? $schedule->name : 'Reguler',
            'start_time'             => $startTime,
            'end_time'               => $endTime,
            'late_tolerance_minutes' => $lateToleranceMinutes,
            'late_tolerance_time'    => $lateToleranceTime,
            'is_work_day'            => $isWorkDay,
            'label'                  => $shiftLabel,
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
        $officeSetting = AttendanceSetting::getSettingForEmployee($employee);

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
        $officeSetting = AttendanceSetting::getSettingForEmployee($employee);

        $todayAttendance = null;
        if ($employee && Schema::hasTable('attendances')) {
            $todayAttendance = Attendance::where('emp_id', $employee->id)->where('date', $today)->first();
        }

        return view('ess.attendance.check-out', compact('employee', 'shift', 'officeSetting', 'todayAttendance'));
    }

    /**
     * Simpan foto selfie presensi (dari base64 webcam/kamera HP atau upload file kamera).
     */
    protected function handleAttendancePhoto(Request $request, string $prefix, int $employeeId): ?string
    {
        $year = date('Y');
        $month = date('m');
        $folder = "attendances/{$year}/{$month}";

        // 1. Jika dikirim via file upload kamera langsung
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            try {
                return $file->store($folder, 'is3');
            } catch (\Throwable $e) {
                return $file->store($folder, 'public');
            }
        }

        // 2. Jika dikirim via base64 data URL (Webcam / Live camera canvas)
        $photoInput = $request->input('photo');
        if (!empty($photoInput) && is_string($photoInput)) {
            if (preg_match('/^data:image\/(\w+);base64,/', $photoInput, $type)) {
                $photoData = substr($photoInput, strpos($photoInput, ',') + 1);
                $ext = strtolower($type[1]);
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $ext = 'jpg';
                }
            } else {
                $photoData = $photoInput;
                $ext = 'jpg';
            }

            $decoded = base64_decode($photoData);
            if ($decoded !== false && strlen($decoded) > 100) {
                $fileName = $prefix . '_' . $employeeId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $relativePath = $folder . '/' . $fileName;

                try {
                    \Illuminate\Support\Facades\Storage::disk('is3')->put($relativePath, $decoded);
                    return $relativePath;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Storage::disk('public')->put($relativePath, $decoded);
                    return $relativePath;
                }
            }
        }

        return null;
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
            'photo'     => 'required',
        ], [
            'photo.required' => 'Foto selfie presensi masuk wajib diambil langsung dari kamera HP.',
        ]);

        $photoPath = $this->handleAttendancePhoto($request, 'check_in', $employee->id);
        if (!$photoPath) {
            return response()->json([
                'success' => false,
                'message' => 'Foto selfie presensi masuk tidak valid. Silakan ambil foto ulang dari kamera HP.',
            ], 422);
        }

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $lat = $request->filled('latitude') ? (float) $request->input('latitude') : null;
        $lng = $request->filled('longitude') ? (float) $request->input('longitude') : null;

        $shift = $this->getTodayShift($employee);
        $shiftStart = Carbon::parse($today . ' ' . $shift['start_time']);
        $lateLimitTime = !empty($shift['late_tolerance_time']) ? $shift['late_tolerance_time'] : '08:30';
        $toleranceLimit = Carbon::parse($today . ' ' . $lateLimitTime);

        // Terlambat jika melewati batas jam toleransi keterlambatan (misal 08:30)
        $isLate = $now->gt($toleranceLimit);

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

        // Kalkulasi jarak GPS terhadap titik kantor & radius
        $officeSetting = AttendanceSetting::getSettingForEmployee($employee);
        $distance = null;
        if ($officeSetting && $lat !== null && $lng !== null && $officeSetting->latitude !== null && $officeSetting->longitude !== null) {
            $distance = $this->calculateDistance($lat, $lng, (float) $officeSetting->latitude, (float) $officeSetting->longitude);
        }

        // Validasi pembatasan radius presensi jika tidak mengizinkan presensi di luar radius
        if ($officeSetting && !$officeSetting->allow_outside_radius && $distance !== null && $distance > $officeSetting->radius_meters) {
            return response()->json([
                'success' => false,
                'message' => "Posisi Anda berada di luar radius presensi ({$distance} meter dari {$officeSetting->office_name}, batas maksimal {$officeSetting->radius_meters} meter).",
            ], 422);
        }

        $attendance->check_in_time = $now->toTimeString();
        $attendance->check_in_lat = $lat;
        $attendance->check_in_lng = $lng;
        $attendance->check_in_distance = $distance ?? 0;
        $attendance->check_in_status = $isLate ? 'late' : 'on_time';
        $attendance->check_in_notes = $request->input('notes');
        if (Schema::hasColumn('attendances', 'check_in_photo')) {
            $attendance->check_in_photo = $photoPath;
        }
        $attendance->status = $isLate ? 'late' : 'present';
        $attendance->save();

        $statusLabel = $isLate ? 'Terlambat' : 'Tepat Waktu';
        $toleranceNote = (!$isLate && $now->gt($shiftStart)) ? " (Dispensasi toleransi s/d {$lateLimitTime})" : "";

        return response()->json([
            'success'  => true,
            'message'  => "Presensi masuk berhasil dicatat pada pukul {$now->format('H:i:s')} ({$statusLabel}){$toleranceNote}.",
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
            'photo'     => 'required',
        ], [
            'photo.required' => 'Foto selfie kepulangan wajib diambil langsung dari kamera HP.',
        ]);

        $photoPath = $this->handleAttendancePhoto($request, 'check_out', $employee->id);
        if (!$photoPath) {
            return response()->json([
                'success' => false,
                'message' => 'Foto selfie kepulangan tidak valid. Silakan ambil foto ulang dari kamera HP.',
            ], 422);
        }

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

        // Kalkulasi jarak GPS terhadap titik kantor & radius
        $officeSetting = AttendanceSetting::getSettingForEmployee($employee);
        $distance = null;
        if ($officeSetting && $lat !== null && $lng !== null && $officeSetting->latitude !== null && $officeSetting->longitude !== null) {
            $distance = $this->calculateDistance($lat, $lng, (float) $officeSetting->latitude, (float) $officeSetting->longitude);
        }

        // Validasi pembatasan radius presensi jika tidak mengizinkan presensi di luar radius
        if ($officeSetting && !$officeSetting->allow_outside_radius && $distance !== null && $distance > $officeSetting->radius_meters) {
            return response()->json([
                'success' => false,
                'message' => "Posisi Anda berada di luar radius presensi ({$distance} meter dari {$officeSetting->office_name}, batas maksimal {$officeSetting->radius_meters} meter).",
            ], 422);
        }

        $attendance->check_out_time = $now->toTimeString();
        $attendance->check_out_lat = $lat;
        $attendance->check_out_lng = $lng;
        $attendance->check_out_distance = $distance ?? 0;
        $attendance->check_out_status = $isEarly ? 'early_leave' : 'normal';
        $attendance->check_out_notes = $request->input('notes');
        if (Schema::hasColumn('attendances', 'check_out_photo')) {
            $attendance->check_out_photo = $photoPath;
        }
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
        $officeSetting = AttendanceSetting::getSettingForEmployee($employee);

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

