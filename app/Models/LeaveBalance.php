<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $table = 'leave_balances';

    protected $fillable = [
        'emp_id',
        'year',
        'total_leave',
        'used_leave',
        'remaining_leave',
        'notes',
    ];

    protected $casts = [
        'total_leave' => 'decimal:1',
        'used_leave' => 'decimal:1',
        'remaining_leave' => 'decimal:1',
        'year' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'emp_id');
    }
}

