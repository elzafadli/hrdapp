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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emp_id')->constrained('emp_data')->onDelete('cascade');
            $table->date('date');

            // Presensi Masuk (Check-In)
            $table->time('check_in_time')->nullable();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->decimal('check_in_distance', 8, 2)->nullable()->comment('Jarak ke kantor dalam meter');
            $table->string('check_in_status', 20)->nullable()->comment('on_time, late');
            $table->text('check_in_notes')->nullable();

            // Presensi Keluar (Check-Out)
            $table->time('check_out_time')->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lng', 10, 7)->nullable();
            $table->decimal('check_out_distance', 8, 2)->nullable()->comment('Jarak ke kantor dalam meter');
            $table->string('check_out_status', 20)->nullable()->comment('normal, early_leave');
            $table->text('check_out_notes')->nullable();

            // Durasi dan Status Harian
            $table->integer('work_duration_minutes')->nullable()->comment('Durasi kerja riil dalam menit');
            $table->string('status', 20)->default('present')->comment('present, late, half_day, absent');

            $table->timestamps();

            $table->unique(['emp_id', 'date']);
        });

        // Pengaturan Lokasi Kantor & Radius Presensi
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('office_name')->default('Kantor Pusat');
            $table->decimal('latitude', 10, 7)->default(-6.2000000);
            $table->decimal('longitude', 10, 7)->default(106.8166660);
            $table->integer('radius_meters')->default(100)->comment('Batas radius presensi dalam meter');
            $table->boolean('allow_outside_radius')->default(true)->comment('Izinkan presensi di luar radius dengan catatan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
        Schema::dropIfExists('attendances');
    }
};

