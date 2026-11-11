<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermitType extends Model
{
    use HasFactory;

    protected $table = 'permit_types';

    protected $fillable = [
        'name',
        'code',
        'is_paid',
        'requires_attachment',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_paid'             => 'boolean',
        'requires_attachment' => 'boolean',
        'is_active'           => 'boolean',
    ];

    /**
     * Relasi ke pengajuan izin (LeaveRequest).
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'permit_type_id');
    }

    /**
     * Scope untuk tipe izin yang aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

