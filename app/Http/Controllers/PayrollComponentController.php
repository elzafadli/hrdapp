<?php

namespace App\Http\Controllers;

use App\Models\PayrollComponent;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PayrollComponentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PayrollComponent::query();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('components.datatable-actions', [
                        'editRoute' => route('payroll-components.edit', $row->id),
                        'deleteRoute' => route('payroll-components.destroy', $row->id),
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('payroll_components.index');
    }

    public function create()
    {
        $components = PayrollComponent::all();
        return view('payroll_components.form', [
            'payrollComponent' => new PayrollComponent(),
            'components' => $components
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:allowance,deduction,bpjs,other',
            'payroll_reference' => 'nullable|exists:payroll_components,id',
        ]);

        PayrollComponent::create($request->all());

        return redirect()->route('payroll-components.index')->with('success', 'Payroll Component created successfully.');
    }

    public function edit(PayrollComponent $payrollComponent)
    {
        $components = PayrollComponent::where('id', '!=', $payrollComponent->id)->get();
        return view('payroll_components.form', [
            'payrollComponent' => $payrollComponent,
            'components' => $components
        ]);
    }

    public function update(Request $request, PayrollComponent $payrollComponent)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:allowance,deduction,bpjs,other',
            'payroll_reference' => 'nullable|exists:payroll_components,id',
        ]);

        $payrollComponent->update($request->all());

        return redirect()->route('payroll-components.index')->with('success', 'Payroll Component updated successfully.');
    }

    public function destroy(PayrollComponent $payrollComponent)
    {
        $payrollComponent->delete();

        return redirect()->route('payroll-components.index')->with('success', 'Payroll Component deleted successfully.');
    }
}
