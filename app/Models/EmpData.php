<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmpData extends Model
{
    use HasFactory;

    protected $table = 'emp_data';
    protected $fillable = ['user_id', 'name', 'gender', 'jabatan_id', 'status_ptkp', 'project_id', 'company_id'];

    /**
     * Label jenis kelamin dalam Bahasa Indonesia.
     */
    public function getGenderLabelAttribute(): string
    {
        return ($this->gender === 'female') ? 'Perempuan' : 'Laki-laki';
    }

    /**
     * Get the user that owns the EmpData.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the jabatan that owns the EmpData.
     */
    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(EmpJabatan::class, 'jabatan_id');
    }

    /**
     * Get the project that owns the EmpData.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the company that owns the EmpData.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Dapatkan company efektif (dari company_id langsung atau fallback via project->company).
     */
    public function getEffectiveCompanyAttribute(): ?Company
    {
        return $this->company ?? $this->project?->company;
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(EmpAllowance::class, 'employee_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmpLoan::class, 'emp_id');
    }

    public function bpjs(): HasMany
    {
        return $this->hasMany(EmpBpjs::class, 'employee_id');
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'emp_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'emp_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmpSchedule::class, 'emp_id');
    }

    /**
     * Mendapatkan jadwal kerja karyawan untuk tanggal tertentu.
     * Jika karyawan belum terdaftar di emp_schedules, otomatis menggunakan jadwal default (Reguler).
     */
    public function getWorkScheduleForDate($date = null): ?WorkSchedule
    {
        $targetDate = $date ? \Carbon\Carbon::parse($date)->toDateString() : now()->toDateString();

        $empSchedule = $this->schedules()
            ->where('effective_date', '<=', $targetDate)
            ->where(function ($query) use ($targetDate) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $targetDate);
            })
            ->latest('effective_date')
            ->first();

        if ($empSchedule && $empSchedule->workSchedule) {
            return $empSchedule->workSchedule->loadMissing('days');
        }

        // Default jika karyawan belum terdaftar di schedule khusus:
        return WorkSchedule::getDefault();
    }

    /**
     * Cek apakah tanggal tertentu adalah hari kerja untuk karyawan ini.
     */
    public function isWorkDay($date): bool
    {
        $schedule = $this->getWorkScheduleForDate($date);

        return $schedule ? $schedule->isWorkDay($date) : false;
    }

    /**
     * Menghitung total hari kerja antara dua tanggal untuk karyawan ini.
     * Mengabaikan hari off dan hari libur nasional.
     */
    public function calculateWorkingDays($startDate, $endDate): float
    {
        $schedule = $this->getWorkScheduleForDate($startDate);

        return $schedule ? $schedule->calculateWorkingDays($startDate, $endDate) : 0.0;
    }
}
