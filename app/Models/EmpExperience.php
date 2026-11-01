<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpExperience extends Model
{
    use HasFactory;

    protected $table = 'emp_experiences';

    protected $fillable = [
        'emp_id',
        'perusahaan',
        'jabatan',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_current',
        'gaji_terakhir',
        'deskripsi_pekerjaan',
        'alasan_berhenti',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_current' => 'boolean',
    ];

    protected $appends = [
        'period',
        'formatted_tanggal_mulai',
        'formatted_tanggal_selesai',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'emp_id');
    }

    public function getFormattedTanggalMulaiAttribute(): ?string
    {
        return $this->tanggal_mulai ? Carbon::parse($this->tanggal_mulai)->format('d/m/Y') : null;
    }

    public function getFormattedTanggalSelesaiAttribute(): ?string
    {
        return $this->tanggal_selesai ? Carbon::parse($this->tanggal_selesai)->format('d/m/Y') : null;
    }

    public function getPeriodAttribute(): string
    {
        $start = $this->formatted_tanggal_mulai;
        if ($this->is_current) {
            return ($start ?? '-') . ' - Sekarang';
        }

        $end = $this->formatted_tanggal_selesai;
        if ($start && $end) {
            return $start . ' - ' . $end;
        }

        return $start ?? ($end ?? '-');
    }
}
