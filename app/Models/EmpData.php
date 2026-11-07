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
    protected $fillable = [
        'user_id',
        'email',
        'name',
        'nik_karyawan',
        'photo',
        'gender',
        'citizenship',
        'nik_ktp',
        'birth_place',
        'birth_date',
        'marital_status',
        'religion',
        'blood_type',
        'last_education',
        'education_institution',
        'join_date',
        'jabatan_id',
        'status_ptkp',
        'project_id',
        'company_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'join_date'  => 'date',
    ];

    /**
     * Label jenis kelamin dalam Bahasa Indonesia.
     */
    public function getGenderLabelAttribute(): string
    {
        return ($this->gender === 'female') ? 'Perempuan' : 'Laki-laki';
    }

    /**
     * Display ID Karyawan (fallback ke id jika belum diisi).
     */
    public function getEmployeeIdDisplayAttribute(): string
    {
        return $this->nik_karyawan ?: (string) $this->id;
    }

    /**
     * Email resmi karyawan (fallback ke email akun login).
     */
    public function getEffectiveEmailAttribute(): ?string
    {
        return $this->email ?: $this->user?->email;
    }

    /**
     * URL Foto profil dengan fallback placeholder.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }

        if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
            return $this->photo;
        }

        try {
            return \Illuminate\Support\Facades\Storage::disk('is3')->url($this->photo);
        } catch (\Throwable $e) {
            // fallback
        }

        return asset('storage/' . $this->photo);
    }

    /**
     * Tempat dan tanggal lahir gabungan (e.g. Malang, 10 Okt 1995).
     */
    public function getBirthInfoAttribute(): string
    {
        $parts = [];
        if (!empty($this->birth_place)) {
            $parts[] = $this->birth_place;
        }
        if (!empty($this->birth_date)) {
            $parts[] = \Carbon\Carbon::parse($this->birth_date)->translatedFormat('d M Y');
        }
        return count($parts) > 0 ? implode(', ', $parts) : '-';
    }

    /**
     * Tanggal lahir berformat (e.g. 10 Okt 1995).
     */
    public function getFormattedBirthDateAttribute(): string
    {
        return $this->birth_date ? \Carbon\Carbon::parse($this->birth_date)->translatedFormat('d M Y') : '-';
    }

    /**
     * Format tanggal mulai kerja: d/m/Y (e.g. 01/09/2026).
     */
    public function getFormattedJoinDateAttribute(): string
    {
        return $this->join_date ? \Carbon\Carbon::parse($this->join_date)->format('d/m/Y') : '-';
    }

    /**
     * Masa kerja terhitung otomatis (e.g. 2 Bulan -, atau 1 Tahun 3 Bulan -).
     */
    public function getMasaKerjaAttribute(): string
    {
        $startDate = $this->join_date;
        if (!$startDate && \Illuminate\Support\Facades\Schema::hasTable('emp_careers')) {
            try {
                $startDate = $this->careers()->orderBy('tanggal_efektif')->first()?->tanggal_efektif;
            } catch (\Throwable $e) {}
        }
        $startDate = $startDate ?? $this->created_at;

        if (!$startDate) {
            return '-';
        }

        $start = \Carbon\Carbon::parse($startDate);
        $now = \Carbon\Carbon::now();

        if ($start->isFuture()) {
            return 'Belum Mulai';
        }

        $diff = $start->diff($now);
        $parts = [];
        if ($diff->y > 0) {
            $parts[] = $diff->y . ' Tahun';
        }
        if ($diff->m > 0) {
            $parts[] = $diff->m . ' Bulan';
        }

        if (empty($parts)) {
            return ($diff->d > 0) ? ($diff->d . ' Hari -') : '1 Hari -';
        }

        return implode(' ', $parts) . ' -';
    }

    /**
     * Relasi riwayat karir karyawan (multi-entry).
     */
    public function careers(): HasMany
    {
        return $this->hasMany(EmpCareer::class, 'emp_id')->orderByDesc('tanggal_efektif')->orderByDesc('id');
    }

    /**
     * Karir terkini / aktif.
     */
    public function currentCareer(): ?EmpCareer
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('emp_careers')) {
            return null;
        }

        try {
            return $this->careers()->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Relasi riwayat pendidikan karyawan (multi-entry).
     */
    public function educations(): HasMany
    {
        return $this->hasMany(EmpEducation::class, 'emp_id')->orderByDesc('tahun_lulus')->orderByDesc('id');
    }

    /**
     * Pendidikan terakhir / terkini.
     */
    public function latestEducation(): ?EmpEducation
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('emp_educations')) {
            return null;
        }

        try {
            return $this->educations()->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Relasi riwayat pengalaman kerja karyawan (multi-entry).
     */
    public function experiences(): HasMany
    {
        return $this->hasMany(EmpExperience::class, 'emp_id')->orderByDesc('tanggal_mulai')->orderByDesc('id');
    }

    /**
     * Relasi pelatihan, sertifikasi, & training karyawan (multi-entry).
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(EmpTraining::class, 'emp_id')->orderByDesc('tahun')->orderByDesc('id');
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

    /**
     * Cek apakah karyawan ini bertindak sebagai approver berdasarkan jabatannya atau penunjukan langsung (karyawan pengganti).
     */
    public function canApprove(): bool
    {
        // Cek jika terdaftar sebagai assigned approver (misal karyawan pengganti) pada dokumen pending aktif
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('document_approvals', 'approver_emp_id')) {
                $hasDirectApproval = DocumentApproval::where('approver_emp_id', $this->id)
                    ->where('status', 'pending')
                    ->exists();
                if ($hasDirectApproval) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        if (!$this->jabatan_id) {
            return false;
        }

        $jabatan = $this->relationLoaded('jabatan') ? $this->jabatan : $this->jabatan;
        if ($jabatan) {
            return $jabatan->canApprove();
        }

        return ApprovalSetting::isJabatanAnApprover((int) $this->jabatan_id);
    }
}
