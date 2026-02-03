<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollComponent extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'payroll_reference'];

    public function allowances()
    {
        return $this->hasMany(EmpAllowance::class, 'payroll_component_id');
    }

    public function parent()
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_reference');
    }

    public function children()
    {
        return $this->hasMany(PayrollComponent::class, 'payroll_reference');
    }
}
