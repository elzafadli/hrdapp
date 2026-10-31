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
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_default')->default(false)->comment('Apakah jadwal reguler default untuk semua karyawan');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('work_schedule_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_schedule_id')->constrained('work_schedules')->onDelete('cascade');
            $table->unsignedTinyInteger('day_of_week')->comment('1: Senin, 2: Selasa, 3: Rabu, 4: Kamis, 5: Jumat, 6: Sabtu, 7: Minggu');
            $table->string('day_name', 20);
            $table->boolean('is_work_day')->default(true)->comment('true: Hari kerja, false: Hari libur mingguan/off');
            $table->time('start_time')->nullable()->default('08:00:00')->comment('Jam masuk kerja');
            $table->time('end_time')->nullable()->default('17:00:00')->comment('Jam pulang kerja');
            $table->timestamps();

            $table->unique(['work_schedule_id', 'day_of_week']);
        });

        // Seed jadwal reguler default (Senin - Jumat 08:00 - 17:00)
        $scheduleId = DB::table('work_schedules')->insertGetId([
            'name' => 'Reguler (Senin - Jumat 08:00 - 17:00)',
            'code' => 'REGULER_5D',
            'is_default' => true,
            'description' => 'Jadwal kerja normal reguler 5 hari kerja, jam masuk 08:00 s/d 17:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $days = [
            ['day_of_week' => 1, 'day_name' => 'Senin', 'is_work_day' => true, 'start_time' => '08:00:00', 'end_time' => '17:00:00'],
            ['day_of_week' => 2, 'day_name' => 'Selasa', 'is_work_day' => true, 'start_time' => '08:00:00', 'end_time' => '17:00:00'],
            ['day_of_week' => 3, 'day_name' => 'Rabu', 'is_work_day' => true, 'start_time' => '08:00:00', 'end_time' => '17:00:00'],
            ['day_of_week' => 4, 'day_name' => 'Kamis', 'is_work_day' => true, 'start_time' => '08:00:00', 'end_time' => '17:00:00'],
            ['day_of_week' => 5, 'day_name' => 'Jumat', 'is_work_day' => true, 'start_time' => '08:00:00', 'end_time' => '17:00:00'],
            ['day_of_week' => 6, 'day_name' => 'Sabtu', 'is_work_day' => false, 'start_time' => null, 'end_time' => null],
            ['day_of_week' => 7, 'day_name' => 'Minggu', 'is_work_day' => false, 'start_time' => null, 'end_time' => null],
        ];

        foreach ($days as $day) {
            DB::table('work_schedule_days')->insert(array_merge($day, [
                'work_schedule_id' => $scheduleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_schedule_days');
        Schema::dropIfExists('work_schedules');
    }
};

