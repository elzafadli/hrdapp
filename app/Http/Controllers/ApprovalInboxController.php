<?php

namespace App\Http\Controllers;

use App\Models\ApprovalHistory;
use App\Models\DocumentApproval;
use App\Models\EmpData;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalInboxController extends Controller
{
    /**
     * Dapatkan employee untuk user yang sedang login.
     */
    protected function getEmployee()
    {
        $user = Auth::user();
        return $user->employee
            ?? EmpData::where('user_id', $user->id)->first()
            ?? EmpData::where('name', $user->name)->first();
    }

    /**
     * Dashboard Approval Inbox — menampilkan semua pengajuan dari semua modul
     * yang perlu di-approve oleh user ini.
     */
    public function index(Request $request)
    {
        $employee = $this->getEmployee();
        $user     = Auth::user();

        // Modul filter
        $filterModule = $request->input('module', 'all');
        $filterStatus = $request->input('status', 'pending');

        // Semua approvable morph classes
        $morphClasses = [
            'leave'         => LeaveRequest::class,
            'special_leave' => LeaveRequest::class,
            'permit'        => LeaveRequest::class,
            'sick'          => LeaveRequest::class,
            'overtime'      => OvertimeRequest::class,
        ];

        // Query DocumentApproval yang jabatannya sesuai dengan jabatan user ini atau ditunjuk langsung sebagai approver (misal: pengganti)
        $query = DocumentApproval::with([
            'approvable.employee.jabatan',
            'approvable.approvals.jabatan',
            'approverEmployee',
            'jabatan',
            'approver',
        ]);

        // Filter berdasarkan jabatan user / karyawan penanggung jawab
        if ($employee) {
            if (Schema::hasColumn('document_approvals', 'approver_emp_id')) {
                $query->where(function ($q) use ($employee) {
                    $q->where('approver_emp_id', $employee->id)
                      ->orWhere(function ($sub) use ($employee) {
                          $sub->whereNull('approver_emp_id')
                              ->where('jabatan_id', $employee->jabatan_id);
                      });
                });
            } else {
                $query->where('jabatan_id', $employee->jabatan_id);
            }

            // Tidak tampilkan pengajuan diri sendiri
            $query->where(function ($q) use ($employee, $morphClasses) {
                foreach ($morphClasses as $class) {
                    $q->orWhere(function ($sub) use ($employee, $class) {
                        $sub->where('approvable_type', $class)
                            ->whereHasMorph('approvable', [$class], function ($inner) use ($employee) {
                                $inner->where('emp_id', '!=', $employee->id);
                            });
                    });
                }
            });
        }

        // Filter status
        if ($filterStatus === 'pending') {
            $query->actionablePending();
        } elseif ($filterStatus !== 'all') {
            $query->where('status', $filterStatus);
        }

        // Filter modul
        if ($filterModule !== 'all') {
            if ($filterModule === 'overtime') {
                $query->where('approvable_type', OvertimeRequest::class);
            } elseif ($filterModule === 'leave') {
                $query->where('approvable_type', LeaveRequest::class)
                      ->whereHasMorph('approvable', [LeaveRequest::class], fn ($q) => $q->where('leave_type', 'annual'));
            } elseif ($filterModule === 'special_leave') {
                $query->where('approvable_type', LeaveRequest::class)
                      ->whereHasMorph('approvable', [LeaveRequest::class], fn ($q) => $q->where('leave_type', 'special'));
            } elseif ($filterModule === 'permit') {
                $query->where('approvable_type', LeaveRequest::class)
                      ->whereHasMorph('approvable', [LeaveRequest::class], fn ($q) => $q->where('leave_type', 'permit'));
            } elseif ($filterModule === 'sick') {
                $query->where('approvable_type', LeaveRequest::class)
                      ->whereHasMorph('approvable', [LeaveRequest::class], fn ($q) => $q->where('leave_type', 'sick'));
            }
        }

        $approvals = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Hitung ringkasan statistik
        $stats = [];
        if ($employee) {
            $baseQuery = DocumentApproval::query();
            if (Schema::hasColumn('document_approvals', 'approver_emp_id')) {
                $baseQuery->where(function ($q) use ($employee) {
                    $q->where('approver_emp_id', $employee->id)
                      ->orWhere(function ($sub) use ($employee) {
                          $sub->whereNull('approver_emp_id')
                              ->where('jabatan_id', $employee->jabatan_id);
                      });
                });
            } else {
                $baseQuery->where('jabatan_id', $employee->jabatan_id);
            }

            $baseQuery->where(function ($q) use ($employee, $morphClasses) {
                    foreach ($morphClasses as $class) {
                        $q->orWhere(function ($sub) use ($employee, $class) {
                            $sub->where('approvable_type', $class)
                                ->whereHasMorph('approvable', [$class], function ($inner) use ($employee) {
                                    $inner->where('emp_id', '!=', $employee->id);
                                });
                        });
                    }
                });

            $stats['pending']  = (clone $baseQuery)->actionablePending()->count();
            $stats['approved'] = (clone $baseQuery)->where('status', 'approved')->count();
            $stats['rejected'] = (clone $baseQuery)->where('status', 'rejected')->count();
            $stats['total']    = (clone $baseQuery)->count();
        }

        return view('approval-inbox.index', compact(
            'employee', 'approvals', 'stats', 'filterModule', 'filterStatus'
        ));
    }

    /**
     * Approve satu dokumen dari inbox terpusat.
     */
    public function approve(Request $request, $approvalId)
    {
        $approval = DocumentApproval::with('approvable')->findOrFail($approvalId);
        $user     = Auth::user();

        if (!$approval->canUserApprove($user)) {
            return back()->with('error', 'Anda tidak berwenang menyetujui tahapan approval ini.');
        }

        $notes = $request->input('notes', 'Disetujui.');
        $approval->approve($user, $notes);

        return back()->with('success', 'Persetujuan berhasil diproses.');
    }

    /**
     * Reject satu dokumen dari inbox terpusat.
     */
    public function reject(Request $request, $approvalId)
    {
        $request->validate(['notes' => 'required|string|max:500']);

        $approval = DocumentApproval::with('approvable')->findOrFail($approvalId);
        $user     = Auth::user();

        if (!$approval->canUserApprove($user)) {
            return back()->with('error', 'Anda tidak berwenang menolak tahapan approval ini.');
        }

        $approval->reject($user, $request->notes);

        return back()->with('success', 'Penolakan berhasil diproses.');
    }
}

