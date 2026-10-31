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
        Schema::create('emp_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emp_id')->constrained('emp_data')->onDelete('cascade');
            $table->foreignId('work_schedule_id')->constrained('work_schedules')->onDelete('cascade');
            $table->date('effective_date')->comment('Tanggal mulai berlaku jadwal ini');
            $table->date('end_date')->nullable()->comment('Tanggal berakhir jika berlaku sementara/shift');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['emp_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_schedules');
    }
};

