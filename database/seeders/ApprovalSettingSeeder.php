<?php

namespace Database\Seeders;

use App\Models\ApprovalSetting;
use App\Models\ApprovalSettingDetail;
use App\Models\EmpJabatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApprovalSettingSeeder extends Seeder
{
    /**
     * Jalankan seeder alur approval terpusat untuk semua modul ESS ('all').
     * Dengan modul 'all', konfigurasi tidak perlu diulang-ulang untuk setiap jenis pengajuan.
     */
    public function run(): void
    {
        if (!Schema::hasTable('approval_settings') || !Schema::hasTable('approval_setting_details')) {
            $this->command?->warn('Tabel approval_settings atau approval_setting_details belum tersedia.');
            return;
        }

        $pm = EmpJabatan::where('jabatan', 'Project Manager')->first();
        $finance = EmpJabatan::where('jabatan', 'Finance')->first();
        $cashier = EmpJabatan::where('jabatan', 'Cashier')->first();

        $pmId = $pm?->id ?? 3;
        $financeId = $finance?->id ?? 2;
        $cashierId = $cashier?->id ?? 4;

        DB::transaction(function () use ($pmId, $financeId, $cashierId) {
            // Bersihkan data lama alur approval
            ApprovalSettingDetail::query()->delete();
            ApprovalSetting::query()->delete();

            // 1. Alur Default untuk SEMUA MODUL ESS (Semua Jabatan)
            // Level 1: Project Manager (Atasan Langsung) -> Level 2: HRD / Finance
            $defaultSetting = ApprovalSetting::create([
                'module'               => 'all',
                'requester_jabatan_id' => null,
                'description'          => 'Semua Modul ESS (Semua Jabatan)',
                'is_active'            => true,
            ]);

            ApprovalSettingDetail::create([
                'approval_setting_id' => $defaultSetting->id,
                'level'               => 1,
                'approver_jabatan_id' => $pmId,
                'description'         => 'Persetujuan Level 1: Project Manager / Atasan Langsung',
                'min_approvers'       => 1,
            ]);

            ApprovalSettingDetail::create([
                'approval_setting_id' => $defaultSetting->id,
                'level'               => 2,
                'approver_jabatan_id' => $financeId,
                'description'         => 'Persetujuan Level 2: HRD / Finance Approval',
                'min_approvers'       => 1,
            ]);

            // 2. Alur Khusus Karyawan Kasir untuk SEMUA MODUL ESS (1 Level: Finance)
            $cashierSetting = ApprovalSetting::create([
                'module'               => 'all',
                'requester_jabatan_id' => $cashierId,
                'description'          => 'Khusus Karyawan Kasir (Semua Modul - 1 Level Approval)',
                'is_active'            => true,
            ]);

            ApprovalSettingDetail::create([
                'approval_setting_id' => $cashierSetting->id,
                'level'               => 1,
                'approver_jabatan_id' => $financeId,
                'description'         => 'Persetujuan Level 1: Finance Approval',
                'min_approvers'       => 1,
            ]);
        });

        $this->command?->info('Seeder ApprovalSetting (' . ApprovalSetting::count() . ' setting) berhasil dijalankan.');
    }
}
