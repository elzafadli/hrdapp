<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpJabatan extends Model
{
    use HasFactory;

    protected $table = 'emp_jabatan';
    protected $fillable = ['jabatan'];
}
