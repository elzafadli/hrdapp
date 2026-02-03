<?php

namespace App\Http\Controllers;

use App\Models\EmpBpjs;
use App\Models\EmpData;
use App\Models\PayrollComponent;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmpBpjsController extends Controller
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
                    return '<a href="' . route('emp-bpjs.edit', $row->id) . '" class="text-indigo-600 hover:text-indigo-900">Manage BPJS</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('emp_bpjs.index');
    }

    public function edit(EmpData $empBpjs)
    {
        // $empBpjs is actually the EmpData model (employee)
        $employee = $empBpjs;

        // Filter where type = 'subsidi' as requested
        $components = PayrollComponent::where('type', 'subsidi')->orderBy('name', 'desc')->get();

        $bpjsData = $employee->bpjs->pluck('value', 'payroll_component_id')->toArray();

        return view('emp_bpjs.edit', compact('employee', 'components', 'bpjsData'));
    }

    public function update(Request $request, EmpData $empBpjs)
    {
        $employee = $empBpjs;

        $request->validate([
            'bpjs' => 'array',
            'bpjs.*' => 'nullable|string',
        ]);

        $bpjsInput = $request->input('bpjs', []);

        foreach ($bpjsInput as $componentId => $value) {
            if ($value !== null) {
                EmpBpjs::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'payroll_component_id' => $componentId,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        }

        return redirect()->route('emp-bpjs.index')->with('success', 'BPJS updated successfully.');
    }
}
