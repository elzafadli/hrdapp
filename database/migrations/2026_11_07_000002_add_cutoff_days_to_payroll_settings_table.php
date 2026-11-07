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
        Schema::table('payroll_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_settings', 'cutoff_start_day')) {
                $table->integer('cutoff_start_day')->default(25)->nullable()->after('is_aktif');
            }
            if (!Schema::hasColumn('payroll_settings', 'cutoff_end_day')) {
                $table->integer('cutoff_end_day')->default(25)->nullable()->after('cutoff_start_day');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_settings', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_settings', 'cutoff_end_day')) {
                $table->dropColumn('cutoff_end_day');
            }
            if (Schema::hasColumn('payroll_settings', 'cutoff_start_day')) {
                $table->dropColumn('cutoff_start_day');
            }
        });
    }
};
