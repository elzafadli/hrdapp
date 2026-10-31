<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AttendanceSetting extends Model
{
    use HasFactory;

    protected $table = 'attendance_settings';

    protected $fillable = [
        'office_name',
        'latitude',
        'longitude',
        'radius_meters',
        'allow_outside_radius',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'radius_meters' => 'integer',
        'allow_outside_radius' => 'boolean',
    ];

    /**
     * Dapatkan setting aktif atau default jika belum ada di database.
     */
    public static function getActiveSetting(): self
    {
        if (Schema::hasTable('attendance_settings')) {
            $setting = self::first();
            if ($setting) {
                return $setting;
            }
        }

        // Default setting fallback
        $dummy = new self();
        $dummy->office_name = 'Kantor Pusat';
        $dummy->latitude = -6.2000000;
        $dummy->longitude = 106.8166660;
        $dummy->radius_meters = 150;
        $dummy->allow_outside_radius = true;
        return $dummy;
    }
}

