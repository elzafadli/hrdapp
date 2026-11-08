<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollResult extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(EmpData::class, 'emp_id');
    }

    public function payrollSetting()
    {
        return $this->belongsTo(PayrollSetting::class);
    }

    public function payrollComponent()
    {
        return $this->belongsTo(PayrollComponent::class);
    }

    /**
     * Scope untuk hanya mengambil payroll_results yang berstatus final (jika kolom status sudah dimigrasi).
     */
    public function scopeFinal($query)
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('payroll_results', 'status')) {
            return $query->where('status', 'final');
        }
        return $query;
    }
}
