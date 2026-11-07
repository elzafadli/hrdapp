<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('potongan_karyawans', function (Blueprint $table) {
            if (!Schema::hasColumn('potongan_karyawans', 'period_date')) {
                $table->date('period_date')->nullable()->after('month')->comment('Tanggal periode penggajian (misal 25 Nov 2026)');
            }
            if (!Schema::hasColumn('potongan_karyawans', 'start_date')) {
                $table->date('start_date')->nullable()->after('period_date')->comment('Tanggal awal cut-off presensi (misal 25 Okt 2026)');
            }
            if (!Schema::hasColumn('potongan_karyawans', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date')->comment('Tanggal akhir cut-off presensi (misal 25 Nov 2026)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('potongan_karyawans', function (Blueprint $table) {
            $dropCols = [];
            if (Schema::hasColumn('potongan_karyawans', 'end_date')) {
                $dropCols[] = 'end_date';
            }
            if (Schema::hasColumn('potongan_karyawans', 'start_date')) {
                $dropCols[] = 'start_date';
            }
            if (Schema::hasColumn('potongan_karyawans', 'period_date')) {
                $dropCols[] = 'period_date';
            }
            if (!empty($dropCols)) {
                $table->dropColumn($dropCols);
            }
        });
    }
};

