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
    ];

    public function details()
    {
        return $this->hasMany(PotonganKaryawanDetail::class, 'header_id');
    }
}
