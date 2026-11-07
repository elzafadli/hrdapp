<?php

namespace App\Http\Controllers;

use App\Models\EmpAllowance;
use App\Models\EmpData;
use App\Models\PayrollComponent;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmpAllowanceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = EmpData::with(['jabatan', 'project', 'allowances'])->select('emp_data.*');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('employee_info', function ($row) {
                    $name = e($row->name);
                    $email = e($row->email ?? $row->nomor_induk_karyawan ?? '-');
                    return '<div class="font-bold text-slate-900 leading-tight">' . $name . '</div>'
                        . '<div class="text-[11px] text-slate-500 font-mono mt-0.5">' . $email . '</div>';
                })
                ->addColumn('jabatan_name', function ($row) {
                    if ($row->jabatan) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">'
                            . e($row->jabatan->jabatan)
                            . '</span>';
                    }
                    return '<span class="text-slate-400 text-xs">-</span>';
                })
                ->addColumn('project_name', function ($row) {
                    if ($row->project) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">'
                            . e($row->project->name)
                            . '</span>';
                    }
                    return '<span class="text-slate-400 text-xs">-</span>';
                })
                ->addColumn('allowance_count', function ($row) {
                    $activeCount = $row->allowances->where('value', '>', 0)->count();
                    if ($activeCount > 0) {
                        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">'
                            . '<i class="fa-solid fa-list-check text-[10px]"></i> ' . $activeCount . ' Komponen'
                            . '</span>';
                    }
                    return '<span class="text-slate-400 text-xs italic">Belum diatur</span>';
                })
                ->addColumn('total_allowance', function ($row) {
                    $total = (float) $row->allowances->sum('value');
                    if ($total > 0) {
                        return '<span class="font-bold text-emerald-700 font-mono text-xs">Rp ' . number_format($total, 0, ',', '.') . '</span>';
                    }
                    return '<span class="text-slate-400 text-xs font-mono">Rp 0</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<div class="flex items-center justify-center">
                        <a href="' . route('emp-allowance.edit', $row->id) . '"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-2xs transition duration-150"
                            title="Kelola Tunjangan">
                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                            <span>Kelola Tunjangan</span>
                        </a>
                    </div>';
                })
                ->rawColumns(['employee_info', 'jabatan_name', 'project_name', 'allowance_count', 'total_allowance', 'action'])
                ->make(true);
        }

        // Ringkasan Statistik
        $allEmployees = EmpData::with('allowances')->get();
        $totalEmployees = $allEmployees->count();
        $withAllowance = $allEmployees->filter(function ($e) {
            return $e->allowances->where('value', '>', 0)->count() > 0;
        })->count();
        $totalAmount = EmpAllowance::sum('value');
        $componentsCount = PayrollComponent::where('type', 'allowance')->count();

        $stats = [
            'total_employees'          => $totalEmployees,
            'employees_with_allowance' => $withAllowance,
            'total_allowance_amount'   => (float) $totalAmount,
            'total_components'         => $componentsCount,
        ];

        return view('emp_allowance.index', compact('stats'));
    }

    public function edit(EmpData $empAllowance)
    {
        $employee = $empAllowance->loadMissing(['jabatan', 'project', 'company', 'allowances']);
        $components = PayrollComponent::where('type', 'allowance')->get();
        $allowances = $employee->allowances->pluck('value', 'payroll_component_id')->toArray();
        $totalAllowance = array_sum(array_filter($allowances));

        return view('emp_allowance.edit', compact('employee', 'components', 'allowances', 'totalAllowance'));
    }

    public function update(Request $request, EmpData $empAllowance)
    {
        $employee = $empAllowance;

        $request->validate([
            'allowances'   => 'array',
            'allowances.*' => 'nullable|numeric|min:0',
        ]);

        $allowancesData = $request->input('allowances', []);

        foreach ($allowancesData as $componentId => $value) {
            if ($value !== null && $value !== '') {
                EmpAllowance::updateOrCreate(
                    [
                        'employee_id'          => $employee->id,
                        'payroll_component_id' => $componentId,
                    ],
                    [
                        'value' => (float) $value,
                    ]
                );
            } else {
                EmpAllowance::where('employee_id', $employee->id)
                    ->where('payroll_component_id', $componentId)
                    ->delete();
            }
        }

        return redirect()->route('emp-allowance.index')->with('success', 'Data tunjangan karyawan ' . $employee->name . ' berhasil diperbarui.');
    }
}
