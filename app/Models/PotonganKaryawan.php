<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PotonganKaryawan extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'period_date',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'period_date' => 'date',
        'start_date'  => 'date',
        'end_date'    => 'date',
    ];

    public function details()
    {
        return $this->hasMany(PotonganKaryawanDetail::class, 'header_id');
    }
}
