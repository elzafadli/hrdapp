<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalHistory extends Model
{
    use HasFactory;

    protected $table = 'approval_histories';

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'module',
        'level',
        'jabatan_id',
        'action',
        'user_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'level' => 'integer',
        'metadata' => 'array',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(EmpJabatan::class, 'jabatan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUserNameAttribute(): string
    {
        $user = $this->relationLoaded('user')
            ? $this->user
            : ($this->user_id ? User::find($this->user_id) : null);

        if (!$user) {
            return 'Sistem';
        }

        if ($user->relationLoaded('employee')) {
            return $user->employee?->name ?? $user->name;
        }

        return EmpData::where('user_id', $user->id)->value('name') ?? $user->name;
    }

    public function getJabatanNameAttribute(): ?string
    {
        if ($this->relationLoaded('jabatan') && $this->jabatan) {
            return $this->jabatan->jabatan;
        }

        if ($this->jabatan_id) {
            return EmpJabatan::find($this->jabatan_id)?->jabatan;
        }

        if ($this->user_id) {
            $emp = EmpData::with('jabatan')->where('user_id', $this->user_id)->first();
            return $emp?->jabatan?->jabatan;
        }

        return null;
    }

    /**
     * Helper cepat untuk mencatat riwayat approval.
     */
    public static function log($approvable, string $action, ?User $user = null, ?string $notes = null, ?int $level = null, ?int $jabatanId = null, ?array $metadata = null): self
    {
        return self::create([
            'approvable_type' => get_class($approvable),
            'approvable_id' => $approvable->id,
            'module' => $approvable->leave_type ?? 'leave',
            'level' => $level,
            'jabatan_id' => $jabatanId,
            'action' => $action,
            'user_id' => $user ? $user->id : auth()->id(),
            'notes' => $notes,
            'metadata' => $metadata,
        ]);
    }
}

