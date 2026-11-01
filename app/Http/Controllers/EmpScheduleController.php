<?php

namespace App\Http\Controllers;

use App\Models\EmpData;
use App\Models\EmpSchedule;
use App\Models\Project;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EmpScheduleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmpData::with(['jabatan', 'project', 'company', 'schedules.workSchedule'])
                ->orderBy('name', 'asc');

            if ($request->filled('project_id')) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->filled('schedule_id')) {
                $query->whereHas('schedules', function ($q) use ($request) {
                    $q->where('work_schedule_id', $request->schedule_id);
                });
            }

            $defaultSchedule = WorkSchedule::getDefault();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" name="emp_ids[]" value="' . $row->id . '" class="emp-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">';
                })
                ->addColumn('employee_info', function ($row) {
                    $jabatan = $row->jabatan ? $row->jabatan->name : '-';
                    $nik = $row->nik_karyawan ?: '-';
                    return '<div>
                        <div class="font-bold text-gray-900">' . e($row->name) . '</div>
                        <div class="text-[11px] text-gray-500 font-mono">NIK: ' . e($nik) . ' • ' . e($jabatan) . '</div>
                    </div>';
                })
                ->addColumn('project_name', function ($row) {
                    return $row->project ? e($row->project->name) : '-';
                })
                ->addColumn('current_schedule', function ($row) use ($defaultSchedule) {
                    // Cek jadwal aktif terbaru
                    $activeSchedule = $row->schedules()
                        ->with('workSchedule')
                        ->where('effective_date', '<=', Carbon::today())
                        ->where(function ($q) {
                            $q->whereNull('end_date')->orWhere('end_date', '>=', Carbon::today());
                        })
                        ->latest('effective_date')
                        ->first();

                    if ($activeSchedule && $activeSchedule->workSchedule) {
                        return '<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                            <i class="fa-solid fa-clock text-[10px]"></i> ' . e($activeSchedule->workSchedule->name) . '
                        </div>'
                        . '<div class="text-[10px] text-gray-400 mt-0.5">Sejak ' . $activeSchedule->effective_date->format('d/m/Y') . '</div>';
                    }

                    if ($defaultSchedule) {
                        return '<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200" title="Menggunakan jadwal default sistem">
                            <i class="fa-solid fa-shield text-[10px]"></i> ' . e($defaultSchedule->name) . ' (Default)
                        </div>';
                    }

                    return '<span class="text-xs text-red-500 font-medium">Belum Diatur</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<button type="button" onclick="openAssignSingleModal(' . $row->id . ', \'' . addslashes($row->name) . '\')"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-xs font-semibold transition">
                        <i class="fa-solid fa-calendar-check text-xs"></i> Atur Jadwal
                    </button>';
                })
                ->rawColumns(['checkbox', 'employee_info', 'project_name', 'current_schedule', 'action'])
                ->make(true);
        }

        $schedules = WorkSchedule::pluck('name', 'id');
        $projects = Project::pluck('name', 'id');

        return view('emp_schedules.index', compact('schedules', 'projects'));
    }

    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'emp_ids'          => 'required|array|min:1',
            'emp_ids.*'        => 'exists:emp_data,id',
            'work_schedule_id' => 'required|exists:work_schedules,id',
            'effective_date'   => 'required|date',
            'end_date'         => 'nullable|date|after_or_equal:effective_date',
            'notes'            => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $count = 0;
            foreach ($validated['emp_ids'] as $empId) {
                // Tutup jadwal lama jika ada
                EmpSchedule::where('emp_id', $empId)
                    ->whereNull('end_date')
                    ->where('effective_date', '<', $validated['effective_date'])
                    ->update([
                        'end_date' => Carbon::parse($validated['effective_date'])->subDay()->format('Y-m-d')
                    ]);

                EmpSchedule::create([
                    'emp_id'           => $empId,
                    'work_schedule_id' => $validated['work_schedule_id'],
                    'effective_date'   => $validated['effective_date'],
                    'end_date'         => $validated['end_date'] ?? null,
                    'notes'            => $validated['notes'] ?? 'Penugasan Jadwal Kerja',
                ]);
                $count++;
            }

            DB::commit();

            return redirect()->route('emp-schedules.index')
                ->with('success', "Berhasil menugaskan jadwal kerja kepada {$count} karyawan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal menugaskan jadwal kerja: ' . $e->getMessage()]);
        }
    }
}

