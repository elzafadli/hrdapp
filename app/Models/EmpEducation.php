<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpEducation extends Model
{
    use HasFactory;

    protected $table = 'emp_educations';

    protected $fillable = [
        'emp_id',
        'jenjang',
        'institusi',
        'jurusan',
        'tahun_masuk',
        'tahun_lulus',
        'nilai',
        'notes',
    ];

    protected $appends = [
        'period',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'emp_id');
    }

    /**
     * Tampilan periode pendidikan (e.g. 2018 - 2022).
     */
    public function getPeriodAttribute(): string
    {
        if ($this->tahun_masuk && $this->tahun_lulus) {
            return $this->tahun_masuk . ' - ' . $this->tahun_lulus;
        }

        return $this->tahun_lulus ? ('Lulus ' . $this->tahun_lulus) : ($this->tahun_masuk ?? '-');
    }
}

