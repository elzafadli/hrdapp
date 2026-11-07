<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('work_schedules', 'late_tolerance_minutes')) {
                $table->unsignedInteger('late_tolerance_minutes')->default(30)->after('description')->comment('Toleransi keterlambatan dalam menit');
            }
        });

        Schema::table('work_schedule_days', function (Blueprint $table) {
            if (!Schema::hasColumn('work_schedule_days', 'late_tolerance_time')) {
                $table->time('late_tolerance_time')->nullable()->after('start_time')->comment('Batas jam toleransi keterlambatan (contoh: 08:30:00)');
            }
        });

        // Update seeder & data existing jadwal agar toleransi 30 menit (maksimal 08:30:00)
        if (Schema::hasTable('work_schedules')) {
            DB::table('work_schedules')->update(['late_tolerance_minutes' => 30]);
        }
        if (Schema::hasTable('work_schedule_days')) {
            DB::table('work_schedule_days')
                ->where('is_work_day', true)
                ->where(function ($q) {
                    $q->whereNull('late_tolerance_time')
                      ->orWhere('late_tolerance_time', '00:00:00');
                })
                ->update(['late_tolerance_time' => '08:30:00']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_schedule_days', function (Blueprint $table) {
            if (Schema::hasColumn('work_schedule_days', 'late_tolerance_time')) {
                $table->dropColumn('late_tolerance_time');
            }
        });

        Schema::table('work_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('work_schedules', 'late_tolerance_minutes')) {
                $table->dropColumn('late_tolerance_minutes');
            }
        });
    }
};

