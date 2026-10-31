<?php

namespace App\Http\Controllers;

use App\Helpers\DocumentNumberHelper;
use App\Models\ApprovalHistory;
use App\Models\DocumentApproval;
use App\Models\EmpData;
use App\Models\Holiday;
use App\Models\OvertimeRequest;
use App\Models\OvertimeRequestFile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OvertimeRequestController extends Controller
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
     * Dashboard daftar riwayat pengajuan lembur & inbox approval.
     */
    public function index(Request $request)
    {
        $employee = $this->getEmployee();
        $currentYear = (int) date('Y');

        $overtimeRequests = collect();
        $stats = [
            'total' => 0,
            'approved' => 0,
            'pending' => 0,
            'total_hours' => 0.0,
        ];

        if ($employee && \Illuminate\Support\Facades\Schema::hasTable('overtime_requests')) {
            $overtimeRequests = OvertimeRequest::with(['files', 'approvals.approvable', 'approvals.jabatan'])
                ->where('emp_id', $employee->id)
                ->latest()
                ->paginate(10);

            $stats['total'] = OvertimeRequest::where('emp_id', $employee->id)->count();
            $stats['approved'] = OvertimeRequest::where('emp_id', $employee->id)->whereIn('status', ['approved', 'completed'])->count();
            $stats['pending'] = OvertimeRequest::where('emp_id', $employee->id)->where('status', 'pending')->count();
            $stats['total_hours'] = (float) OvertimeRequest::where('emp_id', $employee->id)
                ->whereIn('status', ['approved', 'completed'])
                ->sum('total_hours');

            // Inbox approval bagi atasan
            $user = auth()->user();
        }

        return view('ess.overtime.index', compact('employee', 'overtimeRequests', 'currentYear', 'stats'));
    }

    /**
     * Formulir pengajuan lembur baru.
     */
    public function create()
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return redirect()->route('overtime.index')->with('error', 'Data karyawan Anda belum terdaftar.');
        }

        return view('ess.overtime.create', compact('employee'));
    }

    /**
     * Kalkulasi simulasi durasi jam lembur secara real-time via AJAX (Golden Base Template).
     */
    public function calculatePreview(Request $request)
    {
        $employee = $this->getEmployee();
        $date = $request->input('date');
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');
        $breakMinutes = (int) $request->input('break_minutes', 0);

        if (!$date || !$startTime || !$endTime) {
            return response()->json([
                'total_hours'      => 0.0,
                'day_type'         => 'workday',
                'day_type_label'   => 'Hari Kerja Biasa',
                'break_minutes'    => 0,
                'has_conflict'     => false,
                'conflict_message' => '',
                'is_valid'         => false,
                'message'          => 'Lengkapi tanggal dan jam lembur.',
            ]);
        }

        $carbonDate = Carbon::parse($date);

        // Deteksi jenis hari (hari kerja biasa, libur nasional, akhir pekan)
        $isHoliday = Holiday::isHoliday($carbonDate);
        $isWorkDay = $employee ? $employee->isWorkDay($carbonDate) : true;

        if ($isHoliday) {
            $dayType = 'holiday';
            $dayTypeLabel = 'Hari Libur Nasional';
        } elseif (!$isWorkDay) {
            $dayType = 'weekend';
            $dayTypeLabel = 'Hari Libur / Akhir Pekan (Off)';
        } else {
            $dayType = 'workday';
            $dayTypeLabel = 'Hari Kerja Biasa';
        }

        // Kalkulasi durasi jam
        $start = Carbon::parse($date . ' ' . $startTime);
        $end = Carbon::parse($date . ' ' . $endTime);

        // Jika jam selesai lebih kecil dari jam mulai, asumsikan lembur melintasi tengah malam (overnight)
        if ($end->lt($start)) {
            $end->addDay();
        }

        $diffMinutes = $start->diffInMinutes($end);
        $netMinutes = max(0, $diffMinutes - $breakMinutes);
        $totalHours = round($netMinutes / 60, 2);

        // Cek apakah ada konflik / tumpang tindih lembur di tanggal yang sama
        $hasConflict = false;
        $conflictMessage = '';

        if ($employee && \Illuminate\Support\Facades\Schema::hasTable('overtime_requests')) {
            $existing = OvertimeRequest::where('emp_id', $employee->id)
                ->where('date', $date)
                ->whereIn('status', ['pending', 'approved', 'completed'])
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function ($sub) use ($startTime, $endTime) {
                          $sub->where('start_time', '<=', $startTime)
                              ->where('end_time', '>=', $endTime);
                      });
                })
                ->first();

            if ($existing) {
                $hasConflict = true;
                $conflictMessage = "Sudah ada pengajuan lembur pada tanggal {$carbonDate->format('d/m/Y')} pukul {$existing->start_time} s/d {$existing->end_time} ({$existing->request_number}).";
            }
        }

        return response()->json([
            'total_hours'      => $totalHours,
            'day_type'         => $dayType,
            'day_type_label'   => $dayTypeLabel,
            'break_minutes'    => $breakMinutes,
            'has_conflict'     => $hasConflict,
            'conflict_message' => $conflictMessage,
            'is_valid'         => $totalHours > 0 && !$hasConflict,
            'duration_text'    => floor($netMinutes / 60) . ' Jam ' . ($netMinutes % 60) . ' Menit',
        ]);
    }

    /**
     * Simpan pengajuan lembur baru.
     */
    public function store(Request $request)
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return back()->with('error', 'Data karyawan tidak ditemukan.');
        }

        $request->validate([
            'date'             => 'required|date',
            'start_time'       => 'required',
            'end_time'         => 'required',
            'break_minutes'    => 'nullable|integer|min:0|max:300',
            'reason'           => 'required|string|max:1000',
            'task_description' => 'nullable|string|max:2000',
            'files.*'          => 'nullable|file|max:10240', // Maks 10MB per berkas
        ]);

        $date = $request->input('date');
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');
        $breakMinutes = (int) $request->input('break_minutes', 0);

        // Kalkulasi durasi
        $start = Carbon::parse($date . ' ' . $startTime);
        $end = Carbon::parse($date . ' ' . $endTime);
        if ($end->lt($start)) {
            $end->addDay();
        }

        $diffMinutes = $start->diffInMinutes($end);
        $netMinutes = max(0, $diffMinutes - $breakMinutes);
        $totalHours = round($netMinutes / 60, 2);

        if ($totalHours <= 0) {
            return back()->withInput()->with('error', 'Durasi lembur harus lebih besar dari 0 jam.');
        }

        // Cek konflik
        $existing = OvertimeRequest::where('emp_id', $employee->id)
            ->where('date', $date)
            ->whereIn('status', ['pending', 'approved', 'completed'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function ($sub) use ($startTime, $endTime) {
                      $sub->where('start_time', '<=', $startTime)
                          ->where('end_time', '>=', $endTime);
                  });
            })
            ->first();

        if ($existing) {
            return back()->withInput()->with('error', "Tanggal dan jam lembur bentrok dengan pengajuan lembur sebelumnya ({$existing->request_number}).");
        }

        // Tentukan day type
        $carbonDate = Carbon::parse($date);
        $dayType = 'workday';
        if (Holiday::isHoliday($carbonDate)) {
            $dayType = 'holiday';
        } elseif (!$employee->isWorkDay($carbonDate)) {
            $dayType = 'weekend';
        }

        DB::beginTransaction();
        try {
            $requestNumber = DocumentNumberHelper::generate('SPL', $employee);

            $overtime = OvertimeRequest::create([
                'request_number'   => $requestNumber,
                'emp_id'           => $employee->id,
                'user_id'          => auth()->id(),
                'date'             => $date,
                'start_time'       => $startTime,
                'end_time'         => $endTime,
                'break_minutes'    => $breakMinutes,
                'total_hours'      => $totalHours,
                'day_type'         => $dayType,
                'reason'           => $request->reason,
                'task_description' => $request->task_description,
                'status'           => 'pending',
            ]);

            // Simpan lampiran file jika ada
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('overtime_attachments', 'is3');
                    OvertimeRequestFile::create([
                        'overtime_request_id' => $overtime->id,
                        'file_path'           => $path,
                        'file_name'           => $file->getClientOriginalName(),
                        'file_size'           => $file->getSize(),
                        'mime_type'           => $file->getClientMimeType(),
                    ]);
                }
            }

            // Inisialisasi alur approval
            $overtime->initializeApproval();

            DB::commit();

            $msg = $overtime->status === 'completed'
                ? 'Pengajuan lembur berhasil disetujui otomatis.'
                : 'Pengajuan lembur berhasil diajukan dan sedang menunggu persetujuan atasan.';

            return redirect()->route('overtime.index')->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memproses pengajuan lembur: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail pengajuan lembur.
     */
    public function show($id)
    {
        $overtime = OvertimeRequest::with([
            'employee.jabatan',
            'files',
            'approvals.approvable', 'approvals.jabatan',
            'approvals.approver.employee',
            'approvalHistories.user.employee',
            'approvalHistories.jabatan'
        ])->findOrFail($id);

        $employee = $this->getEmployee();
        $user = auth()->user();

        $overtimeRequest = $overtime;
        return view('ess.overtime.show', compact('overtime', 'overtimeRequest', 'employee', 'user'));
    }

    /**
     * Batalkan pengajuan lembur pending oleh karyawan pemohon.
     */
    public function cancel($id)
    {
        $employee = $this->getEmployee();
        $overtime = OvertimeRequest::where('emp_id', $employee->id)->findOrFail($id);

        if ($overtime->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan berstatus pending yang dapat dibatalkan.');
        }

        $overtime->update(['status' => 'cancelled']);
        ApprovalHistory::log($overtime, 'cancelled', auth()->user(), 'Dibatalkan oleh pemohon.');

        return redirect()->route('overtime.index')->with('success', 'Pengajuan lembur berhasil dibatalkan.');
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

        return redirect()->back()->with('success', 'Pengajuan lembur berhasil disetujui.');
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

        return redirect()->back()->with('success', 'Pengajuan lembur telah ditolak.');
    }
}
