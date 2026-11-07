<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkScheduleDay extends Model
{
    use HasFactory;

    protected $table = 'work_schedule_days';

    protected $fillable = [
        'work_schedule_id',
        'day_of_week',
        'day_name',
        'is_work_day',
        'start_time',
        'late_tolerance_time',
        'end_time',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_work_day' => 'boolean',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }
}

