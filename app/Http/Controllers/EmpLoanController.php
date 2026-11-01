<?php

namespace App\Http\Controllers;

use App\Models\EmpLoan;
use App\Models\EmpData;
use App\Models\Budget;
use App\Models\BudgetItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class EmpLoanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmpLoan::with(['employee', 'budget']);

            if ($request->filled('emp_id')) {
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
                ->addColumn('budget_reference', function ($row) {
                    if (!empty($row->budget_reference_number)) {
                        return '<div class="inline-flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Budget ID: #' . $row->budget_id . '">
                                <i class="fa-solid fa-receipt text-[10px]"></i> ' . e($row->budget_reference_number) . '
                            </span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-100 text-amber-800 border border-amber-200">Draft</span>
                        </div>';
                    }
                    return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs text-gray-400 bg-gray-50 border border-gray-200">
                        <i class="fa-regular fa-clock text-[10px]"></i> Belum Diposting
                    </span>';
                })
                ->addColumn('action', function ($row) {
                    $csrf = csrf_field();
                    $methodDelete = method_field('DELETE');

                    $detailBtn = '<a href="' . route('emp-loans.show', $row->id) . '"
                        class="inline-flex items-center px-2 py-1.5 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring ring-blue-300 transition ease-in-out duration-150 mr-1.5"
                        title="Detail Pinjaman">
                        <i class="fa-solid fa-eye"></i>
                    </a>';

                    $budgetAction = '';
                    if (empty($row->budget_id)) {
                        $budgetAction = '<form action="' . route('emp-loans.post-to-budget', $row->id) . '" method="POST" class="inline-block" onsubmit="return confirm(\'Posting pinjaman ini ke Budgeting dengan status Draft?\');">
                            ' . $csrf . '
                            <button type="submit"
                                class="inline-flex items-center gap-1 px-2 py-1.5 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 active:bg-emerald-800 focus:outline-none focus:ring ring-emerald-300 transition ease-in-out duration-150 mr-1.5"
                                title="Post ke Budgeting (Draft)">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                <span class="hidden sm:inline text-[10px]">Post Budget</span>
                            </button>
                        </form>';
                    } else {
                        $budgetAction = '<span class="inline-flex items-center px-2 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-300 rounded-md text-xs font-semibold mr-1.5" title="Sudah diposting ke Budgeting: ' . e($row->budget_reference_number) . '">
                            <i class="fa-solid fa-check-double text-xs"></i>
                        </span>';
                    }

                    $editBtn = '<a href="' . route('emp-loans.edit', $row->id) . '"
                        class="inline-flex items-center px-2 py-1.5 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600 active:bg-yellow-700 focus:outline-none focus:ring ring-yellow-300 transition ease-in-out duration-150 mr-1.5"
                        title="Edit Pinjaman">
                        <i class="fa-solid fa-pen"></i>
                    </a>';

                    $deleteBtn = '<form action="' . route('emp-loans.destroy', $row->id) . '" method="POST" class="inline-block" onsubmit="return confirm(\'Apakah Anda yakin ingin menghapus data pinjaman ini?\');">
                        ' . $csrf . '
                        ' . $methodDelete . '
                        <button type="submit"
                            class="inline-flex items-center px-2 py-1.5 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:ring ring-red-300 transition ease-in-out duration-150"
                            title="Hapus Pinjaman">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>';

                    return '<div class="flex items-center flex-nowrap">' . $detailBtn . $budgetAction . $editBtn . $deleteBtn . '</div>';
                })
                ->rawColumns(['status', 'budget_reference', 'action'])
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
            DB::beginTransaction();

            $data = $request->all();

            // Convert month/year to dates
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

            $loan = EmpLoan::create($data);

            DB::commit();

            return redirect()->route('emp-loans.index', ['emp_id' => $request->emp_id])
                ->with('success', 'Loan created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create loan: ' . $e->getMessage()]);
        }
    }

    public function show(EmpLoan $empLoan)
    {
        $empLoan->load(['employee.jabatan', 'employee.project', 'employee.company', 'budget.items']);

        $budget = $empLoan->budget;

        // Generate installment schedule simulation
        $installments = [];
        if ($empLoan->duration && $empLoan->start_date) {
            $currentDate = \Carbon\Carbon::parse($empLoan->start_date);
            $remaining = (float) $empLoan->amount;
            $installmentAmount = (float) ($empLoan->installment_amount ?: ($empLoan->amount / $empLoan->duration));

            for ($i = 1; $i <= $empLoan->duration; $i++) {
                $installmentVal = min($remaining, $installmentAmount);
                if ($i === (int)$empLoan->duration) {
                    $installmentVal = $remaining; // Absorbs remainder
                }
                $remaining -= $installmentVal;

                $installments[] = [
                    'no' => $i,
                    'period' => $currentDate->copy()->translatedFormat('F Y'),
                    'due_date' => $currentDate->copy()->format('Y-m-d'),
                    'amount' => $installmentVal,
                    'remaining' => max(0, $remaining),
                ];

                $currentDate->addMonth();
            }
        }

        return view('emp_loans.show', compact('empLoan', 'budget', 'installments'));
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
            DB::beginTransaction();

            $data = $request->all();

            // Convert month/year to dates
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

            DB::commit();

            return redirect()->route('emp-loans.index', ['emp_id' => $empLoan->emp_id])
                ->with('success', 'Loan updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
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

    public function postToBudget(Request $request, EmpLoan $empLoan)
    {
        if (!Schema::hasColumn('emp_loans', 'budget_id') || !Schema::hasColumn('emp_loans', 'budget_reference_number')) {
            return redirect()->back()->withErrors([
                'error' => 'Kolom budget_id / budget_reference_number belum ada di tabel emp_loans. Harap jalankan migrasi database terlebih dahulu: php artisan migrate'
            ]);
        }

        if (!empty($empLoan->budget_id)) {
            return redirect()->back()->with('warning', 'Pinjaman ini sudah pernah diposting ke Budgeting (' . ($empLoan->budget_reference_number ?? ('ID #' . $empLoan->budget_id)) . ').');
        }

        try {
            DB::beginTransaction();

            $employee = $empLoan->employee;

            // Generate unique request number: BDG-YYYYMMDD-XXXX
            $datePrefix = date('Ymd');
            $lastBudget = DB::table('budgets')->latest('id')->first();
            $nextNum = ($lastBudget ? $lastBudget->id + 1 : 1);
            $requestNo = 'BDG-' . $datePrefix . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            while (DB::table('budgets')->where('request_no', $requestNo)->exists()) {
                $nextNum++;
                $requestNo = 'BDG-' . $datePrefix . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            }

            // Find matching loan account in accounts table if available
            $loanAccount = DB::table('accounts')
                ->where('account_description', 'like', '%Pinjaman%')
                ->orWhere('account_description', 'like', '%Kasbon%')
                ->first();
            $accountId = $loanAccount ? $loanAccount->id : null;

            $userId = auth()->id() ?? ($employee->user_id ?? 1);
            $employeeName = $employee ? $employee->name : '-';
            $description = 'Pinjaman Karyawan: ' . $employeeName . ($empLoan->description ? ' (' . $empLoan->description . ')' : '');

            // Create budget record with status 'draft'
            $budget = Budget::create([
                'request_no' => $requestNo,
                'user_id' => $userId,
                'project_id' => $employee->project_id ?? null,
                'account_from_id' => null,
                'biaya_admin_bank' => 0.00,
                'account_to' => $employeeName,
                'document_date' => $empLoan->loan_date ? $empLoan->loan_date->format('Y-m-d') : date('Y-m-d'),
                'payment_date' => $empLoan->start_date ? $empLoan->start_date->format('Y-m-d') : null,
                'description' => $description,
                'total_amount' => $empLoan->amount,
                'approved_total' => 0.00,
                'is_cash' => $empLoan->amount <= 100000 ? 1 : 0,
                'status' => 'draft',
            ]);

            // Create 1 budget item detail
            BudgetItem::create([
                'budget_id' => $budget->id,
                'account_id' => $accountId,
                'qty' => 1,
                'unit_price' => $empLoan->amount,
                'total_price' => $empLoan->amount,
                'remarks' => $description,
            ]);

            // Update emp_loans with budget_id and budget_reference_number
            $empLoan->update([
                'budget_id' => $budget->id,
                'budget_reference_number' => $requestNo,
            ]);

            DB::commit();

            return redirect()->back()->with(
                'success',
                "Pinjaman berhasil diposting ke Budgeting sebagai Draft dengan Nomor Referensi: {$requestNo}"
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors([
                'error' => 'Gagal memposting ke Budgeting: ' . $e->getMessage()
            ]);
        }
    }
}
