<?php

namespace App\Http\Controllers;

use App\Models\EmpLoan;
use App\Models\EmpData;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmpLoanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmpLoan::with('employee');

            if ($request->has('emp_id') && $request->emp_id) {
                $query->where('emp_id', $request->emp_id);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($row) {
                    return $row->employee ? $row->employee->name : '-';
                })
                ->addColumn('formatted_amount', function ($row) {
                    return number_format($row->amount, 2);
                })
                ->addColumn('action', function ($row) {
                    return view('components.datatable-actions', [
                        'editRoute' => route('emp-loans.edit', $row->id),
                        'deleteRoute' => route('emp-loans.destroy', $row->id),
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $employee = null;
        if ($request->has('emp_id')) {
            $employee = EmpData::find($request->emp_id);
        }

        return view('emp_loans.index', compact('employee'));
    }

    public function create(Request $request)
    {
        $employees = EmpData::pluck('name', 'id');
        $selectedEmpId = $request->get('emp_id');

        return view('emp_loans.form', [
            'empLoan' => new EmpLoan(),
            'employees' => $employees,
            'selectedEmpId' => $selectedEmpId
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'emp_id' => 'required|exists:emp_data,id',
            'amount' => 'required|numeric|min:0',
            'installment_amount' => 'nullable|numeric|min:0',
            'loan_date' => 'required|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'required|in:open,closed',
        ]);

        EmpLoan::create($request->all());

        return redirect()->route('emp-loans.index', ['emp_id' => $request->emp_id])
            ->with('success', 'Loan created successfully.');
    }

    public function edit(EmpLoan $empLoan)
    {
        $employees = EmpData::pluck('name', 'id');
        return view('emp_loans.form', [
            'empLoan' => $empLoan,
            'employees' => $employees,
            'selectedEmpId' => $empLoan->emp_id
        ]);
    }

    public function update(Request $request, EmpLoan $empLoan)
    {
        $request->validate([
            'emp_id' => 'required|exists:emp_data,id',
            'amount' => 'required|numeric|min:0',
            'installment_amount' => 'nullable|numeric|min:0',
            'loan_date' => 'required|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'required|in:open,closed',
        ]);

        $empLoan->update($request->all());

        return redirect()->route('emp-loans.index', ['emp_id' => $empLoan->emp_id])
            ->with('success', 'Loan updated successfully.');
    }

    public function destroy(EmpLoan $empLoan)
    {
        $empId = $empLoan->emp_id;
        $empLoan->delete();

        return redirect()->route('emp-loans.index', ['emp_id' => $empId])
            ->with('success', 'Loan deleted successfully.');
    }
}
