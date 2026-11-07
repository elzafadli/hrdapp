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
     * Dapatkan nama karyawan terkait atau fallback ke nama user.
     */
    public function getEmployeeNameAttribute(): string
    {
        if ($this->relationLoaded('employee')) {
            return $this->employee?->name ?? $this->name;
        }

        return EmpData::where('user_id', $this->id)->value('name') ?? $this->name;
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'user_id');
    }

    /**
     * Cek apakah user berhak mengakses menu Master Data & Payroll (Hanya Finance, Admin Finance, dan Cashier).
     */
    public function canAccessFinanceBackoffice(): bool
    {
        // 1. Ambil nama jabatan karyawan
        $jabatanName = $this->employee?->jabatan?->jabatan;
        if (!$jabatanName && !$this->relationLoaded('employee')) {
            $jabatanName = $this->employee()->with('jabatan')->first()?->jabatan?->jabatan;
        }

        $allowedJabatan = [
            'finance',
            'admin finance',
            'cashier',
        ];

        if ($jabatanName && in_array(strtolower(trim($jabatanName)), $allowedJabatan, true)) {
            return true;
        }

        // 2. Fallback periksa role akun user jika ada kecocokan
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
