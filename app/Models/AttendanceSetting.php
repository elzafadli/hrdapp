<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class AttendanceSetting extends Model
{
    use HasFactory;

    protected $table = 'attendance_settings';

    protected $fillable = [
        'company_id',
        'project_id',
        'office_name',
        'latitude',
        'longitude',
        'radius_meters',
        'allow_outside_radius',
    ];

    protected $casts = [
        'company_id'           => 'integer',
        'project_id'           => 'integer',
        'latitude'             => 'decimal:7',
        'longitude'            => 'decimal:7',
        'radius_meters'        => 'integer',
        'allow_outside_radius' => 'boolean',
    ];

    /**
     * Relasi ke Perusahaan (Company).
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Relasi ke Proyek (Project).
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Dapatkan setting presensi berdasarkan prioritas Employee:
     * 1. Prioritas Utama : Company & Project sesuai data karyawan
     * 2. Prioritas Kedua : Company saja (kantor pusat/cabang perusahaan tanpa project khusus)
     * 3. Prioritas Ketiga: Setting Global (Company & Project null)
     * 4. Fallback        : Setting pertama yang ada atau default dummy
     */
    public static function getSettingForEmployee($employee = null): self
    {
        if (!Schema::hasTable('attendance_settings')) {
            return self::defaultFallback();
        }

        $hasCompanyCol = Schema::hasColumn('attendance_settings', 'company_id');
        $hasProjectCol = Schema::hasColumn('attendance_settings', 'project_id');

        if ($employee && $hasCompanyCol) {
            // Prioritas 1: Company + Project
            if ($hasProjectCol && !empty($employee->company_id) && !empty($employee->project_id)) {
                $setting = self::with(['company', 'project'])
                    ->where('company_id', $employee->company_id)
                    ->where('project_id', $employee->project_id)
                    ->first();
                if ($setting) {
                    return $setting;
                }
            }

            // Prioritas 2: Company saja (project_id null)
            if (!empty($employee->company_id)) {
                $query = self::with(['company', 'project'])->where('company_id', $employee->company_id);
                if ($hasProjectCol) {
                    $query->whereNull('project_id');
                }
                $setting = $query->first();
                if ($setting) {
                    return $setting;
                }
            }
        }

        // Prioritas 3: Setting Global (company_id null)
        if ($hasCompanyCol) {
            $general = self::with(['company', 'project'])->whereNull('company_id')->first();
            if ($general) {
                return $general;
            }
        }

        // Prioritas 4: Record pertama yang ada di tabel
        $first = self::with(['company', 'project'])->first();
        if ($first) {
            return $first;
        }

        return self::defaultFallback();
    }

    /**
     * Dapatkan setting aktif atau default jika belum ada di database.
     * Kompatibel dengan pemanggilan legacy.
     */
    public static function getActiveSetting($employee = null): self
    {
        return self::getSettingForEmployee($employee);
    }

    /**
     * Objek fallback default jika belum ada data di database.
     */
    public static function defaultFallback(): self
    {
        $dummy = new self();
        $dummy->office_name = 'Kantor Pusat (Default)';
        $dummy->latitude = -6.2000000;
        $dummy->longitude = 106.8166660;
        $dummy->radius_meters = 150;
        $dummy->allow_outside_radius = true;
        return $dummy;
    }
}
