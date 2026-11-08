<?php

namespace App\Http\Controllers;

use App\Helpers\DocumentNumberHelper;
use App\Models\ApprovalHistory;
use App\Models\ApprovalSetting;
use App\Models\DocumentApproval;
use App\Models\EmpData;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestDate;
use App\Models\LeaveRequestFile;
use App\Models\SpecialLeaveType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SpecialLeaveController extends Controller
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
     * Dashboard daftar riwayat cuti khusus & inbox approval.
     */
    public function index(Request $request)
    {
        $employee = $this->getEmployee();
        $currentYear = (int) date('Y');

        $leaveRequests = collect();
        $stats = [
            'total' => 0,
            'approved' => 0,
            'pending' => 0,
        ];

        if ($employee) {
            $leaveRequests = LeaveRequest::with(['specialLeaveType', 'dates', 'files', 'approvals.approvable', 'approvals.jabatan'])
                ->where('emp_id', $employee->id)
                ->where('leave_type', 'special')
                ->latest()
                ->paginate(10);

            $stats['total'] = LeaveRequest::where('emp_id', $employee->id)->where('leave_type', 'special')->count();
            $stats['approved'] = LeaveRequest::where('emp_id', $employee->id)->where('leave_type', 'special')->whereIn('status', ['approved', 'completed'])->count();
            $stats['pending'] = LeaveRequest::where('emp_id', $employee->id)->where('leave_type', 'special')->where('status', 'pending')->count();

            // Cek apakah ada approval masuk bagi approver ini
            $user = auth()->user();
        }

        return view('ess.special-leave.index', compact('employee', 'leaveRequests', 'currentYear', 'stats'));
    }

    /**
     * Formulir pengajuan cuti khusus baru.
     */
    public function create()
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return redirect()->route('special-leave.index')->with('error', 'Data karyawan Anda belum terdaftar.');
        }

        // Ambil daftar tipe cuti khusus yang terfilter berdasarkan gender karyawan (default: male / laki-laki)
        $empGender = $employee->gender ?? 'male';
        $specialLeaveTypes = SpecialLeaveType::active()
            ->forGender($empGender)
            ->orderBy('name')
            ->get();
        $types = $specialLeaveTypes;
        $typeOptions = $specialLeaveTypes->mapWithKeys(function ($t) {
            $days = (float) $t->days_allowed;
            $daysLabel = ($days == (int) $days) ? (int) $days : $days;
            return [$t->id => "{$t->name} (Jatah: {$daysLabel} Hari)"];
        })->toArray();

        // Ambil seluruh tanggal cuti yang sudah ada untuk di-disable di kalender Flatpickr
        $disabledDates = LeaveRequestDate::where('emp_id', $employee->id)
            ->whereHas('leaveRequest', function ($q) {
                $q->whereIn('status', ['pending', 'approved', 'completed']);
            })
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->toArray();

        return view('ess.special-leave.create', compact('employee', 'specialLeaveTypes', 'types', 'typeOptions', 'disabledDates'));
    }

    /**
     * Simpan pengajuan cuti khusus baru.
     */
    public function store(Request $request)
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return back()->with('error', 'Data karyawan tidak ditemukan.');
        }

        $request->validate([
            'special_leave_type_id' => 'required|exists:special_leave_types,id',
            'start_date'            => 'required|date',
            'reason'                => 'required|string|max:1000',
            'files.*'               => 'nullable|file|max:3072', // Maks 3MB per file
        ]);

        $specialLeaveType = SpecialLeaveType::findOrFail($request->special_leave_type_id);
        $empGender = $employee->gender ?? 'male';

        // Validasi restriksi gender pada tipe cuti khusus
        if ($specialLeaveType->gender_restriction !== 'all' && $specialLeaveType->gender_restriction !== $empGender) {
            $forLabel = ($specialLeaveType->gender_restriction === 'female') ? 'karyawan perempuan' : 'karyawan laki-laki';
            return back()->withInput()->with('error', "Jenis cuti {$specialLeaveType->name} khusus diperuntukkan bagi {$forLabel}.");
        }

        $startDate = $request->input('start_date');

        // Validasi Back-Date (Ketentuan H-1)
        $minAllowedDate = Carbon::tomorrow()->toDateString();
        if ($startDate < $minAllowedDate) {
            return back()->withInput()->with('error', 'Tanggal cuti tidak boleh tanggal lampau (back-date). Pengajuan paling lambat H-1 (mulai tanggal ' . Carbon::tomorrow()->format('d/m/Y') . ').');
        }

        // Hitung otomatis tanggal selesai & daftar hari kerja sesuai jatah cuti khusus
        $daysNeeded = (float) $specialLeaveType->days_allowed;
        $currentDate = Carbon::parse($startDate)->startOfDay();
        $fullDayDates = [];
        $halfDayDates = [];
        $totalDays = 0.0;
        $safetyCounter = 0;

        while ($totalDays < $daysNeeded && $safetyCounter < 120) {
            if ($employee->isWorkDay($currentDate)) {
                $fullDayDates[] = $currentDate->toDateString();
                $totalDays += 1.0;
            }
            if ($totalDays < $daysNeeded) {
                $currentDate->addDay();
            }
            $safetyCounter++;
        }

        if ($totalDays <= 0) {
            return back()->withInput()->with('error', 'Tanggal yang Anda pilih tidak memiliki hari kerja yang dapat diajukan.');
        }

        $endDate = !empty($fullDayDates) ? end($fullDayDates) : $startDate;

        // Validasi Bentrok Tanggal
        $conflicts = LeaveRequestDate::checkDateConflicts(
            $employee->id,
            $fullDayDates,
            false,
            null
        );

        if (!empty($conflicts)) {
            $conflictStr = implode(' | ', array_column($conflicts, 'message'));
            return back()->withInput()->with('error', "Tanggal pengajuan bentrok dengan cuti Anda yang sudah ada: {$conflictStr}");
        }

        DB::beginTransaction();
        try {
            $requestNumber = DocumentNumberHelper::generate('CK', $employee);

            $leaveRequest = LeaveRequest::create([
                'request_number'        => $requestNumber,
                'emp_id'                => $employee->id,
                'user_id'               => auth()->id(),
                'leave_type'            => 'special',
                'special_leave_type_id' => $specialLeaveType->id,
                'full_day_start_date'   => $startDate,
                'full_day_end_date'     => $endDate,
                'full_day_count'        => $totalDays,
                'half_day_start_date'   => null,
                'half_day_end_date'     => null,
                'half_day_count'        => 0.0,
                'half_day_type'         => null,
                'total_days'            => $totalDays,
                'reason'                => $request->reason,
                'status'                => 'pending',
            ]);

            // Simpan rincian tanggal cuti
            foreach ($fullDayDates as $date) {
                LeaveRequestDate::create([
                    'leave_request_id' => $leaveRequest->id,
                    'emp_id'           => $employee->id,
                    'date'             => $date,
                    'day_type'         => 'full_day',
                    'deduction_value'  => 1.0,
                ]);
            }

            // Simpan file lampiran jika ada
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $folder = 'special_leave_attachments/' . date('Y') . '/' . date('m');
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
                ? 'Pengajuan cuti khusus berhasil disetujui otomatis.'
                : 'Pengajuan cuti khusus berhasil diajukan dan sedang menunggu persetujuan.';

            return redirect()->route('special-leave.index')->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memproses pengajuan cuti khusus: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail pengajuan cuti khusus.
     */
    public function show($id)
    {
        $leaveRequest = LeaveRequest::with([
            'specialLeaveType',
            'employee.jabatan',
            'dates',
            'files',
            'approvals.approvable', 'approvals.jabatan',
            'approvals.approver.employee',
            'approvalHistories.user.employee',
            'approvalHistories.jabatan'
        ])
        ->where('leave_type', 'special')
        ->findOrFail($id);

        $employee = $this->getEmployee();
        $user = auth()->user();

        $specialLeave = $leaveRequest;
        return view('ess.special-leave.show', compact('leaveRequest', 'specialLeave', 'employee', 'user'));
    }

    /**
     * Batalkan pengajuan cuti khusus pending oleh karyawan.
     */
    public function cancel($id)
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return back()->with('error', 'Anda tidak memiliki akses data karyawan.');
        }

        $leaveRequest = LeaveRequest::where('emp_id', $employee->id)
            ->where('leave_type', 'special')
            ->findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan berstatus pending yang dapat dibatalkan.');
        }

        $leaveRequest->update(['status' => 'cancelled']);
        ApprovalHistory::log($leaveRequest, 'cancelled', auth()->user(), 'Dibatalkan oleh pemohon.');
        DocumentApproval::cancelFor($leaveRequest);

        return redirect()->route('special-leave.index')->with('success', 'Pengajuan cuti khusus berhasil dibatalkan.');
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

        return redirect()->back()->with('success', 'Pengajuan cuti khusus berhasil disetujui.');
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

        return redirect()->back()->with('success', 'Pengajuan cuti khusus telah ditolak.');
    }
}

