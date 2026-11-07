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
        'approver_emp_id',
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

    public function approverEmployee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'approver_emp_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getJabatanNameAttribute(): string
    {
        if ($this->approver_emp_id) {
            $emp = $this->relationLoaded('approverEmployee')
                ? $this->approverEmployee
                : EmpData::find($this->approver_emp_id);
            return 'Karyawan Pengganti' . ($emp ? " ({$emp->name})" : '');
        }

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
     * Cek apakah approval ini merupakan tahapan aktif (level pending terendah yang siap diproses).
     */
    public function isCurrentPendingLevel(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        // Jika dokumen induk sudah tidak pending (misal rejected/cancelled/completed), alur berhenti
        if ($this->relationLoaded('approvable') && $this->approvable && in_array($this->approvable->status, ['rejected', 'cancelled', 'completed', 'approved'])) {
            return false;
        }

        // Jika ada tahapan approval lain pada dokumen ini yang berstatus 'rejected', alur sudah berakhir
        $hasRejection = static::where('approvable_type', $this->approvable_type)
            ->where('approvable_id', $this->approvable_id)
            ->where('status', 'rejected')
            ->exists();

        if ($hasRejection) {
            return false;
        }

        // Semua level di bawah level ini harus sudah berstatus 'approved' (alur berurutan)
        $unapprovedLower = static::where('approvable_type', $this->approvable_type)
            ->where('approvable_id', $this->approvable_id)
            ->where('level', '<', $this->level)
            ->where('status', '!=', 'approved')
            ->exists();

        if ($unapprovedLower) {
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

        // Dokumen induk harus ada dan tidak berstatus rejected/cancelled/completed
        if (!$parent || in_array($parent->status, ['rejected', 'cancelled', 'completed'])) {
            return false;
        }

        // Karyawan pemohon tidak boleh menyetujui pengajuannya sendiri
        if (isset($parent->emp_id) && (int) $parent->emp_id === (int) $employee->id) {
            return false;
        }

        // Jika tahapan approval ini menunjuk karyawan tertentu secara spesifik (misal: karyawan pengganti)
        if ($this->approver_emp_id) {
            if ((int) $employee->id !== (int) $this->approver_emp_id) {
                return false;
            }
        } else {
            // Jika persetujuan berdasarkan struktural jabatan, periksa kesesuaian jabatan user
            if ((int) $employee->jabatan_id !== (int) $this->jabatan_id) {
                return false;
            }
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
            // Notifikasi ke pemohon bahwa pengajuan telah disetujui pada tahapan ini
            SendEssApprovalNotification::dispatch($approvable, 'step_approved', $this);
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

        // Kembalikan saldo cuti jika pengajuan ditolak
        if (method_exists($this->approvable, 'restoreLeaveBalance')) {
            $this->approvable->restoreLeaveBalance();
        }

        // Otomatis batalkan / tolak semua tahapan pending lainnya untuk dokumen ini
        $approverRoleName = $this->jabatan_name ?? 'Approver';
        static::where('approvable_type', $this->approvable_type)
            ->where('approvable_id', $this->approvable_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'action_at' => now(),
                'notes' => 'Otomatis dibatalkan karena pengajuan ditolak pada Level ' . $this->level . ' (' . $approverRoleName . ').',
            ]);

        // Kirim notifikasi WhatsApp rejection ke pemohon
        SendEssApprovalNotification::dispatch($this->approvable, 'rejected', $this, ['notes' => $notes]);

        return true;
    }

    /**
     * Scope untuk mengambil persetujuan yang aktif dan benar-benar siap diproses oleh approver.
     * Alur berurutan:
     * - Status persetujuan level ini harus 'pending'.
     * - Dokumen induk harus masih berstatus 'pending'.
     * - Tidak ada level berapapun pada dokumen ini yang berstatus 'rejected'.
     * - Semua level di bawah level ini harus sudah berstatus 'approved'.
     */
    public function scopeActionablePending($query)
    {
        return $query->where('document_approvals.status', 'pending')
            ->where(function ($q) {
                $morphClasses = [
                    LeaveRequest::class,
                    OvertimeRequest::class,
                ];
                foreach ($morphClasses as $class) {
                    $q->orWhere(function ($sub) use ($class) {
                        $sub->where('document_approvals.approvable_type', $class)
                            ->whereHasMorph('approvable', [$class], function ($inner) {
                                $inner->where('status', 'pending');
                            });
                    });
                }
            })
            ->whereNotExists(function ($sub) {
                $sub->selectRaw(1)
                    ->from('document_approvals as da_rej')
                    ->whereColumn('da_rej.approvable_type', 'document_approvals.approvable_type')
                    ->whereColumn('da_rej.approvable_id', 'document_approvals.approvable_id')
                    ->where('da_rej.status', 'rejected');
            })
            ->whereNotExists(function ($sub) {
                $sub->selectRaw(1)
                    ->from('document_approvals as da_prev')
                    ->whereColumn('da_prev.approvable_type', 'document_approvals.approvable_type')
                    ->whereColumn('da_prev.approvable_id', 'document_approvals.approvable_id')
                    ->whereColumn('da_prev.level', '<', 'document_approvals.level')
                    ->where('da_prev.status', '!=', 'approved');
            });
    }

    /**
     * Batalkan semua tahapan approval pending saat pengajuan dibatalkan oleh pemohon.
     */
    public static function cancelFor($approvable, ?string $reason = 'Dibatalkan oleh pemohon.'): void
    {
        if (!$approvable) {
            return;
        }

        static::where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'notes' => $reason,
                'action_at' => now(),
            ]);
    }
}
