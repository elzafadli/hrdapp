<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'check_in_photo')) {
                $table->string('check_in_photo', 255)->nullable()->after('check_in_notes')->comment('Foto selfie saat presensi masuk');
            }
            if (!Schema::hasColumn('attendances', 'check_out_photo')) {
                $table->string('check_out_photo', 255)->nullable()->after('check_out_notes')->comment('Foto selfie saat presensi keluar');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('attendances', 'check_in_photo')) {
                $columns[] = 'check_in_photo';
            }
            if (Schema::hasColumn('attendances', 'check_out_photo')) {
                $columns[] = 'check_out_photo';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
