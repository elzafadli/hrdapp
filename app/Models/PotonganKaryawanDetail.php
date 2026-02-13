<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PotonganKaryawanDetail extends Model
{
    use HasFactory;

    protected $table = 'potongan_karyawan_details';

    protected $fillable = [
        'header_id',
        'employee_id',
        'payroll_component_id',
        'value',
    ];

    public function header()
    {
        return $this->belongsTo(PotonganKaryawan::class, 'header_id');
    }

    public function employee()
    {
        return $this->belongsTo(EmpData::class, 'employee_id');
    }

    public function payrollComponent()
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }
}
