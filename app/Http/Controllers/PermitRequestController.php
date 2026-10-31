<?php

namespace App\Http\Controllers;

use App\Helpers\DocumentNumberHelper;
use App\Models\ApprovalHistory;
use App\Models\DocumentApproval;
use App\Models\EmpData;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestDate;
use App\Models\LeaveRequestFile;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermitRequestController extends Controller
{
    /**
     * Dapatkan employee untuk user yang sedang login.
     */
    protected function getEmployee()
    {
        $user = auth()->user();
        return $user->employee
            ?? EmpData::where('user_id', $user->id)->first()
            ?? EmpData::where('name', $user->name)->first()
            ?? EmpData::first();
    }

    /**
     * Dashboard daftar riwayat izin & inbox approval.
     */
    public function index(Request $request)
    {
        $employee = $this->getEmployee();
        $currentYear = (int) date('Y');

        $permitRequests = collect();
        $stats = [
            'total' => 0,
            'approved' => 0,
            'pending' => 0,
        ];

        if ($employee) {
            $permitRequests = LeaveRequest::with(['dates', 'files', 'approvals.approvable', 'approvals.jabatan'])
                ->where('emp_id', $employee->id)
                ->where('leave_type', 'permit')
                ->latest()
                ->paginate(10);

            $stats['total'] = LeaveRequest::where('emp_id', $employee->id)->where('leave_type', 'permit')->count();
            $stats['approved'] = LeaveRequest::where('emp_id', $employee->id)->where('leave_type', 'permit')->whereIn('status', ['approved', 'completed'])->count();
            $stats['pending'] = LeaveRequest::where('emp_id', $employee->id)->where('leave_type', 'permit')->where('status', 'pending')->count();

            // Inbox approval bagi atasan
            $user = auth()->user();
        }

        return view('ess.permit.index', compact('employee', 'permitRequests', 'currentYear', 'stats'));
    }

    /**
     * Formulir pengajuan izin baru.
     */
    public function create()
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return redirect()->route('permit.index')->with('error', 'Data karyawan Anda belum terdaftar.');
        }

        // Ambil daftar tanggal cuti/izin yang sudah pernah diajukan untuk di-disable di kalender Flatpickr
        $disabledDates = LeaveRequestDate::where('emp_id', $employee->id)
            ->whereHas('leaveRequest', function ($q) {
                $q->whereIn('status', ['pending', 'approved', 'completed']);
            })
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->toArray();

        return view('ess.permit.create', compact('employee', 'disabledDates'));
    }

    /**
     * Simpan pengajuan izin baru.
     */
    public function store(Request $request)
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return back()->with('error', 'Data karyawan tidak ditemukan.');
        }

        $request->validate([
            'is_half_day'   => 'nullable|boolean',
            'start_date'    => 'required|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'half_day_type' => 'nullable|in:first_half,second_half',
            'reason'        => 'required|string|max:1000',
            'files.*'       => 'nullable|file|max:3072', // Maks 3MB per file
        ]);

        $isHalfDay = $request->boolean('is_half_day');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date') ?? $startDate;

        // Validasi Back-Date (Ketentuan H-1)
        $minAllowedDate = Carbon::tomorrow()->toDateString();
        if ($startDate < $minAllowedDate) {
            return back()->withInput()->with('error', 'Tanggal izin tidak boleh tanggal lampau (back-date). Pengajuan paling lambat H-1 (mulai tanggal ' . Carbon::tomorrow()->format('d/m/Y') . ').');
        }

        // Ekstraksi tanggal hari kerja
        $fullDayDates = [];
        $halfDayDates = [];
        $fullDayCount = 0.0;
        $halfDayCount = 0.0;

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($employee->isWorkDay($date)) {
                if ($isHalfDay) {
                    $halfDayDates[] = $date->toDateString();
                    $halfDayCount += 0.5;
                } else {
                    $fullDayDates[] = $date->toDateString();
                    $fullDayCount += 1.0;
                }
            }
        }

        $totalDays = $fullDayCount + $halfDayCount;

        if ($totalDays <= 0) {
            return back()->withInput()->with('error', 'Tanggal yang Anda pilih seluruhnya adalah hari libur atau akhir pekan (0 hari kerja).');
        }

        // Validasi Bentrok Tanggal
        $allSelectedDates = array_merge($fullDayDates, $halfDayDates);
        $conflicts = LeaveRequestDate::checkDateConflicts(
            $employee->id,
            $allSelectedDates,
            $isHalfDay,
            $request->input('half_day_type')
        );

        if (!empty($conflicts)) {
            $conflictStr = implode(' | ', array_column($conflicts, 'message'));
            return back()->withInput()->with('error', "Tanggal pengajuan bentrok dengan permohonan Anda yang sudah ada: {$conflictStr}");
        }

        DB::beginTransaction();
        try {
            $requestNumber = DocumentNumberHelper::generate('IZ', $employee);

            $leaveRequest = LeaveRequest::create([
                'request_number'      => $requestNumber,
                'emp_id'              => $employee->id,
                'user_id'             => auth()->id(),
                'leave_type'          => 'permit',
                'full_day_start_date' => $isHalfDay ? null : $startDate,
                'full_day_end_date'   => $isHalfDay ? null : $endDate,
                'full_day_count'      => $fullDayCount,
                'half_day_start_date' => $isHalfDay ? $startDate : null,
                'half_day_end_date'   => $isHalfDay ? $endDate : null,
                'half_day_count'      => $halfDayCount,
                'half_day_type'       => $isHalfDay ? $request->input('half_day_type') : null,
                'total_days'          => $totalDays,
                'reason'              => $request->reason,
                'status'              => 'pending',
            ]);

            // Simpan rincian tanggal izin
            foreach ($fullDayDates as $date) {
                LeaveRequestDate::create([
                    'leave_request_id' => $leaveRequest->id,
                    'emp_id'           => $employee->id,
                    'date'             => $date,
                    'day_type'         => 'full_day',
                    'deduction_value'  => 1.0,
                ]);
            }

            foreach ($halfDayDates as $date) {
                LeaveRequestDate::create([
                    'leave_request_id' => $leaveRequest->id,
                    'emp_id'           => $employee->id,
                    'date'             => $date,
                    'day_type'         => 'half_day',
                    'half_day_type'    => $request->input('half_day_type'),
                    'deduction_value'  => 0.5,
                ]);
            }

            // Simpan lampiran file jika ada
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('permit_attachments', 'is3');
                    LeaveRequestFile::create([
                        'leave_request_id' => $leaveRequest->id,
                        'file_path'        => $path,
                        'file_name'        => $file->getClientOriginalName(),
                        'file_size'        => $file->getSize(),
                        'mime_type'        => $file->getClientMimeType(),
                    ]);
                }
            }

            // Inisialisasi alur approval
            $leaveRequest->initializeApproval();

            DB::commit();

            $msg = $leaveRequest->status === 'completed'
                ? 'Pengajuan izin berhasil disetujui otomatis.'
                : 'Pengajuan izin berhasil diajukan dan sedang menunggu persetujuan.';

            return redirect()->route('permit.index')->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memproses pengajuan izin: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail pengajuan izin.
     */
    public function show($id)
    {
        $leaveRequest = LeaveRequest::with([
            'employee.jabatan',
            'dates',
            'files',
            'approvals.approvable', 'approvals.jabatan',
            'approvals.approver.employee',
            'approvalHistories.user.employee',
            'approvalHistories.jabatan'
        ])
        ->where('leave_type', 'permit')
        ->findOrFail($id);

        $employee = $this->getEmployee();
        $user = auth()->user();

        $permitRequest = $leaveRequest;
        return view('ess.permit.show', compact('leaveRequest', 'permitRequest', 'employee', 'user'));
    }

    /**
     * Batalkan pengajuan izin pending oleh karyawan.
     */
    public function cancel($id)
    {
        $employee = $this->getEmployee();
        $leaveRequest = LeaveRequest::where('emp_id', $employee->id)
            ->where('leave_type', 'permit')
            ->findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan berstatus pending yang dapat dibatalkan.');
        }

        $leaveRequest->update(['status' => 'cancelled']);
        ApprovalHistory::log($leaveRequest, 'cancelled', auth()->user(), 'Dibatalkan oleh pemohon.');

        return redirect()->route('permit.index')->with('success', 'Pengajuan izin berhasil dibatalkan.');
    }

    /**
     * Tindakan Approve oleh Approver yang berwenang.
     */
    public function approve(Request $request, $approvalId)
    {
        $approval = DocumentApproval::with('approvable')->findOrFail($approvalId);
        $user = auth()->user();

        if (!$approval->canUserApprove($user)) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk menyetujui tahap ini.');
        }

        $notes = $request->input('notes', 'Disetujui.');
        $approval->approve($user, $notes);

        return redirect()->back()->with('success', 'Pengajuan izin berhasil disetujui.');
    }

    /**
     * Tindakan Reject oleh Approver yang berwenang.
     */
    public function reject(Request $request, $approvalId)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:1000',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $approval = DocumentApproval::with('approvable')->findOrFail($approvalId);
        $user = auth()->user();

        if (!$approval->canUserApprove($user)) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk menolak tahap ini.');
        }

        $reason = $request->input('rejection_reason') ?? $request->input('notes') ?? 'Ditolak.';
        $approval->reject($user, $reason);

        return redirect()->back()->with('success', 'Pengajuan izin telah ditolak.');
    }
}

