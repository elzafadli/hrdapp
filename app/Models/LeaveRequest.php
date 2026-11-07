<?php

namespace App\Models;

use App\Jobs\SendEssApprovalNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $table = 'leave_requests';

    protected $fillable = [
        'request_number',
        'emp_id',
        'user_id',
        'substitute_emp_id',
        'leave_type',
        'special_leave_type_id',
        'permit_type_id',
        'full_day_start_date',
        'full_day_end_date',
        'full_day_count',
        'half_day_start_date',
        'half_day_end_date',
        'half_day_count',
        'half_day_type',
        'total_days',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'full_day_start_date' => 'date',
        'full_day_end_date' => 'date',
        'full_day_count' => 'decimal:1',
        'half_day_start_date' => 'date',
        'half_day_end_date' => 'date',
        'half_day_count' => 'decimal:1',
        'total_days' => 'decimal:1',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'emp_id');
    }

    public function substitute(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'substitute_emp_id');
    }

    public function specialLeaveType(): BelongsTo
    {
        return $this->belongsTo(SpecialLeaveType::class, 'special_leave_type_id');
    }

    public function permitType(): BelongsTo
    {
        return $this->belongsTo(PermitType::class, 'permit_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(LeaveRequestFile::class, 'leave_request_id');
    }

    public function dates(): HasMany
    {
        return $this->hasMany(LeaveRequestDate::class, 'leave_request_id');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(DocumentApproval::class, 'approvable')->orderBy('level');
    }

    public function getCurrentPendingApproval(): ?DocumentApproval
    {
        return $this->approvals->where('status', 'pending')->sortBy('level')->first();
    }

    public function getCurrentApproverLabelAttribute(): string
    {
        $pending = $this->getCurrentPendingApproval();
        if (!$pending) {
            return '-';
        }
        return $pending->jabatan_name;
    }

    public function approvalHistories(): MorphMany
    {
        return $this->morphMany(ApprovalHistory::class, 'approvable')->latest();
    }

    /**
     * Inisialisasi approval saat pengajuan cuti dibuat:
     * - Karyawan pengganti (jika ada) bertindak sebagai Approver Level 1 (wajib).
     * - Tingkatan approval berikutnya diambil dari setting approval berjenjang.
     * - Jika belum ada setting approval dan tidak ada pengganti, langsung disetujui otomatis.
     */
    public function initializeApproval(): void
    {
        $moduleKey = match ($this->leave_type) {
            'special' => 'special_leave',
            'permit' => 'permit',
            'sick' => 'sick',
            default => 'leave',
        };

        $requesterJabatanId = $this->employee?->jabatan_id;
        $workflow = ApprovalSetting::getActiveWorkflow($moduleKey, $requesterJabatanId);
        if ($workflow->isEmpty() && in_array($this->leave_type, ['special', 'permit', 'sick'])) {
            $workflow = ApprovalSetting::getActiveWorkflow('leave', $requesterJabatanId);
        }

        // Cek karyawan pengganti / penanggung jawab tugas
        $substitute = $this->substitute_emp_id ? ($this->substitute ?? EmpData::find($this->substitute_emp_id)) : null;

        // Kalo belum ada setting nya dan TIDAK ada karyawan pengganti, langsung otomatis COMPLETED
        if ($workflow->isEmpty() && !$substitute) {
            $this->update([
                'status' => 'completed',
                'approved_at' => now(),
            ]);

            ApprovalHistory::log($this, 'auto_completed', null, 'Disetujui otomatis karena belum ada setting approval.');
            SendEssApprovalNotification::dispatch($this, 'auto_completed');
            return;
        }

        // Catat pengajuan awal ke log riwayat
        ApprovalHistory::log($this, 'submitted', $this->user, 'Pengajuan cuti diajukan.');

        $currentLevel = 1;

        // Level 1: Karyawan Pengganti / Penanggung Jawab Tugas
        if ($substitute) {
            $this->approvals()->create([
                'module' => 'leave',
                'level' => $currentLevel,
                'jabatan_id' => $substitute->jabatan_id ?? ($requesterJabatanId ?? 1),
                'approver_emp_id' => $substitute->id,
                'status' => 'pending',
            ]);
            $currentLevel++;
        }

        // Level berikutnya: Alur persetujuan struktural/atasan dari setting approval
        foreach ($workflow as $step) {
            $this->approvals()->create([
                'module' => 'leave',
                'level' => $currentLevel,
                'jabatan_id' => $step->jabatan_id ?? $step->approver_jabatan_id,
                'approver_emp_id' => null,
                'status' => 'pending',
            ]);
            $currentLevel++;
        }

        // Notifikasi ke Approver Level 1
        $firstStep = $this->approvals()->where('status', 'pending')->orderBy('level')->first();
        if ($firstStep) {
            SendEssApprovalNotification::dispatch($this, 'submitted', $firstStep);
        }
    }

    /**
     * Ajukan ulang (resubmit) jika sebelumnya pernah di-reject.
     * Riwayat approval lama tetap tersimpan di ApprovalHistory.
     */
    public function resubmit(?string $reason = null): void
    {
        if ($reason) {
            $this->reason = $reason;
        }

        $this->status = 'pending';
        $this->rejected_by = null;
        $this->rejected_at = null;
        $this->rejection_reason = null;
        $this->save();

        // Hapus approval aktif yang lama (karena riwayat historisnya sudah tercatat aman di ApprovalHistory)
        $this->approvals()->delete();

        // Log aksi pengajuan ulang
        ApprovalHistory::log($this, 'resubmitted', auth()->user(), 'Pengajuan cuti diajukan kembali.');

        // Potong saldo lagi karena saldo sudah dikembalikan saat ditolak sebelumnya
        $this->deductLeaveBalance();

        // Re-generate approval workflow baru (otomatis menotifikasi Approver level 1)
        $this->initializeApproval();
    }

    /**
     * Cek apakah seluruh level approval sudah disetujui.
     * Jika sudah semua, status pengajuan cuti menjadi completed.
     * Saldo TIDAK dipotong lagi di sini karena sudah dipotong saat submit.
     */
    public function checkApprovalCompletion(): void
    {
        $totalLevels = $this->approvals()->count();
        $approvedLevels = $this->approvals()->where('status', 'approved')->count();

        if ($totalLevels > 0 && $totalLevels === $approvedLevels) {
            $this->update([
                'status' => 'completed',
                'approved_at' => now(),
            ]);
        }
    }

    /**
     * Memotong saldo cuti tahunan.
     * Dipanggil LANGSUNG saat pengajuan di-submit (termasuk saat masih pending),
     * bukan menunggu completed. Ini mencegah abuse double-submit.
     */
    public function deductLeaveBalance(): void
    {
        if ($this->leave_type !== 'annual' || $this->total_days <= 0) {
            return;
        }

        $targetDate = $this->full_day_start_date ?? $this->half_day_start_date;
        $year = $targetDate ? Carbon::parse($targetDate)->year : now()->year;

        $balance = LeaveBalance::firstOrCreate(
            ['emp_id' => $this->emp_id, 'year' => $year],
            ['total_leave' => 12.0, 'used_leave' => 0.0, 'remaining_leave' => 12.0]
        );

        $balance->used_leave += $this->total_days;
        $balance->remaining_leave = max(0, $balance->total_leave - $balance->used_leave);
        $balance->save();
    }

    /**
     * Kembalikan saldo cuti tahunan.
     * Dipanggil saat pengajuan dibatalkan oleh karyawan atau ditolak oleh approver.
     */
    public function restoreLeaveBalance(): void
    {
        if ($this->leave_type !== 'annual' || $this->total_days <= 0) {
            return;
        }

        $targetDate = $this->full_day_start_date ?? $this->half_day_start_date;
        $year = $targetDate ? Carbon::parse($targetDate)->year : now()->year;

        $balance = LeaveBalance::firstOrCreate(
            ['emp_id' => $this->emp_id, 'year' => $year],
            ['total_leave' => 12.0, 'used_leave' => 0.0, 'remaining_leave' => 12.0]
        );

        $balance->used_leave = max(0, $balance->used_leave - $this->total_days);
        $balance->remaining_leave = max(0, $balance->total_leave - $balance->used_leave);
        $balance->save();
    }
}
