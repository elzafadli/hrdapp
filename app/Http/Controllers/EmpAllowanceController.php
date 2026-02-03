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
            $data = EmpData::with('jabatan')->select('emp_data.*');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('jabatan_name', function ($row) {
                    return $row->jabatan ? $row->jabatan->jabatan : '-';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('emp-allowance.edit', $row->id) . '" class="text-indigo-600 hover:text-indigo-900">Manage Allowances</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('emp_allowance.index');
    }

    public function edit(EmpData $empAllowance)
    {
        // $empAllowance is actually the EmpData model (employee) because route model binding uses the parameter name
        // But since we defined resource route as 'emp-allowance', the parameter is 'emp_allowance'
        $employee = $empAllowance;

        $components = PayrollComponent::where('type', 'allowance')->get();
        $allowances = $employee->allowances->pluck('value', 'payroll_component_id')->toArray();

        return view('emp_allowance.edit', compact('employee', 'components', 'allowances'));
    }

    public function update(Request $request, EmpData $empAllowance)
    {
        $employee = $empAllowance;

        $request->validate([
            'allowances' => 'array',
            'allowances.*' => 'nullable|numeric',
        ]);

        $allowancesData = $request->input('allowances', []);

        foreach ($allowancesData as $componentId => $value) {
            if ($value !== null) {
                EmpAllowance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'payroll_component_id' => $componentId,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            } else {
                // Optional: Delete if value is null? Or just leave it? 
                // Creating assumption: if empty, maybe remove it?
                // For now let's just ignore nulls or handle them as 0 if needed, but requirements said "update all"
                // Let's stick to updateOrCreate if we have data.
                // If the user clears a field, they might expect it to be deleted.
                // Let's handle delete if value is empty string or null?
                // But validation says numeric.
                // Let's assume if it is present in the request we update it.
            }
        }

        return redirect()->route('emp-allowance.index')->with('success', 'Allowances updated successfully.');
    }
}
