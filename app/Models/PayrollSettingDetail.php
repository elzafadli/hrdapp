<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSettingDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function payrollSetting()
    {
        return $this->belongsTo(PayrollSetting::class);
    }

    public function component()
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }
}
