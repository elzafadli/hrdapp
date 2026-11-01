<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpTraining extends Model
{
    use HasFactory;

    protected $table = 'emp_trainings';

    protected $fillable = [
        'emp_id',
        'nama_pelatihan',
        'penyelenggara',
        'tahun',
        'nomor_sertifikat',
        'masa_berlaku',
        'deskripsi',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'emp_id');
    }
}
