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

class SickLeaveController extends Controller
{
    /**
     * Dapatkan employee untuk user yang sedang login.
     */
    protected function getEmployee()
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        if ($user->role === 'superadmin') {
            return EmpData::find(16);
        }

        return $user->employee
            ?? EmpData::where('user_id', $user->id)->first()
            ?? (!empty($user->email) ? EmpData::where('email', $user->email)->first() : null);
    }

    /**
     * Dashboard daftar riwayat izin sakit & inbox approval.
     */
    public function index(Request $request)
    {
        $employee = $this->getEmployee();
        $currentYear = (int) date('Y');

        $sickRequests = collect();
        $stats = [
            'total' => 0,
            'approved' => 0,
            'pending' => 0,
            'total_days' => 0.0,
        ];

        if ($employee) {
            $sickRequests = LeaveRequest::with(['dates', 'files', 'approvals.approvable', 'approvals.jabatan'])
                ->where('emp_id', $employee->id)
                ->where('leave_type', 'sick')
                ->latest()
                ->paginate(10);

            $stats['total'] = LeaveRequest::where('emp_id', $employee->id)->where('leave_type', 'sick')->count();
            $stats['approved'] = LeaveRequest::where('emp_id', $employee->id)->where('leave_type', 'sick')->whereIn('status', ['approved', 'completed'])->count();
            $stats['pending'] = LeaveRequest::where('emp_id', $employee->id)->where('leave_type', 'sick')->where('status', 'pending')->count();
            $stats['total_days'] = (float) LeaveRequest::where('emp_id', $employee->id)
                ->where('leave_type', 'sick')
                ->whereIn('status', ['approved', 'completed'])
                ->sum('total_days');

            // Inbox approval bagi atasan
            $user = auth()->user();
        }

        return view('ess.sick.index', compact('employee', 'sickRequests', 'currentYear', 'stats'));
    }

    /**
     * Formulir pengajuan izin sakit baru.
     */
    public function create()
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return redirect()->route('sick.index')->with('error', 'Data karyawan Anda belum terdaftar.');
        }

        // Ambil daftar tanggal cuti/izin/sakit yang sudah pernah diajukan untuk di-disable di kalender Flatpickr
        $disabledDates = LeaveRequestDate::where('emp_id', $employee->id)
            ->whereHas('leaveRequest', function ($q) {
                $q->whereIn('status', ['pending', 'approved', 'completed']);
            })
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->toArray();

        return view('ess.sick.create', compact('employee', 'disabledDates'));
    }

    /**
     * Simpan pengajuan izin sakit baru.
     */
    public function store(Request $request)
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return back()->with('error', 'Data karyawan tidak ditemukan.');
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:1000',
            'files.*'    => 'nullable|file|max:10240', // Maks 10MB per file sesuai mockup
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date') ?? $startDate;

        // Validasi batas lampau maksimal 7 hari
        $minAllowedDate = Carbon::today()->subDays(7)->toDateString();
        if ($startDate < $minAllowedDate) {
            return back()->withInput()->with('error', 'Pengajuan izin sakit maksimal lampau 7 hari kalender (paling lampau tanggal ' . Carbon::parse($minAllowedDate)->format('d/m/Y') . ').');
        }

        // Ekstraksi tanggal hari kerja
        $fullDayDates = [];
        $fullDayCount = 0.0;

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($employee->isWorkDay($date)) {
                $fullDayDates[] = $date->toDateString();
                $fullDayCount += 1.0;
            }
        }

        $totalDays = $fullDayCount;

        if ($totalDays <= 0) {
            return back()->withInput()->with('error', 'Tanggal yang Anda pilih seluruhnya adalah hari libur atau akhir pekan (0 hari kerja).');
        }

        // Validasi Bentrok Tanggal
        $conflicts = LeaveRequestDate::checkDateConflicts(
            $employee->id,
            $fullDayDates,
            false,
            null
        );

        if (!empty($conflicts)) {
            $conflictStr = implode(' | ', array_column($conflicts, 'message'));
            return back()->withInput()->with('error', "Tanggal pengajuan bentrok dengan permohonan Anda yang sudah ada: {$conflictStr}");
        }

        DB::beginTransaction();
        try {
            $requestNumber = DocumentNumberHelper::generate('SK', $employee);

            $leaveRequest = LeaveRequest::create([
                'request_number'      => $requestNumber,
                'emp_id'              => $employee->id,
                'user_id'             => auth()->id(),
                'leave_type'          => 'sick',
                'full_day_start_date' => $startDate,
                'full_day_end_date'   => $endDate,
                'full_day_count'      => $fullDayCount,
                'half_day_start_date' => null,
                'half_day_end_date'   => null,
                'half_day_count'      => 0.0,
                'half_day_type'       => null,
                'total_days'          => $totalDays,
                'reason'              => $request->reason,
                'status'              => 'pending',
            ]);

            // Simpan rincian tanggal sakit
            foreach ($fullDayDates as $date) {
                LeaveRequestDate::create([
                    'leave_request_id' => $leaveRequest->id,
                    'emp_id'           => $employee->id,
                    'date'             => $date,
                    'day_type'         => 'full_day',
                    'deduction_value'  => 1.0,
                ]);
            }

            // Simpan lampiran file jika ada (Surat Dokter / Resep)
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $folder = 'sick_attachments/' . date('Y') . '/' . date('m');
                    try {
                        $path = $file->store($folder, 'is3');
                    } catch (\Throwable $e) {
                        $path = $file->store($folder, 'public');
                    }
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
                ? 'Pengajuan izin sakit berhasil disetujui otomatis.'
                : 'Pengajuan izin sakit berhasil diajukan dan sedang menunggu persetujuan atasan.';

            return redirect()->route('sick.index')->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memproses pengajuan izin sakit: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail pengajuan izin sakit.
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
        ->where('leave_type', 'sick')
        ->findOrFail($id);

        $employee = $this->getEmployee();
        $user = auth()->user();

        $sickRequest = $leaveRequest;
        return view('ess.sick.show', compact('leaveRequest', 'sickRequest', 'employee', 'user'));
    }

    /**
     * Batalkan pengajuan izin sakit pending oleh karyawan.
     */
    public function cancel($id)
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return back()->with('error', 'Anda tidak memiliki akses data karyawan.');
        }

        $leaveRequest = LeaveRequest::where('emp_id', $employee->id)
            ->where('leave_type', 'sick')
            ->findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan berstatus pending yang dapat dibatalkan.');
        }

        $leaveRequest->update(['status' => 'cancelled']);
        ApprovalHistory::log($leaveRequest, 'cancelled', auth()->user(), 'Dibatalkan oleh pemohon.');
        DocumentApproval::cancelFor($leaveRequest);

        return redirect()->route('sick.index')->with('success', 'Pengajuan izin sakit berhasil dibatalkan.');
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

        return redirect()->back()->with('success', 'Pengajuan izin sakit berhasil disetujui.');
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

        return redirect()->back()->with('success', 'Pengajuan izin sakit telah ditolak.');
    }
}

