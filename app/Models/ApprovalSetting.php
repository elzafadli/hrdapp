<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class ApprovalSetting extends Model
{
    use HasFactory;

    protected $table = 'approval_settings';

    protected $fillable = [
        'module',
        'requester_jabatan_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Jabatan karyawan pemohon yang dikenakan aturan alur ini.
     * Null berarti berlaku umum untuk semua jabatan.
     */
    public function requesterJabatan(): BelongsTo
    {
        return $this->belongsTo(EmpJabatan::class, 'requester_jabatan_id');
    }

    /**
     * Daftar tingkatan approver berjenjang pada aturan ini.
     */
    public function details(): HasMany
    {
        return $this->hasMany(ApprovalSettingDetail::class, 'approval_setting_id')->orderBy('level');
    }

    /**
     * Cek apakah jabatan tertentu terdaftar sebagai approver di setting manapun.
     * Digunakan sidebar untuk menyembunyikan menu Approval bagi yang bukan approver.
     */
    public static function isJabatanAnApprover(int $jabatanId): bool
    {
        // Skema baru: cek di approval_setting_details
        if (Schema::hasTable('approval_setting_details')) {
            return ApprovalSettingDetail::where('approver_jabatan_id', $jabatanId)
                ->whereHas('setting', fn ($q) => $q->where('is_active', true))
                ->exists();
        }

        // Skema lama: cek di approval_settings langsung
        return self::where('is_active', true)
            ->where('jabatan_id', $jabatanId)
            ->exists();
    }

    /**
     * Ambil workflow approval yang aktif untuk modul dan jabatan pemohon tertentu.
     * Prioritas:
     * 1. Aturan spesifik (Modul sama + Jabatan Pemohon sama persis).
     * 2. Aturan default modul (Modul sama + requester_jabatan_id NULL / Semua Jabatan).
     * 3. Aturan global ('all' + Jabatan Pemohon sama).
     * 4. Aturan global default ('all' + Semua Jabatan).
     */
    public static function getActiveWorkflow(string $module, ?int $requesterJabatanId = null)
    {
        // Skema baru Header-Detail
        if (Schema::hasTable('approval_setting_details')) {
            // 1. Cari aturan spesifik modul & jabatan pemohon
            if ($requesterJabatanId) {
                $setting = self::with(['details.approverJabatan'])
                    ->where('is_active', true)
                    ->where('module', $module)
                    ->where('requester_jabatan_id', $requesterJabatanId)
                    ->first();

                if ($setting && $setting->details->isNotEmpty()) {
                    return $setting->details->sortBy('level');
                }
            }

            // 2. Fallback: Aturan modul default untuk Semua Jabatan
            $defaultSetting = self::with(['details.approverJabatan'])
                ->where('is_active', true)
                ->where('module', $module)
                ->whereNull('requester_jabatan_id')
                ->first();

            if ($defaultSetting && $defaultSetting->details->isNotEmpty()) {
                return $defaultSetting->details->sortBy('level');
            }

            // 3. Fallback: Aturan global 'all' spesifik jabatan pemohon
            if ($requesterJabatanId) {
                $globalSetting = self::with(['details.approverJabatan'])
                    ->where('is_active', true)
                    ->where('module', 'all')
                    ->where('requester_jabatan_id', $requesterJabatanId)
                    ->first();

                if ($globalSetting && $globalSetting->details->isNotEmpty()) {
                    return $globalSetting->details->sortBy('level');
                }
            }

            // 4. Fallback: Aturan global 'all' default semua jabatan
            $globalDefault = self::with(['details.approverJabatan'])
                ->where('is_active', true)
                ->where('module', 'all')
                ->whereNull('requester_jabatan_id')
                ->first();

            if ($globalDefault && $globalDefault->details->isNotEmpty()) {
                return $globalDefault->details->sortBy('level');
            }

            return collect();
        }

        // Skema legacy flat table (sebelum migrate manual dijalankan)
        return self::where('is_active', true)
            ->where(function ($q) use ($module) {
                $q->where('module', $module)
                    ->orWhere('module', 'all');
            })
            ->orderBy('level')
            ->get();
    }
}
