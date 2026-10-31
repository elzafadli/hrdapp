<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $fillable = [
        'emp_id',
        'date',
        'check_in_time',
        'check_in_lat',
        'check_in_lng',
        'check_in_distance',
        'check_in_status',
        'check_in_notes',
        'check_out_time',
        'check_out_lat',
        'check_out_lng',
        'check_out_distance',
        'check_out_status',
        'check_out_notes',
        'work_duration_minutes',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_lat' => 'decimal:7',
        'check_in_lng' => 'decimal:7',
        'check_in_distance' => 'decimal:2',
        'check_out_lat' => 'decimal:7',
        'check_out_lng' => 'decimal:7',
        'check_out_distance' => 'decimal:2',
        'work_duration_minutes' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'emp_id');
    }
}

