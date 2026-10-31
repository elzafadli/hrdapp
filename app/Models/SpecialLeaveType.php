<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpecialLeaveType extends Model
{
    use HasFactory;

    protected $table = 'special_leave_types';

    protected $fillable = [
        'name',
        'code',
        'days_allowed',
        'description',
        'requires_attachment',
        'gender_restriction',
        'is_active',
    ];

    protected $casts = [
        'days_allowed' => 'decimal:1',
        'requires_attachment' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'special_leave_type_id');
    }

    /**
     * Scope untuk tipe cuti khusus yang aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk memfilter jenis cuti berdasarkan gender karyawan (default: male / laki-laki).
     */
    public function scopeForGender($query, ?string $gender = 'male')
    {
        $gender = $gender ? strtolower($gender) : 'male';
        $allowed = ($gender === 'female') ? ['female', 'all'] : ['male', 'all'];

        return $query->whereIn('gender_restriction', $allowed);
    }
}

