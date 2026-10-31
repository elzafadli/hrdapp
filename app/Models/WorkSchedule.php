<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $table = 'work_schedules';

    protected $fillable = [
        'name',
        'code',
        'is_default',
        'description',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function days(): HasMany
    {
        return $this->hasMany(WorkScheduleDay::class, 'work_schedule_id');
    }

    public function employeeSchedules(): HasMany
    {
        return $this->hasMany(EmpSchedule::class, 'work_schedule_id');
    }

    /**
     * Get default regular schedule (Senin - Jumat 08:00 - 17:00).
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->with('days')->first();
    }

    /**
     * Check if a specific date is a working day based on this schedule and holidays.
     */
    public function isWorkDay($date): bool
    {
        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);

        // 1. Cek apakah tanggal adalah hari libur nasional / cuti bersama
        if (Holiday::isHoliday($carbonDate)) {
            return false;
        }

        // 2. Cek hari kerja pada jadwal (Carbon dayOfWeekIso: 1 = Senin, ..., 7 = Minggu)
        $dayOfWeek = $carbonDate->dayOfWeekIso;
        $scheduleDay = $this->days->firstWhere('day_of_week', $dayOfWeek);

        return $scheduleDay ? (bool) $scheduleDay->is_work_day : false;
    }

    /**
     * Calculate total working days between two dates.
     * Excludes weekly off days and national holidays.
     */
    public function calculateWorkingDays($startDate, $endDate): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->gt($end)) {
            return 0.0;
        }

        // Ambil daftar hari libur dalam rentang tanggal
        $holidayDates = Holiday::getHolidayDatesBetween($start, $end);
        $workDaysMap = $this->days->pluck('is_work_day', 'day_of_week')->toArray();

        $period = CarbonPeriod::create($start, $end);
        $workingDays = 0;

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $dayOfWeek = $date->dayOfWeekIso; // 1 = Senin, ..., 7 = Minggu

            // Lewati jika hari libur nasional
            if (in_array($dateStr, $holidayDates)) {
                continue;
            }

            // Cek apakah hari kerja sesuai jadwal
            if (!empty($workDaysMap[$dayOfWeek])) {
                $workingDays++;
            }
        }

        return (float) $workingDays;
    }
}
