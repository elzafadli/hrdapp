<?php

namespace App\Http\Controllers;

use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkScheduleController extends Controller
{
    public function index()
    {
        $schedules = WorkSchedule::with(['days', 'employeeSchedules'])->get();

        return view('work_schedules.index', compact('schedules'));
    }

    public function create()
    {
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        return view('work_schedules.form', [
            'schedule' => new WorkSchedule(),
            'dayNames' => $dayNames,
            'scheduleDays' => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:work_schedules,code',
            'is_default'  => 'nullable|boolean',
            'description' => 'nullable|string',
            'days'        => 'required|array',
            'days.*.is_work_day' => 'nullable|boolean',
            'days.*.start_time'  => 'nullable|string',
            'days.*.end_time'    => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $isDefault = $request->boolean('is_default');
            if ($isDefault) {
                WorkSchedule::where('is_default', true)->update(['is_default' => false]);
            }

            $schedule = WorkSchedule::create([
                'name'        => $validated['name'],
                'code'        => strtoupper($validated['code']),
                'is_default'  => $isDefault,
                'description' => $validated['description'] ?? null,
            ]);

            $dayNames = [
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
                7 => 'Minggu',
            ];

            foreach ($dayNames as $dayNumber => $dayName) {
                $dayData = $request->input("days.{$dayNumber}", []);
                $isWorkDay = !empty($dayData['is_work_day']);

                WorkScheduleDay::create([
                    'work_schedule_id' => $schedule->id,
                    'day_of_week'      => $dayNumber,
                    'day_name'         => $dayName,
                    'is_work_day'      => $isWorkDay,
                    'start_time'       => $isWorkDay ? ($dayData['start_time'] ?? '08:00:00') : null,
                    'end_time'         => $isWorkDay ? ($dayData['end_time'] ?? '17:00:00') : null,
                ]);
            }

            DB::commit();

            return redirect()->route('work-schedules.index')
                ->with('success', 'Jadwal kerja berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['error' => 'Gagal menyimpan jadwal kerja: ' . $e->getMessage()]);
        }
    }

    public function edit(WorkSchedule $workSchedule)
    {
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $scheduleDays = $workSchedule->days->keyBy('day_of_week');

        return view('work_schedules.form', [
            'schedule'     => $workSchedule,
            'dayNames'     => $dayNames,
            'scheduleDays' => $scheduleDays,
        ]);
    }

    public function update(Request $request, WorkSchedule $workSchedule)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:work_schedules,code,' . $workSchedule->id,
            'is_default'  => 'nullable|boolean',
            'description' => 'nullable|string',
            'days'        => 'required|array',
            'days.*.is_work_day' => 'nullable|boolean',
            'days.*.start_time'  => 'nullable|string',
            'days.*.end_time'    => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $isDefault = $request->boolean('is_default');
            if ($isDefault) {
                WorkSchedule::where('id', '!=', $workSchedule->id)->update(['is_default' => false]);
            }

            $workSchedule->update([
                'name'        => $validated['name'],
                'code'        => strtoupper($validated['code']),
                'is_default'  => $isDefault,
                'description' => $validated['description'] ?? null,
            ]);

            $dayNames = [
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
                7 => 'Minggu',
            ];

            foreach ($dayNames as $dayNumber => $dayName) {
                $dayData = $request->input("days.{$dayNumber}", []);
                $isWorkDay = !empty($dayData['is_work_day']);

                WorkScheduleDay::updateOrCreate(
                    [
                        'work_schedule_id' => $workSchedule->id,
                        'day_of_week'      => $dayNumber,
                    ],
                    [
                        'day_name'    => $dayName,
                        'is_work_day' => $isWorkDay,
                        'start_time'  => $isWorkDay ? ($dayData['start_time'] ?? '08:00:00') : null,
                        'end_time'    => $isWorkDay ? ($dayData['end_time'] ?? '17:00:00') : null,
                    ]
                );
            }

            DB::commit();

            return redirect()->route('work-schedules.index')
                ->with('success', 'Jadwal kerja berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['error' => 'Gagal memperbarui jadwal kerja: ' . $e->getMessage()]);
        }
    }

    public function destroy(WorkSchedule $workSchedule)
    {
        if ($workSchedule->is_default) {
            return redirect()->back()->withErrors(['error' => 'Jadwal kerja default tidak dapat dihapus. Silakan tentukan jadwal default lainnya terlebih dahulu.']);
        }

        if ($workSchedule->employeeSchedules()->exists()) {
            return redirect()->back()->withErrors(['error' => 'Jadwal kerja sedang digunakan oleh karyawan. Lepaskan penugasan karyawan terlebih dahulu.']);
        }

        $workSchedule->days()->delete();
        $workSchedule->delete();

        return redirect()->route('work-schedules.index')
            ->with('success', 'Jadwal kerja berhasil dihapus.');
    }

    public function setDefault(WorkSchedule $workSchedule)
    {
        WorkSchedule::where('is_default', true)->update(['is_default' => false]);
        $workSchedule->update(['is_default' => true]);

        return redirect()->route('work-schedules.index')
            ->with('success', "Jadwal '{$workSchedule->name}' berhasil dijadikan sebagai default.");
    }
}

