<?php

namespace App\Models;

use App\Jobs\SendEssApprovalNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentApproval extends Model
{
    use HasFactory;

    protected $table = 'document_approvals';

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'module',
        'level',
        'jabatan_id',
        'status',
        'approved_by',
        'action_at',
        'notes',
    ];

    protected $casts = [
        'level' => 'integer',
        'action_at' => 'datetime',
    ];

    /**
     * Get the parent approvable model (LeaveRequest, PermitRequest, SickRequest, etc.).
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(EmpJabatan::class, 'jabatan_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getJabatanNameAttribute(): string
    {
        if ($this->relationLoaded('jabatan')) {
            return $this->jabatan?->jabatan ?? 'Atasan Berwenang';
        }
        return $this->jabatan_id ? (EmpJabatan::find($this->jabatan_id)?->jabatan ?? 'Atasan Berwenang') : 'Atasan Berwenang';
    }

    public function getApproverNameAttribute(): string
    {
        $approver = $this->relationLoaded('approver') 
            ? $this->approver 
            : ($this->approved_by ? User::find($this->approved_by) : null);

        if (!$approver) {
            return 'Approver';
        }

        if ($approver->relationLoaded('employee')) {
            return $approver->employee?->name ?? $approver->name;
        }

        return EmpData::where('user_id', $approver->id)->value('name') ?? $approver->name;
    }

    public function getApproverJabatanNameAttribute(): string
    {
        $approver = $this->relationLoaded('approver') 
            ? $this->approver 
            : ($this->approved_by ? User::find($this->approved_by) : null);

        if ($approver) {
            $emp = $approver->relationLoaded('employee') 
                ? $approver->employee 
                : EmpData::with('jabatan')->where('user_id', $approver->id)->first();

            if ($emp && $emp->jabatan) {
                return $emp->jabatan->jabatan;
            }
        }

        return $this->jabatan_name;
    }

    /**
     * Cek apakah approval ini merupakan tahapan aktif (level pending terendah).
     */
    public function isCurrentPendingLevel(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $lowestPending = static::where('approvable_type', $this->approvable_type)
            ->where('approvable_id', $this->approvable_id)
            ->where('status', 'pending')
            ->min('level');

        return (int) $this->level === (int) $lowestPending;
    }

    /**
     * Cek apakah user berwenang menyetujui/menolak approval ini.
     * Aturan ketat:
     * 1. Status approval pada level ini harus 'pending'.
     * 2. Harus merupakan level pending terendah / aktif saat ini (alur berurutan).
     * 3. Pemohon TIDAK BOLEH menyetujui pengajuannya sendiri.
     * 4. Jabatan user harus persis sesuai dengan jabatan_id approval.
     */
    public function canUserApprove(User $user, $approvable = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $employee = $user->employee;
        if (!$employee) {
            return false;
        }

        // Dapatkan objek induk (approvable) secara aman tanpa memicu LazyLoadingViolationException
        $parent = $approvable ?? ($this->relationLoaded('approvable') ? $this->approvable : null);
        if (!$parent && $this->approvable_type && $this->approvable_id) {
            $parent = $this->approvable_type::find($this->approvable_id);
        }

        // Karyawan pemohon tidak boleh menyetujui pengajuannya sendiri
        if ($parent && isset($parent->emp_id) && (int) $parent->emp_id === (int) $employee->id) {
            return false;
        }

        // Jabatan user harus sesuai dengan jabatan approver pada tahapan ini
        if ((int) $employee->jabatan_id !== (int) $this->jabatan_id) {
            return false;
        }

        // Hanya tahapan pending aktif terendah yang dapat diproses (alur berurutan)
        return $this->isCurrentPendingLevel();
    }

    /**
     * Setujui approval pada level ini.
     */
    public function approve(User $user, ?string $notes = null): bool
    {
        if (!$this->canUserApprove($user)) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'action_at' => now(),
            'notes' => $notes,
        ]);

        // Catat ke Approval History Log
        ApprovalHistory::log($this->approvable, 'approved', $user, $notes, $this->level, $this->jabatan_id);

        $approvable = $this->approvable;

        // Cek dokumen induk apakah semua level sudah approved
        if (method_exists($approvable, 'checkApprovalCompletion')) {
            $approvable->checkApprovalCompletion();
        }

        // Kirim notifikasi WhatsApp via Laravel Job
        $freshApprovable = $approvable->fresh();
        if ($freshApprovable && $freshApprovable->status === 'completed') {
            SendEssApprovalNotification::dispatch($freshApprovable, 'completed', $this);
        } else {
            $nextApproval = $approvable->approvals()->where('status', 'pending')->orderBy('level')->first();
            if ($nextApproval) {
                SendEssApprovalNotification::dispatch($approvable, 'next_level', $nextApproval);
            }
        }

        return true;
    }

    /**
     * Tolak dokumen pada level ini.
     */
    public function reject(User $user, ?string $notes = null): bool
    {
        if (!$this->canUserApprove($user)) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'action_at' => now(),
            'notes' => $notes,
        ]);

        // Catat ke Approval History Log
        ApprovalHistory::log($this->approvable, 'rejected', $user, $notes, $this->level, $this->jabatan_id);

        if (method_exists($this->approvable, 'markAsRejected')) {
            $this->approvable->markAsRejected($user, $notes);
        } else {
            $this->approvable->update([
                'status' => 'rejected',
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $notes,
            ]);
        }

        // Kirim notifikasi WhatsApp rejection ke pemohon
        SendEssApprovalNotification::dispatch($this->approvable, 'rejected', $this, ['notes' => $notes]);

        return true;
    }
}
