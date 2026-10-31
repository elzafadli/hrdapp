<?php

namespace App\Models;

use App\Jobs\SendEssApprovalNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OvertimeRequest extends Model
{
    use HasFactory;

    protected $table = 'overtime_requests';

    protected $fillable = [
        'request_number',
        'emp_id',
        'user_id',
        'date',
        'start_time',
        'end_time',
        'break_minutes',
        'total_hours',
        'day_type',
        'reason',
        'task_description',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'total_hours' => 'decimal:2',
        'break_minutes' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'emp_id');
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
        return $this->hasMany(OvertimeRequestFile::class, 'overtime_request_id');
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
     * Inisialisasi approval saat pengajuan lembur dibuat.
     */
    public function initializeApproval(): void
    {
        $requesterJabatanId = $this->employee?->jabatan_id;
        $workflow = ApprovalSetting::getActiveWorkflow('overtime', $requesterJabatanId);
        if ($workflow->isEmpty()) {
            $workflow = ApprovalSetting::getActiveWorkflow('leave', $requesterJabatanId);
        }

        // Kalo belum ada setting nya, langsung otomatis COMPLETED
        if ($workflow->isEmpty()) {
            $this->update([
                'status' => 'completed',
                'approved_at' => now(),
            ]);

            ApprovalHistory::log($this, 'auto_completed', null, 'Disetujui otomatis karena belum ada setting approval.');
            SendEssApprovalNotification::dispatch($this, 'auto_completed');
            return;
        }

        // Catat pengajuan awal ke log riwayat
        ApprovalHistory::log($this, 'submitted', $this->user, 'Pengajuan lembur diajukan.');

        // Generate approval sesuai level & jabatan
        foreach ($workflow as $step) {
            $this->approvals()->create([
                'module' => 'overtime',
                'level' => $step->level,
                'jabatan_id' => $step->jabatan_id ?? $step->approver_jabatan_id,
                'status' => 'pending',
            ]);
        }

        // Notifikasi ke Approver Level 1
        $firstStep = $this->approvals()->where('status', 'pending')->orderBy('level')->first();
        if ($firstStep) {
            SendEssApprovalNotification::dispatch($this, 'submitted', $firstStep);
        }
    }

    /**
     * Cek apakah seluruh level approval sudah disetujui.
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
}
