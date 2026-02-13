<?php

namespace App\Http\Controllers;

use App\Models\EmpData;
use App\Models\PayrollComponent;
use App\Models\PayrollSettingDetail;
use App\Models\PotonganKaryawanDetail;
use App\Models\PotonganKaryawan;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PotonganKaryawanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PotonganKaryawan::query();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('period', function ($row) {
                    return \Carbon\Carbon::createFromDate($row->year, $row->month, 1)->format('F Y');
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('potongan-karyawan.edit', $row->id) . '" class="text-indigo-600 hover:text-indigo-900">Manage Deductions</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('potongan_karyawan.index');
    }

    public function create()
    {
        $employees = EmpData::with('jabatan')->get();
        $components = PayrollSettingDetail::with('component')
            ->where('is_variable', true)
            ->whereHas('component', function($q) {
                $q->where('type', 'deduction');
            })
            ->get()
            ->pluck('component')
            ->filter();
        $existingDeductions = collect();

        return view('potongan_karyawan.form', compact('employees', 'components', 'existingDeductions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'deductions' => 'array',
            'deductions.*' => 'nullable|numeric',
        ]);

        // Create header
        $header = PotonganKaryawan::create([
            'year' => $request->year,
            'month' => $request->month,
        ]);

        // Create new details
        $deductionsData = $request->input('deductions', []);

        foreach ($deductionsData as $key => $value) {
            if ($value !== null && $value != '') {
                // Parse key format: employeeId_componentId
                [$employeeId, $componentId] = explode('_', $key);

                PotonganKaryawanDetail::create([
                    'header_id' => $header->id,
                    'employee_id' => $employeeId,
                    'payroll_component_id' => $componentId,
                    'value' => $value,
                ]);
            }
        }

        return redirect()->route('potongan-karyawan.index')->with('success', 'Deductions created successfully.');
    }

    public function edit(PotonganKaryawan $potonganKaryawan)
    {
        $header = $potonganKaryawan;
        $employees = EmpData::with('jabatan')->get();
        $components = PayrollSettingDetail::with('component')
            ->where('is_variable', true)
            ->whereHas('component', function($q) {
                $q->where('type', 'deduction');
            })
            ->get()
            ->pluck('component')
            ->filter();

        // Get existing deductions
        $existingDeductions = PotonganKaryawanDetail::where('header_id', $header->id)
            ->get()
            ->map(function ($detail) {
                return [
                    'employee_id' => $detail->employee_id,
                    'component_id' => $detail->payroll_component_id,
                    'value' => $detail->value,
                ];
            });

        return view('potongan_karyawan.form', compact('header', 'employees', 'components', 'existingDeductions'));
    }

    public function update(Request $request, PotonganKaryawan $potonganKaryawan)
    {
        $header = $potonganKaryawan;

        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'deductions' => 'array',
            'deductions.*' => 'nullable|numeric',
        ]);

        // Update header
        $header->update([
            'year' => $request->year,
            'month' => $request->month,
        ]);

        // Delete existing details for this header
        PotonganKaryawanDetail::where('header_id', $header->id)->delete();

        // Create new details
        $deductionsData = $request->input('deductions', []);

        foreach ($deductionsData as $key => $value) {
            if ($value !== null && $value != '') {
                // Parse key format: employeeId_componentId
                [$employeeId, $componentId] = explode('_', $key);

                PotonganKaryawanDetail::create([
                    'header_id' => $header->id,
                    'employee_id' => $employeeId,
                    'payroll_component_id' => $componentId,
                    'value' => $value,
                ]);
            }
        }

        return redirect()->route('potongan-karyawan.index')->with('success', 'Deductions updated successfully.');
    }

    public function destroy(PotonganKaryawan $potonganKaryawan)
    {
        $potonganKaryawan->delete();
        return redirect()->route('potongan-karyawan.index')->with('success', 'Deductions deleted successfully.');
    }
}
