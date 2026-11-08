<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalSettingDetail extends Model
{
    use HasFactory;

    protected $table = 'approval_setting_details';

    protected $fillable = [
        'approval_setting_id',
        'level',
        'approver_jabatan_id',
        'description',
        'min_approvers',
    ];

    protected $casts = [
        'level' => 'integer',
        'min_approvers' => 'integer',
    ];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(ApprovalSetting::class, 'approval_setting_id');
    }

    public function approverJabatan(): BelongsTo
    {
        return $this->belongsTo(EmpJabatan::class, 'approver_jabatan_id');
    }

    /**
     * Relasi ke spesifik karyawan approver jika ditunjuk langsung (nullable).
     */
    public function approverEmployee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'approver_emp_id');
    }

    /**
     * Relasi ke seluruh karyawan dengan jabatan approver pada tingkat ini.
     */
    public function approverEmployees(): HasMany
    {
        return $this->hasMany(EmpData::class, 'jabatan_id', 'approver_jabatan_id');
    }

    /**
     * Alias relasi jabatan approver.
     */
    public function jabatan(): BelongsTo
    {
        return $this->approverJabatan();
    }

    /**
     * Alias accessor jabatan_id.
     */
    public function getJabatanIdAttribute()
    {
        return $this->approver_jabatan_id;
    }
}

