<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function employee(): HasOne
    {
        return $this->hasOne(EmpData::class, 'user_id');
    }

    /**
     * Accessor untuk employee: Hardcode ambil EmpData ID 16 jika user berstatus superadmin.
     */
    public function getEmployeeAttribute()
    {
        if (($this->attributes['role'] ?? null) === 'superadmin' || ($this->role ?? null) === 'superadmin') {
            return EmpData::with(['jabatan', 'company', 'project.company'])->find(16);
        }

        if (array_key_exists('employee', $this->relations)) {
            return $this->relations['employee'];
        }

        return EmpData::where('user_id', $this->id)->first();
    }

    /**
     * Dapatkan record EmpData terkait (berdasarkan user_id, email, atau nama).
     * Hardcode ID 16 jika superadmin.
     */
    public function getEmployeeRecord(): ?EmpData
    {
        if ($this->role === 'superadmin') {
            return EmpData::with(['jabatan', 'company', 'project.company'])->find(16);
        }

        if (array_key_exists('employee', $this->relations)) {
            return $this->relations['employee'];
        }

        return EmpData::where('user_id', $this->id)->first()
            ?? (!empty($this->email) ? EmpData::where('email', $this->email)->first() : null)
            ?? (!empty($this->name) ? EmpData::where('name', $this->name)->first() : null);
    }

    /**
     * Dapatkan nama karyawan terkait atau fallback ke nama user.
     */
    public function getEmployeeNameAttribute(): string
    {
        if ($this->role === 'superadmin') {
            return EmpData::where('id', 16)->value('name') ?? $this->name;
        }

        if (array_key_exists('employee', $this->relations)) {
            return $this->relations['employee']?->name ?? $this->name;
        }

        return EmpData::where('user_id', $this->id)->value('name') ?? $this->name;
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'user_id');
    }

    public function isSuperAdmin(): bool
    {
        return in_array(strtolower(trim($this->role ?? '')), ['superadmin', 'admin'], true);
    }

    /**
     * Cek apakah user berhak mengakses menu Master Data & Payroll (Hanya Finance, Admin Finance, Cashier, dan Super Admin/Admin).
     */
    public function canAccessFinanceBackoffice(): bool
    {
        // 1. Super Admin & Admin selalu memiliki akses penuh ke backoffice / master data / payroll
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 2. Ambil nama jabatan karyawan
        $jabatanName = null;
        if (array_key_exists('employee', $this->relations)) {
            $jabatanName = $this->relations['employee']?->jabatan?->jabatan;
        } else {
            $emp = $this->employee;
            $jabatanName = $emp?->jabatan?->jabatan ?? $this->employee()->with('jabatan')->first()?->jabatan?->jabatan;
        }

        $allowedJabatan = [
            'finance',
            'admin finance',
            'cashier',
        ];

        if ($jabatanName && in_array(strtolower(trim($jabatanName)), $allowedJabatan, true)) {
            return true;
        }

        // 3. Fallback periksa role akun user jika ada kecocokan
        $userRole = strtolower(trim(str_replace('_', ' ', $this->role ?? '')));
        if (in_array($userRole, $allowedJabatan, true) || in_array(strtolower($this->role ?? ''), ['finance', 'admin_finance', 'cashier'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Cek apakah role user termasuk role approver / manajerial khusus
     * (admin, superadmin, hrd, project_manager).
     */
    public function hasApproverRole(): bool
    {
        return is_approver_role($this);
    }

    /**
     * Cek apakah user berwenang sebagai approver:
     * - Default ($includeRoles = false): Hanya approver fungsional yang jabatannya aktif di setting approval.
     * - Jika $includeRoles = true: Termasuk juga jika user memiliki role manajerial/admin.
     */
    public function isApprover(bool $includeRoles = false): bool
    {
        return is_user_approver($this, $includeRoles);
    }
}
