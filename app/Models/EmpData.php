<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpData extends Model
{
    use HasFactory;

    protected $table = 'emp_data';
    protected $fillable = ['name', 'jabatan_id', 'status_ptkp'];

    /**
     * Get the jabatan that owns the EmpData.
     */
    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(EmpJabatan::class, 'jabatan_id');
    }

    public function allowances()
    {
        return $this->hasMany(EmpAllowance::class, 'employee_id');
    }

    public function loans()
    {
        return $this->hasMany(EmpLoan::class, 'emp_id');
    }

    public function bpjs()
    {
        return $this->hasMany(EmpBpjs::class, 'employee_id');
    }
}
