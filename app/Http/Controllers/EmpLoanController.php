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

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($row) {
                    return $row->employee ? $row->employee->name : '-';
                })
                ->addColumn('formatted_amount', function ($row) {
                    return number_format($row->amount, 2);
                })
                ->addColumn('formatted_installment', function ($row) {
                    return number_format($row->installment_amount, 2);
                })
                ->addColumn('start_date', function ($row) {
                    return $row->start_date ? $row->start_date->format('Y-m-d') : '-';
                })
                ->addColumn('end_date', function ($row) {
                    return $row->end_date ? $row->end_date->format('Y-m-d') : '-';
                })
                ->addColumn('status', function ($row) {
                    return view('components.status-badge', ['status' => $row->status])->render();
                })
                ->addColumn('action', function ($row) {
                    return view('components.datatable-actions', [
                        'editRoute' => route('emp-loans.edit', $row->id),
                        'deleteRoute' => route('emp-loans.destroy', $row->id),
                    ])->render();
                })
                ->rawColumns(['status', 'action'])
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
            'duration' => 'required|integer|min:1',
            'installment_amount' => 'nullable|numeric|min:0',
            'loan_date' => 'required|date',
            'start_month' => 'required|integer|between:1,12',
            'start_year' => 'required|integer|min:2020|max:2030',
            'description' => 'nullable|string',
            'status' => 'required|in:open,closed',
        ]);

        try {
            \DB::beginTransaction();

            $data = $request->all();

            // Convert month/year to dates
            // Start date: first day of the month
            $data['start_date'] = sprintf('%04d-%02d-01', $data['start_year'], $data['start_month']);

            // Auto-calculate end date based on duration
            if (!empty($data['duration'])) {
                $startDate = \Carbon\Carbon::parse($data['start_date']);
                $endDate = $startDate->copy()->addMonths((int)$data['duration'])->subDay()->endOfMonth();
                $data['end_date'] = $endDate->format('Y-m-d');
            } else {
                $data['end_date'] = null;
            }

            // Auto-calculate installment_amount if duration provided
            if (isset($data['amount']) && isset($data['duration']) && $data['duration'] > 0) {
                $data['installment_amount'] = $data['amount'] / $data['duration'];
            }

            EmpLoan::create($data);

            \DB::commit();

            return redirect()->route('emp-loans.index', ['emp_id' => $request->emp_id])
                ->with('success', 'Loan created successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create loan: ' . $e->getMessage()]);
        }
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
            'duration' => 'required|integer|min:1',
            'installment_amount' => 'nullable|numeric|min:0',
            'loan_date' => 'required|date',
            'start_month' => 'required|integer|between:1,12',
            'start_year' => 'required|integer|min:2020|max:2030',
            'description' => 'nullable|string',
            'status' => 'required|in:open,closed',
        ]);

        try {
            \DB::beginTransaction();

            $data = $request->all();

            // Convert month/year to dates
            // Start date: first day of the month
            $data['start_date'] = sprintf('%04d-%02d-01', $data['start_year'], $data['start_month']);

            // Auto-calculate end date based on duration
            if (!empty($data['duration'])) {
                $startDate = \Carbon\Carbon::parse($data['start_date']);
                $endDate = $startDate->copy()->addMonths((int)$data['duration'])->subDay()->endOfMonth();
                $data['end_date'] = $endDate->format('Y-m-d');
            } else {
                $data['end_date'] = null;
            }

            // Auto-calculate installment_amount if duration provided
            if (isset($data['amount']) && isset($data['duration']) && $data['duration'] > 0) {
                $data['installment_amount'] = $data['amount'] / $data['duration'];
            }

            $empLoan->update($data);

            \DB::commit();

            return redirect()->route('emp-loans.index', ['emp_id' => $empLoan->emp_id])
                ->with('success', 'Loan updated successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update loan: ' . $e->getMessage()]);
        }
    }

    public function destroy(EmpLoan $empLoan)
    {
        $empId = $empLoan->emp_id;
        $empLoan->delete();

        return redirect()->route('emp-loans.index', ['emp_id' => $empId])
            ->with('success', 'Loan deleted successfully.');
    }
}
