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
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->nullable()->unique();
            $table->foreignId('emp_id')->constrained('emp_data')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Tanggal dan Waktu Lembur
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('break_minutes')->default(0)->comment('Durasi istirahat dalam menit');
            $table->decimal('total_hours', 5, 2)->default(0.0)->comment('Total jam lembur riil');
            $table->string('day_type', 30)->default('workday')->comment('workday, weekend, holiday');

            // Keterangan & Rincian Tugas
            $table->text('reason')->comment('Urgensi / alasan lembur');
            $table->text('task_description')->nullable()->comment('Rincian pekerjaan yang dilakukan saat lembur');

            // Status dan Alur Approval
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });

        Schema::create('overtime_request_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_request_id')->constrained('overtime_requests')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable()->comment('Ukuran file dalam bytes');
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_request_files');
        Schema::dropIfExists('overtime_requests');
    }
};

