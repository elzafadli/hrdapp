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
        Schema::create('leave_request_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->onDelete('cascade');
            $table->foreignId('emp_id')->constrained('emp_data')->onDelete('cascade');
            $table->date('date')->comment('Tanggal spesifik hari kerja yang diambil untuk cuti');
            $table->enum('day_type', ['full_day', 'half_day'])->default('full_day')->comment('Tipe: sehari penuh (1.0) atau setengah hari (0.5)');
            $table->enum('half_day_type', ['first_half', 'second_half'])->nullable()->comment('first_half: pagi, second_half: siang');
            $table->decimal('deduction_value', 3, 1)->default(1.0)->comment('Nilai potong cuti: 1.0 atau 0.5');
            $table->string('notes')->nullable();
            $table->timestamps();

            // Index untuk pengecekan cepat bentrok/overlap cuti & lookup absensi
            $table->index(['emp_id', 'date']);
            $table->index(['leave_request_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_request_dates');
    }
};

