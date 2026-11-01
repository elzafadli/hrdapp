<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpCareer extends Model
{
    use HasFactory;

    protected $table = 'emp_careers';

    protected $fillable = [
        'emp_id',
        'status_karyawan',
        'penempatan_kerja',
        'tanggal_efektif',
        'organisasi',
        'pangkat',
        'jabatan',
        'is_current',
        'notes',
    ];

    protected $casts = [
        'tanggal_efektif' => 'date',
        'is_current'      => 'boolean',
    ];

    protected $appends = [
        'formatted_tanggal_efektif',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'emp_id');
    }

    /**
     * Format tanggal efektif: d/m/Y (e.g. 01/09/2026).
     */
    public function getFormattedTanggalEfektifAttribute(): string
    {
        return $this->tanggal_efektif ? \Carbon\Carbon::parse($this->tanggal_efektif)->format('d/m/Y') : '-';
    }
}
