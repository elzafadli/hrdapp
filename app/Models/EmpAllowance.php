<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpAllowance extends Model
{
    use HasFactory;

    protected $table = 'emp_allowance';

    protected $fillable = [
        'employee_id',
        'payroll_component_id',
        'value',
    ];

    public function employee()
    {
        return $this->belongsTo(EmpData::class, 'employee_id');
    }

    public function payrollComponent()
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }
}
