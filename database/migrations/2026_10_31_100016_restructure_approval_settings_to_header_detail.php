<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop tabel lama jika ada
        Schema::dropIfExists('approval_setting_details');
        Schema::dropIfExists('approval_settings');

        // 1. Tabel Header Approval Setting
        Schema::create('approval_settings', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50)->comment('Nama modul ESS: leave, special_leave, permit, sick, overtime, all');
            $table->foreignId('requester_jabatan_id')->nullable()->constrained('emp_jabatan')->nullOnDelete()->comment('Jabatan karyawan pemohon. Null berarti berlaku untuk semua jabatan');
            $table->string('description')->nullable()->comment('Keterangan alur approval');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['module', 'requester_jabatan_id', 'is_active'], 'idx_mod_reqjab_active');
        });

        // 2. Tabel Detail Approval Setting (Tingkat / Level Persetujuan)
        Schema::create('approval_setting_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_setting_id')->constrained('approval_settings')->onDelete('cascade');
            $table->unsignedTinyInteger('level')->default(1)->comment('Urutan tingkat approval: 1, 2, dst');
            $table->foreignId('approver_jabatan_id')->constrained('emp_jabatan')->onDelete('cascade')->comment('Jabatan approver pada tingkat ini');
            $table->string('description')->nullable()->comment('Keterangan peran approver, misal: Atasan Langsung, HRD Approval');
            $table->unsignedTinyInteger('min_approvers')->default(1)->comment('Jumlah minimal approver');
            $table->timestamps();

            $table->unique(['approval_setting_id', 'level'], 'uniq_setting_level');
            $table->index(['approver_jabatan_id']);
        });

        // Seed data alur standar (Default Semua Jabatan)
        $modules = [
            'leave'         => 'Cuti Tahunan Standar',
            'special_leave' => 'Cuti Khusus Standar',
            'permit'        => 'Izin Standar',
            'sick'          => 'Sakit Standar',
            'overtime'      => 'Lembur Standar',
        ];

        foreach ($modules as $mod => $desc) {
            $headId = DB::table('approval_settings')->insertGetId([
                'module'               => $mod,
                'requester_jabatan_id' => null, // Default berlaku untuk semua jabatan
                'description'          => $desc . ' (Semua Jabatan)',
                'is_active'            => true,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            DB::table('approval_setting_details')->insert([
                [
                    'approval_setting_id' => $headId,
                    'level'               => 1,
                    'approver_jabatan_id' => 3, // Project Manager
                    'description'         => 'Persetujuan Level 1: Project Manager / Atasan Langsung',
                    'min_approvers'       => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ],
                [
                    'approval_setting_id' => $headId,
                    'level'               => 2,
                    'approver_jabatan_id' => 2, // Finance
                    'description'         => 'Persetujuan Level 2: HRD / Finance Approval',
                    'min_approvers'       => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ],
            ]);
        }

        // Seed khusus contoh kebutuhan user: Jabatan Cashier (ID 4) hanya 1 level approval untuk Cuti!
        $cashierHeadId = DB::table('approval_settings')->insertGetId([
            'module'               => 'leave',
            'requester_jabatan_id' => 4, // Cashier
            'description'          => 'Khusus Karyawan Kasir (Hanya 1 Level Approval)',
            'is_active'            => true,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        DB::table('approval_setting_details')->insert([
            [
                'approval_setting_id' => $cashierHeadId,
                'level'               => 1,
                'approver_jabatan_id' => 3, // Project Manager
                'description'         => 'Persetujuan Level 1: Project Manager / Supervisor Kasir',
                'min_approvers'       => 1,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_setting_details');
        Schema::dropIfExists('approval_settings');
    }
};

