<?php

namespace App\Http\Controllers;

use App\Helpers\DocumentNumberHelper;
use App\Models\ApprovalHistory;
use App\Models\DocumentApproval;
use App\Models\EmpData;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestDate;
use App\Models\LeaveRequestFile;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeaveRequestController extends Controller
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
            ?? EmpData::first(); // Fallback development jika belum ada relasi
    }

    /**
     * Halaman daftar & riwayat pengajuan cuti.
     */
    public function index(Request $request)
    {
        $employee = $this->getEmployee();
        $currentYear = (int) date('Y');

        $leaveBalance = null;
        $leaveRequests = collect();

        if ($employee) {
            $leaveBalance = LeaveBalance::firstOrCreate(
                ['emp_id' => $employee->id, 'year' => $currentYear],
                ['total_leave' => 12.0, 'used_leave' => 0.0, 'remaining_leave' => 12.0]
            );

            // Riwayat pengajuan cuti karyawan ini
            $leaveRequests = LeaveRequest::with(['dates', 'files', 'approvals.approvable', 'approvals.jabatan'])
                ->where('emp_id', $employee->id)
                ->where('leave_type', 'annual')
                ->latest()
                ->paginate(10);

            // Cek apakah karyawan ini bertindak sebagai approver untuk pengajuan orang lain
            $user = auth()->user();
        }

        return view('ess.leave.index', compact('employee', 'leaveBalance', 'leaveRequests', 'currentYear'));
    }

    /**
     * Tampilkan formulir pengajuan cuti tahunan.
     */
    public function create()
    {
        $employee = $this->getEmployee();
        $currentYear = (int) date('Y');

        if (!$employee) {
            return redirect()->route('leave.index')->with('error', 'Data karyawan Anda belum terdaftar.');
        }

        $leaveBalance = LeaveBalance::firstOrCreate(
            ['emp_id' => $employee->id, 'year' => $currentYear],
            ['total_leave' => 12.0, 'used_leave' => 0.0, 'remaining_leave' => 12.0]
        );

        // Ambil seluruh tanggal cuti yang sudah pernah diajukan oleh karyawan ini agar bisa di-disable di kalender
        $disabledDates = LeaveRequestDate::where('emp_id', $employee->id)
            ->whereHas('leaveRequest', function ($q) {
                $q->whereIn('status', ['pending', 'approved', 'completed']);
            })
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->toArray();

        return view('ess.leave.create', compact('employee', 'leaveBalance', 'disabledDates'));
    }

    /**
     * Hitung preview simulasi sisa cuti secara real-time via AJAX.
     */
    public function calculatePreview(Request $request)
    {
        $employee = $this->getEmployee();
        $currentYear = (int) date('Y');

        $leaveBalance = LeaveBalance::firstOrCreate(
            ['emp_id' => $employee ? $employee->id : 0, 'year' => $currentYear],
            ['total_leave' => 12.0, 'used_leave' => 0.0, 'remaining_leave' => 12.0]
        );

        $currentRemaining = (float) $leaveBalance->remaining_leave;

        // Dukungan input radio button: is_half_day / leave_duration_type
        $isHalfDay = $request->boolean('is_half_day') || $request->input('leave_duration_type') === 'half_day';
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date') ?? $startDate;

        $minAllowedDate = Carbon::tomorrow()->toDateString();

        // 0. Validasi Back-date (Ketentuan H-1: minimal besok)
        if ($startDate && $startDate < $minAllowedDate) {
            return response()->json([
                'current_remaining'  => $currentRemaining,
                'full_day_count'     => 0,
                'half_day_count'     => 0,
                'total_deduction'    => 0,
                'remaining_after'    => $currentRemaining,
                'is_exceeded'        => false,
                'is_backdate'        => true,
                'has_conflict'       => false,
                'conflicts'          => [],
                'conflict_message'   => '',
                'backdate_message'   => 'Tanggal cuti tidak boleh tanggal lampau (back-date). Sesuai ketentuan, pengajuan paling lambat H-1 (mulai ' . Carbon::tomorrow()->format('d/m/Y') . ').',
                'working_days'       => [],
                'skipped_dates'      => [],
            ]);
        }

        $workingDays = [];
        $skippedDates = [];
        $fullDayCount = 0.0;
        $halfDayCount = 0.0;

        // 1. Jika menggunakan input form baru (start_date + is_half_day)
        if ($startDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->startOfDay();

            if ($start->lte($end)) {
                $deductionPerDay = $isHalfDay ? 0.5 : 1.0;
                $labelType = $isHalfDay ? 'Setengah Hari' : 'Sehari Penuh';

                foreach (CarbonPeriod::create($start, $end) as $date) {
                    $dateStr = $date->toDateString();
                    $dayName = $date->translatedFormat('l');

                    if ($employee && $employee->isWorkDay($date)) {
                        $workingDays[] = [
                            'date' => $dateStr,
                            'formatted' => $date->format('d/m/Y'),
                            'day_name' => $dayName,
                            'type' => $labelType,
                            'val' => $deductionPerDay,
                        ];
                        if ($isHalfDay) {
                            $halfDayCount += 0.5;
                        } else {
                            $fullDayCount += 1.0;
                        }
                    } else {
                        $isHoliday = Holiday::isHoliday($date);
                        $skippedDates[] = [
                            'date' => $dateStr,
                            'formatted' => $date->format('d/m/Y'),
                            'day_name' => $dayName,
                            'reason' => $isHoliday ? 'Hari Libur Nasional' : 'Libur Akhir Pekan / Off',
                        ];
                    }
                }
            }
        } else {
            // Fallback input format lama (jika ada full_day_start_date atau half_day_start_date)
            if ($request->filled('full_day_start_date')) {
                $start = Carbon::parse($request->full_day_start_date)->startOfDay();
                $end = Carbon::parse($request->full_day_end_date ?? $request->full_day_start_date)->startOfDay();

                if ($start->lte($end)) {
                    foreach (CarbonPeriod::create($start, $end) as $date) {
                        $dateStr = $date->toDateString();
                        $dayName = $date->translatedFormat('l');

                        if ($employee && $employee->isWorkDay($date)) {
                            $workingDays[] = [
                                'date' => $dateStr,
                                'formatted' => $date->format('d/m/Y'),
                                'day_name' => $dayName,
                                'type' => 'Sehari Penuh',
                                'val' => 1.0,
                            ];
                            $fullDayCount += 1.0;
                        } else {
                            $isHoliday = Holiday::isHoliday($date);
                            $skippedDates[] = [
                                'date' => $dateStr,
                                'formatted' => $date->format('d/m/Y'),
                                'day_name' => $dayName,
                                'reason' => $isHoliday ? 'Hari Libur Nasional' : 'Libur Akhir Pekan / Off',
                            ];
                        }
                    }
                }
            }

            if ($request->filled('half_day_start_date')) {
                $start = Carbon::parse($request->half_day_start_date)->startOfDay();
                $end = Carbon::parse($request->half_day_end_date ?? $request->half_day_start_date)->startOfDay();

                if ($start->lte($end)) {
                    foreach (CarbonPeriod::create($start, $end) as $date) {
                        $dateStr = $date->toDateString();
                        $dayName = $date->translatedFormat('l');

                        if ($employee && $employee->isWorkDay($date)) {
                            $workingDays[] = [
                                'date' => $dateStr,
                                'formatted' => $date->format('d/m/Y'),
                                'day_name' => $dayName,
                                'type' => 'Setengah Hari',
                                'val' => 0.5,
                            ];
                            $halfDayCount += 0.5;
                        } else {
                            $isHoliday = Holiday::isHoliday($date);
                            $skippedDates[] = [
                                'date' => $dateStr,
                                'formatted' => $date->format('d/m/Y'),
                                'day_name' => $dayName,
                                'reason' => $isHoliday ? 'Hari Libur Nasional' : 'Libur Akhir Pekan / Off',
                            ];
                        }
                    }
                }
            }
        }

        $totalDeduction = $fullDayCount + $halfDayCount;
        $remainingAfter = $currentRemaining - $totalDeduction;
        $isExceeded = $totalDeduction > $currentRemaining;

        // Cek apakah ada tanggal kerja yang bentrok dengan pengajuan cuti sebelumnya
        $workingDateStrings = array_column($workingDays, 'date');
        $conflicts = ($employee && !empty($workingDateStrings))
            ? LeaveRequestDate::checkDateConflicts(
                $employee->id,
                $workingDateStrings,
                $isHalfDay,
                $request->input('half_day_type')
            )
            : [];

        $hasConflict = !empty($conflicts);

        return response()->json([
            'current_remaining'  => $currentRemaining,
            'full_day_count'     => $fullDayCount,
            'half_day_count'     => $halfDayCount,
            'total_deduction'    => $totalDeduction,
            'remaining_after'    => $remainingAfter,
            'is_exceeded'        => $isExceeded,
            'has_conflict'       => $hasConflict,
            'conflicts'          => $conflicts,
            'conflict_message'   => $hasConflict ? implode('; ', array_column($conflicts, 'message')) : '',
            'working_days'       => $workingDays,
            'skipped_dates'      => $skippedDates,
        ]);
    }

    /**
     * Simpan pengajuan cuti tahunan baru.
     */
    public function store(Request $request)
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return back()->with('error', 'Data karyawan tidak ditemukan.');
        }

        $request->validate([
            'leave_duration_type' => 'nullable|in:full_day,half_day',
            'is_half_day'         => 'nullable|boolean',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'full_day_start_date' => 'nullable|date',
            'full_day_end_date'   => 'nullable|date|after_or_equal:full_day_start_date',
            'half_day_start_date' => 'nullable|date',
            'half_day_end_date'   => 'nullable|date|after_or_equal:half_day_start_date',
            'half_day_type'       => 'nullable|in:first_half,second_half',
            'reason'              => 'required|string|max:1000',
            'files.*'             => 'nullable|file|max:3072', // Max 3MB per file
        ]);

        $isHalfDay = $request->boolean('is_half_day') || $request->input('leave_duration_type') === 'half_day';
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date') ?? $startDate;

        // Normalisasi input jika menggunakan form baru
        if ($startDate) {
            if ($isHalfDay) {
                $halfDayStartDate = $startDate;
                $halfDayEndDate = $endDate;
                $fullDayStartDate = null;
                $fullDayEndDate = null;
            } else {
                $fullDayStartDate = $startDate;
                $fullDayEndDate = $endDate;
                $halfDayStartDate = null;
                $halfDayEndDate = null;
            }
        } else {
            $fullDayStartDate = $request->input('full_day_start_date');
            $fullDayEndDate = $request->input('full_day_end_date') ?? $fullDayStartDate;
            $halfDayStartDate = $request->input('half_day_start_date');
            $halfDayEndDate = $request->input('half_day_end_date') ?? $halfDayStartDate;
        }

        if (!$fullDayStartDate && !$halfDayStartDate) {
            return back()->withInput()->with('error', 'Silakan pilih tanggal cuti terlebih dahulu.');
        }

        // Validasi Back-Date (Ketentuan H-1: tidak boleh tanggal lampau / hari ini)
        $minAllowedDate = Carbon::tomorrow()->toDateString();
        $targetStartDate = $startDate ?? ($fullDayStartDate ?? $halfDayStartDate);

        if ($targetStartDate && $targetStartDate < $minAllowedDate) {
            return back()->withInput()->with('error', 'Tanggal cuti tidak boleh tanggal lampau (back-date). Sesuai ketentuan perusahaan, pengajuan cuti tahunan paling lambat H-1 (mulai tanggal ' . Carbon::tomorrow()->format('d/m/Y') . ').');
        }

        $currentYear = (int) date('Y');
        $leaveBalance = LeaveBalance::firstOrCreate(
            ['emp_id' => $employee->id, 'year' => $currentYear],
            ['total_leave' => 12.0, 'used_leave' => 0.0, 'remaining_leave' => 12.0]
        );

        // 1. Ekstraksi tanggal-tanggal hari kerja (Cuti Sehari Penuh)
        $fullDayDates = [];
        $fullDayCount = 0.0;

        if ($fullDayStartDate) {
            $start = Carbon::parse($fullDayStartDate)->startOfDay();
            $end = Carbon::parse($fullDayEndDate)->startOfDay();

            foreach (CarbonPeriod::create($start, $end) as $date) {
                if ($employee->isWorkDay($date)) {
                    $fullDayDates[] = $date->toDateString();
                    $fullDayCount += 1.0;
                }
            }
        }

        // 2. Ekstraksi tanggal-tanggal hari kerja (Cuti Setengah Hari)
        $halfDayDates = [];
        $halfDayCount = 0.0;

        if ($halfDayStartDate) {
            $start = Carbon::parse($halfDayStartDate)->startOfDay();
            $end = Carbon::parse($halfDayEndDate)->startOfDay();

            foreach (CarbonPeriod::create($start, $end) as $date) {
                if ($employee->isWorkDay($date)) {
                    $halfDayDates[] = $date->toDateString();
                    $halfDayCount += 0.5;
                }
            }
        }

        $totalDays = $fullDayCount + $halfDayCount;

        // Validasi: Apakah ada hari kerja riil yang terpilih?
        if ($totalDays <= 0) {
            return back()->withInput()->with('error', 'Tanggal yang Anda pilih seluruhnya adalah hari libur atau akhir pekan (0 hari kerja).');
        }

        // Validasi Saldo Cuti
        if ($totalDays > (float) $leaveBalance->remaining_leave) {
            return back()->withInput()->with('error', "Sisa cuti Anda tidak mencukupi. (Sisa: {$leaveBalance->remaining_leave} hari, diajukan: {$totalDays} hari kerja).");
        }

        // Validasi Overlap (Bentrok dengan cuti yang sudah diajukan sebelumnya)
        $allSelectedDates = array_merge($fullDayDates, $halfDayDates);
        $conflicts = LeaveRequestDate::checkDateConflicts(
            $employee->id,
            $allSelectedDates,
            $isHalfDay,
            $request->input('half_day_type')
        );

        if (!empty($conflicts)) {
            $conflictStr = implode(' | ', array_column($conflicts, 'message'));
            return back()->withInput()->with('error', "Tanggal pengajuan bentrok dengan cuti Anda yang sudah ada: {$conflictStr}");
        }

        // 3. Simpan data pengajuan cuti
        DB::beginTransaction();
        try {
            $requestNumber = DocumentNumberHelper::generate('CT', $employee);

            $leaveRequest = LeaveRequest::create([
                'request_number'      => $requestNumber,
                'emp_id'              => $employee->id,
                'user_id'             => auth()->id(),
                'leave_type'          => 'annual',
                'full_day_start_date' => $fullDayStartDate,
                'full_day_end_date'   => $fullDayEndDate,
                'full_day_count'      => $fullDayCount,
                'half_day_start_date' => $halfDayStartDate,
                'half_day_end_date'   => $halfDayEndDate,
                'half_day_count'      => $halfDayCount,
                'half_day_type'       => $isHalfDay ? $request->input('half_day_type') : null,
                'total_days'          => $totalDays,
                'reason'              => $request->reason,
                'status'              => 'pending',
            ]);

            // Simpan rincian tanggal cuti sehari penuh
            foreach ($fullDayDates as $date) {
                LeaveRequestDate::create([
                    'leave_request_id' => $leaveRequest->id,
                    'emp_id'           => $employee->id,
                    'date'             => $date,
                    'day_type'         => 'full_day',
                    'deduction_value'  => 1.0,
                ]);
            }

            // Simpan rincian tanggal cuti setengah hari
            foreach ($halfDayDates as $date) {
                LeaveRequestDate::create([
                    'leave_request_id' => $leaveRequest->id,
                    'emp_id'           => $employee->id,
                    'date'             => $date,
                    'day_type'         => 'half_day',
                    'half_day_type'    => $request->half_day_type,
                    'deduction_value'  => 0.5,
                ]);
            }

            // Simpan lampiran berkas jika ada
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('leave_attachments', 'is3');
                    LeaveRequestFile::create([
                        'leave_request_id' => $leaveRequest->id,
                        'file_path'        => $path,
                        'file_name'        => $file->getClientOriginalName(),
                        'file_size'        => $file->getSize(),
                        'mime_type'        => $file->getClientMimeType(),
                    ]);
                }
            }

            // Inisialisasi alur approval:
            // - Jika belum ada setting approval -> status otomatis 'completed' & kuota terpotong
            // - Jika ada setting approval -> status 'pending' & approval list per level terbuat
            $leaveRequest->initializeApproval();

            DB::commit();

            $msg = $leaveRequest->status === 'completed'
                ? 'Pengajuan cuti berhasil disetujui otomatis (tanpa tahapan approval).'
                : 'Pengajuan cuti berhasil diajukan dan sedang menunggu persetujuan.';

            return redirect()->route('leave.index')->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memproses pengajuan cuti: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail pengajuan cuti beserta histori approval.
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
        ])->findOrFail($id);

        $employee = $this->getEmployee();
        $user = auth()->user();

        return view('ess.leave.show', compact('leaveRequest', 'employee', 'user'));
    }

    /**
     * Batalkan pengajuan cuti yang masih pending oleh karyawan.
     */
    public function cancel($id)
    {
        $employee = $this->getEmployee();
        $leaveRequest = LeaveRequest::where('emp_id', $employee->id)->findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Hanya pengajuan berstatus pending yang dapat dibatalkan.');
        }

        $leaveRequest->update(['status' => 'cancelled']);
        ApprovalHistory::log($leaveRequest, 'cancelled', auth()->user(), 'Dibatalkan oleh pemohon.');

        return redirect()->route('leave.index')->with('success', 'Pengajuan cuti berhasil dibatalkan.');
    }

    /**
     * Tindakan Approve oleh Approver yang berwenang.
     */
    public function approve(Request $request, $approvalId)
    {
        $approval = DocumentApproval::with('approvable')->findOrFail($approvalId);
        $user = auth()->user();

        if (!$approval->canUserApprove($user)) {
            return back()->with('error', 'Anda tidak berwenang menyetujui tahapan approval ini.');
        }

        $notes = $request->input('notes', 'Disetujui.');
        $approval->approve($user, $notes);

        return back()->with('success', 'Persetujuan cuti berhasil diproses.');
    }

    /**
     * Tindakan Reject oleh Approver yang berwenang.
     */
    public function reject(Request $request, $approvalId)
    {
        $request->validate(['notes' => 'required|string|max:500']);

        $approval = DocumentApproval::with('approvable')->findOrFail($approvalId);
        $user = auth()->user();

        if (!$approval->canUserApprove($user)) {
            return back()->with('error', 'Anda tidak berwenang menolak tahapan approval ini.');
        }

        $approval->reject($user, $request->notes);

        return back()->with('success', 'Pengajuan cuti telah ditolak.');
    }
}

