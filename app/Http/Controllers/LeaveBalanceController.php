<?php

namespace App\Http\Controllers;

use App\Models\EmpData;
use App\Models\LeaveBalance;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));

        if ($request->ajax()) {
            $query = LeaveBalance::with(['employee.jabatan', 'employee.project'])
                ->where('year', $year);

            if ($request->filled('project_id')) {
                $query->whereHas('employee', function ($q) use ($request) {
                    $q->where('project_id', $request->project_id);
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('employee_info', function ($row) {
                    $name = $row->employee ? $row->employee->name : '-';
                    $nik = $row->employee ? ($row->employee->nik_karyawan ?: '-') : '-';
                    $jabatan = $row->employee && $row->employee->jabatan ? $row->employee->jabatan->name : '-';

                    return '<div>
                        <div class="font-bold text-gray-900">' . e($name) . '</div>
                        <div class="text-[11px] text-gray-500 font-mono">NIK: ' . e($nik) . ' • ' . e($jabatan) . '</div>
                    </div>';
                })
                ->addColumn('project_name', function ($row) {
                    return $row->employee && $row->employee->project ? e($row->employee->project->name) : '-';
                })
                ->addColumn('total_leave_badge', function ($row) {
                    return '<span class="font-bold text-gray-900 text-sm">' . (float) $row->total_leave . '</span> <span class="text-[11px] text-gray-400">Hari</span>';
                })
                ->addColumn('used_leave_badge', function ($row) {
                    return '<span class="font-bold text-amber-700 text-sm">' . (float) $row->used_leave . '</span> <span class="text-[11px] text-gray-400">Hari</span>';
                })
                ->addColumn('remaining_leave_badge', function ($row) {
                    $color = $row->remaining_leave > 3 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : ($row->remaining_leave > 0 ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-red-700 bg-red-50 border-red-200');
                    return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border ' . $color . '">' . (float) $row->remaining_leave . ' Hari Tersedia</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<button type="button" onclick="editBalance(' . htmlspecialchars(json_encode([
                        'id' => $row->id,
                        'name' => $row->employee ? $row->employee->name : '-',
                        'year' => $row->year,
                        'total_leave' => (float) $row->total_leave,
                        'used_leave' => (float) $row->used_leave,
                        'remaining_leave' => (float) $row->remaining_leave,
                        'notes' => $row->notes,
                    ])) . ')"
                        class="inline-flex items-center px-2.5 py-1.5 bg-yellow-50 hover:bg-yellow-100 text-yellow-800 border border-yellow-200 rounded-lg text-xs font-semibold transition"
                        title="Sesuaikan Kuota Cuti">
                        <i class="fa-solid fa-pen text-[10px] mr-1"></i> Sesuaikan
                    </button>';
                })
                ->rawColumns(['employee_info', 'project_name', 'total_leave_badge', 'used_leave_badge', 'remaining_leave_badge', 'action'])
                ->make(true);
        }

        // Summary stats
        $totalEmployeesWithBalance = LeaveBalance::where('year', $year)->count();
        $totalRemainingDays = (float) LeaveBalance::where('year', $year)->sum('remaining_leave');
        $totalUsedDays = (float) LeaveBalance::where('year', $year)->sum('used_leave');

        $years = LeaveBalance::select('year')->distinct()->orderBy('year', 'desc')->pluck('year')->toArray();
        if (!in_array((int) date('Y'), $years)) {
            $years[] = (int) date('Y');
        }
        if (!in_array((int) date('Y') + 1, $years)) {
            $years[] = (int) date('Y') + 1;
        }
        rsort($years);

        $projects = Project::pluck('name', 'id');

        return view('leave_balances.index', compact('year', 'years', 'projects', 'totalEmployeesWithBalance', 'totalRemainingDays', 'totalUsedDays'));
    }

    public function update(Request $request, LeaveBalance $leaveBalance)
    {
        $validated = $request->validate([
            'total_leave' => 'required|numeric|min:0',
            'notes'       => 'nullable|string|max:255',
        ]);

        $totalLeave = (float) $validated['total_leave'];
        $usedLeave = (float) $leaveBalance->used_leave;
        $remainingLeave = max(0, $totalLeave - $usedLeave);

        $leaveBalance->update([
            'total_leave'     => $totalLeave,
            'remaining_leave' => $remainingLeave,
            'notes'           => $validated['notes'] ?? $leaveBalance->notes,
        ]);

        return redirect()->route('leave-balances.index', ['year' => $leaveBalance->year])
            ->with('success', 'Saldo cuti karyawan berhasil disesuaikan.');
    }

    public function generateAnnual(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $defaultQuota = (float) $request->get('default_quota', 12.0);

        try {
            DB::beginTransaction();

            $employees = EmpData::all();
            $generatedCount = 0;

            foreach ($employees as $emp) {
                $exists = LeaveBalance::where('emp_id', $emp->id)->where('year', $year)->exists();
                if (!$exists) {
                    LeaveBalance::create([
                        'emp_id'          => $emp->id,
                        'year'            => $year,
                        'total_leave'     => $defaultQuota,
                        'used_leave'      => 0.0,
                        'remaining_leave' => $defaultQuota,
                        'notes'           => "Inisialisasi kuota cuti tahunan {$year} (standar {$defaultQuota} hari)",
                    ]);
                    $generatedCount++;
                }
            }

            DB::commit();

            return redirect()->route('leave-balances.index', ['year' => $year])
                ->with('success', "Berhasil inisialisasi saldo cuti tahunan {$year} untuk {$generatedCount} karyawan baru.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal generate saldo cuti tahunan: ' . $e->getMessage()]);
        }
    }
}

