<?php

namespace App\Http\Controllers;

use App\Models\EmpLoan;
use App\Models\EmpData;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class EmpLoanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmpLoan::with(['employee.project', 'employee.jabatan', 'budget', 'project']);

            if ($request->filled('emp_id')) {
                $query->where('emp_id', $request->emp_id);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($row) {
                    $name = $row->employee ? e($row->employee->name) : '-';
                    $jabatan = $row->employee && $row->employee->jabatan ? e($row->employee->jabatan->jabatan) : '';
                    return '<div class="font-bold text-slate-900 whitespace-nowrap leading-tight">' . $name . '</div>'
                        . ($jabatan ? '<div class="text-[11px] text-slate-500 leading-tight mt-0.5">' . $jabatan . '</div>' : '');
                })
                ->addColumn('project_name', function ($row) {
                    if ($row->project) {
                        return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 whitespace-nowrap">' . e($row->project->name) . '</span>';
                    }
                    if ($row->employee && $row->employee->project) {
                        return '<span class="text-slate-500 text-xs italic whitespace-nowrap">' . e($row->employee->project->name) . '</span>';
                    }
                    return '<span class="text-slate-400 text-xs">-</span>';
                })
                ->addColumn('formatted_amount', function ($row) {
                    return '<span class="font-bold text-slate-900 font-mono text-xs whitespace-nowrap">Rp ' . number_format($row->amount, 0, ',', '.') . '</span>';
                })
                ->addColumn('formatted_installment', function ($row) {
                    return '<span class="font-semibold text-slate-700 font-mono text-xs whitespace-nowrap">Rp ' . number_format($row->installment_amount, 0, ',', '.') . '</span>';
                })
                ->addColumn('paid_amount', function ($row) {
                    $paid = $row->getPaidAmount();
                    return '<span class="font-semibold text-emerald-600 font-mono text-xs whitespace-nowrap">Rp ' . number_format($paid, 0, ',', '.') . '</span>';
                })
                ->addColumn('remaining_balance', function ($row) {
                    $paid = $row->getPaidAmount();
                    $remaining = $row->remaining_balance;
                    $percent = $row->amount > 0 ? min(100, round(($paid / $row->amount) * 100)) : 100;

                    if ($remaining <= 0 || $row->status === 'closed') {
                        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 whitespace-nowrap"><i class="fa-solid fa-check text-[10px]"></i> Lunas</span>';
                    }

                    return '<div class="min-w-[125px]">
                        <div class="flex items-center justify-between text-xs font-mono">
                            <span class="font-bold text-amber-700 whitespace-nowrap">Rp ' . number_format($remaining, 0, ',', '.') . '</span>
                            <span class="text-[10px] text-slate-400 font-normal">' . $percent . '%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                            <div class="bg-indigo-600 h-1.5 rounded-full" style="width: ' . $percent . '%"></div>
                        </div>
                        <div class="text-[10px] text-amber-600 font-semibold mt-0.5 whitespace-nowrap">Belum Lunas</div>
                    </div>';
                })
                ->addColumn('period', function ($row) {
                    $start = $row->start_date ? $row->start_date->translatedFormat('d M Y') : '-';
                    $end = $row->end_date ? $row->end_date->translatedFormat('d M Y') : '-';
                    return '<div class="text-xs font-mono whitespace-nowrap text-slate-700">' . $start . '</div>'
                        . '<div class="text-[11px] font-mono whitespace-nowrap text-slate-400">s/d ' . $end . '</div>';
                })
                ->addColumn('attachment_badge', function ($row) {
                    if (!empty($row->attachment)) {
                        return '<a href="' . route('emp-loans.download-attachment', $row->id) . '" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition whitespace-nowrap" title="Unduh / Lihat Lampiran">
                            <i class="fa-solid fa-paperclip text-[10px]"></i> Lampiran
                        </a>';
                    }
                    return '<span class="text-slate-400 text-xs italic whitespace-nowrap">Tidak ada</span>';
                })
                ->addColumn('status', function ($row) {
                    return view('components.status-badge', ['status' => $row->status])->render();
                })
                ->addColumn('budget_reference', function ($row) {
                    if (!empty($row->budget_reference_number)) {
                        return '<div class="inline-flex items-center gap-1.5 flex-nowrap whitespace-nowrap">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Budget ID: #' . $row->budget_id . '">
                                <i class="fa-solid fa-receipt text-[10px]"></i> ' . e($row->budget_reference_number) . '
                            </span>
                        </div>';
                    }
                    return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] text-slate-400 bg-slate-50 border border-slate-200 whitespace-nowrap">
                        <i class="fa-regular fa-clock text-[10px]"></i> Belum Diposting
                    </span>';
                })
                ->addColumn('action', function ($row) {
                    $csrf = csrf_field();
                    $methodDelete = method_field('DELETE');

                    $detailBtn = '<a href="' . route('emp-loans.show', $row->id) . '"
                        class="inline-flex items-center p-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-xs font-semibold shadow-2xs transition mr-1"
                        title="Detail Pinjaman">
                        <i class="fa-solid fa-eye text-xs"></i>
                    </a>';

                    $budgetAction = '';
                    if (empty($row->budget_id)) {
                        $budgetAction = '<form action="' . route('emp-loans.post-to-budget', $row->id) . '" method="POST" class="inline-block" onsubmit="return confirm(\'Posting pinjaman ini ke Budgeting dengan status Draft?\');">
                            ' . $csrf . '
                            <button type="submit"
                                class="inline-flex items-center p-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-xs font-semibold shadow-2xs transition mr-1"
                                title="Post ke Budgeting (Draft)">
                                <i class="fa-solid fa-file-invoice-dollar text-xs"></i>
                            </button>
                        </form>';
                    } else {
                        $budgetAction = '<span class="inline-flex items-center p-1.5 bg-emerald-50 text-emerald-700 border border-emerald-300 rounded-md text-xs font-semibold mr-1" title="Sudah diposting ke Budgeting: ' . e($row->budget_reference_number) . '">
                            <i class="fa-solid fa-check-double text-xs"></i>
                        </span>';
                    }

                    $editBtn = '<a href="' . route('emp-loans.edit', $row->id) . '"
                        class="inline-flex items-center p-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-md text-xs font-semibold shadow-2xs transition mr-1"
                        title="Edit Pinjaman">
                        <i class="fa-solid fa-pen text-xs"></i>
                    </a>';

                    $deleteBtn = '<form action="' . route('emp-loans.destroy', $row->id) . '" method="POST" class="inline-block" onsubmit="return confirm(\'Apakah Anda yakin ingin menghapus data pinjaman ini?\');">
                        ' . $csrf . '
                        ' . $methodDelete . '
                        <button type="submit"
                            class="inline-flex items-center p-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-md text-xs font-semibold shadow-2xs transition"
                            title="Hapus Pinjaman">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                    </form>';

                    return '<div class="flex items-center justify-center flex-nowrap whitespace-nowrap">' . $detailBtn . $budgetAction . $editBtn . $deleteBtn . '</div>';
                })
                ->rawColumns(['employee_name', 'project_name', 'formatted_amount', 'formatted_installment', 'paid_amount', 'remaining_balance', 'period', 'status', 'budget_reference', 'attachment_badge', 'action'])
                ->make(true);
        }

        $employee = null;
        if ($request->has('emp_id')) {
            $employee = EmpData::find($request->emp_id);
        }

        $statsQuery = EmpLoan::query();
        if ($request->filled('emp_id')) {
            $statsQuery->where('emp_id', $request->emp_id);
        }
        $loans = $statsQuery->get();
        $totalLoansCount = $loans->count();
        $openLoansCount = $loans->where('status', 'open')->count();
        $closedLoansCount = $loans->where('status', 'closed')->count();
        $totalLoanAmount = (float) $loans->sum('amount');
        $totalPaidAmount = 0.0;
        $totalRemainingAmount = 0.0;

        foreach ($loans as $l) {
            $paid = $l->getPaidAmount();
            $totalPaidAmount += $paid;
            $totalRemainingAmount += $l->remaining_balance;
        }

        $stats = [
            'total_count'      => $totalLoansCount,
            'open_count'       => $openLoansCount,
            'closed_count'     => $closedLoansCount,
            'total_amount'     => $totalLoanAmount,
            'paid_amount'      => $totalPaidAmount,
            'remaining_amount' => $totalRemainingAmount,
        ];

        return view('emp_loans.index', compact('employee', 'stats'));
    }

    public function create(Request $request)
    {
        $employees = EmpData::with(['jabatan', 'project'])->orderBy('name')->get();
        $employeeOptions = $employees->pluck('name', 'id');
        $employeeProjects = $employees->pluck('project_id', 'id');
        $employeeDetails = $employees->mapWithKeys(function ($emp) {
            return [
                $emp->id => [
                    'id'          => $emp->id,
                    'name'        => $emp->name,
                    'project_id'  => $emp->project_id,
                    'project_name'=> $emp->project ? ($emp->project->name . ($emp->project->no_project ? ' (' . $emp->project->no_project . ')' : '')) : '-',
                    'jabatan'     => $emp->jabatan ? $emp->jabatan->jabatan : '-',
                ]
            ];
        });

        $projects = Project::orderBy('name')->get()->mapWithKeys(function ($p) {
            $label = $p->name . ($p->no_project ? ' (' . $p->no_project . ')' : '');
            return [$p->id => $label];
        });

        $selectedEmpId = $request->get('emp_id');
        $selectedEmp = $selectedEmpId ? $employees->firstWhere('id', $selectedEmpId) : null;
        $defaultProjectId = $selectedEmp ? $selectedEmp->project_id : null;

        return view('emp_loans.form', [
            'empLoan'          => new EmpLoan(),
            'employees'        => $employeeOptions,
            'employeeProjects' => $employeeProjects,
            'employeeDetails'  => $employeeDetails,
            'projects'         => $projects,
            'selectedEmpId'    => $selectedEmpId,
            'defaultProjectId' => $defaultProjectId,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'emp_id' => 'required|exists:emp_data,id',
            'project_id' => 'nullable|exists:projects,id',
            'amount' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'installment_amount' => 'nullable|numeric|min:0',
            'loan_date' => 'required|date',
            'start_month' => 'required|integer|between:1,12',
            'start_year' => 'required|integer|min:2020|max:2030',
            'description' => 'nullable|string',
            'status' => 'required|in:open,closed',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:5120',
        ]);

        try {
            DB::beginTransaction();

            $data = $request->except(['attachment']);

            // Auto-fill project_id from employee data if not provided
            if (empty($data['project_id']) && !empty($data['emp_id'])) {
                $data['project_id'] = EmpData::where('id', $data['emp_id'])->value('project_id');
            }

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

            // Handle attachment file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                try {
                    $folder = 'loan_attachments/' . date('Y') . '/' . date('m');
                    $data['attachment'] = $file->store($folder, 'public');
                } catch (\Throwable $e) {
                    \Log::error('Upload loan attachment failed: ' . $e->getMessage());
                }
            }

            // Defensive check for pending migrations
            if (!Schema::hasColumn('emp_loans', 'project_id')) {
                unset($data['project_id']);
            }
            if (!Schema::hasColumn('emp_loans', 'attachment')) {
                unset($data['attachment']);
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
        $empLoan->load(['employee.jabatan', 'employee.project', 'employee.company', 'budget.items', 'project']);

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
        $employees = EmpData::with(['jabatan', 'project'])->orderBy('name')->get();
        $employeeOptions = $employees->pluck('name', 'id');
        $employeeProjects = $employees->pluck('project_id', 'id');
        $employeeDetails = $employees->mapWithKeys(function ($emp) {
            return [
                $emp->id => [
                    'id'          => $emp->id,
                    'name'        => $emp->name,
                    'project_id'  => $emp->project_id,
                    'project_name'=> $emp->project ? ($emp->project->name . ($emp->project->no_project ? ' (' . $emp->project->no_project . ')' : '')) : '-',
                    'jabatan'     => $emp->jabatan ? $emp->jabatan->jabatan : '-',
                ]
            ];
        });

        $projects = Project::orderBy('name')->get()->mapWithKeys(function ($p) {
            $label = $p->name . ($p->no_project ? ' (' . $p->no_project . ')' : '');
            return [$p->id => $label];
        });

        $selectedEmp = $employees->firstWhere('id', $empLoan->emp_id);
        $defaultProjectId = $empLoan->project_id ?: ($selectedEmp ? $selectedEmp->project_id : null);

        return view('emp_loans.form', [
            'empLoan'          => $empLoan,
            'employees'        => $employeeOptions,
            'employeeProjects' => $employeeProjects,
            'employeeDetails'  => $employeeDetails,
            'projects'         => $projects,
            'selectedEmpId'    => $empLoan->emp_id,
            'defaultProjectId' => $defaultProjectId,
        ]);
    }

    public function update(Request $request, EmpLoan $empLoan)
    {
        $request->validate([
            'emp_id' => 'required|exists:emp_data,id',
            'project_id' => 'nullable|exists:projects,id',
            'amount' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'installment_amount' => 'nullable|numeric|min:0',
            'loan_date' => 'required|date',
            'start_month' => 'required|integer|between:1,12',
            'start_year' => 'required|integer|min:2020|max:2030',
            'description' => 'nullable|string',
            'status' => 'required|in:open,closed',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:5120',
        ]);

        try {
            DB::beginTransaction();

            $data = $request->except(['attachment']);

            // Auto-fill project_id from employee data if not provided
            if (empty($data['project_id']) && !empty($data['emp_id'])) {
                $data['project_id'] = EmpData::where('id', $data['emp_id'])->value('project_id');
            }

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

            // Handle attachment file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                try {
                    if ($empLoan->attachment && Storage::disk('public')->exists($empLoan->attachment)) {
                        Storage::disk('public')->delete($empLoan->attachment);
                    }
                    $folder = 'loan_attachments/' . date('Y') . '/' . date('m');
                    $data['attachment'] = $file->store($folder, 'public');
                } catch (\Throwable $e) {
                    \Log::error('Upload loan attachment failed: ' . $e->getMessage());
                }
            }

            // Defensive check for pending migrations
            if (!Schema::hasColumn('emp_loans', 'project_id')) {
                unset($data['project_id']);
            }
            if (!Schema::hasColumn('emp_loans', 'attachment')) {
                unset($data['attachment']);
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

    public function downloadAttachment(EmpLoan $empLoan)
    {
        if (empty($empLoan->attachment) || !Storage::disk('public')->exists($empLoan->attachment)) {
            return redirect()->back()->withErrors(['error' => 'Berkas lampiran tidak ditemukan di penyimpanan server.']);
        }

        return Storage::disk('public')->download($empLoan->attachment);
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
                'project_id' => $empLoan->project_id ?? ($employee->project_id ?? null),
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
