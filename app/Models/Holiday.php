<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $table = 'holidays';

    protected $fillable = [
        'holiday_date',
        'name',
        'year',
        'is_cuti_bersama',
        'description',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_cuti_bersama' => 'boolean',
        'year' => 'integer',
    ];

    /**
     * Check if a given date is a holiday.
     */
    public static function isHoliday($date): bool
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
        return self::where('holiday_date', $dateStr)->exists();
    }

    /**
     * Get list of holiday date strings between two dates.
     *
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @return array<string>
     */
    public static function getHolidayDatesBetween($startDate, $endDate): array
    {
        $start = $startDate instanceof Carbon ? $startDate->toDateString() : Carbon::parse($startDate)->toDateString();
        $end = $endDate instanceof Carbon ? $endDate->toDateString() : Carbon::parse($endDate)->toDateString();

        return self::whereBetween('holiday_date', [$start, $end])
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();
    }
}

