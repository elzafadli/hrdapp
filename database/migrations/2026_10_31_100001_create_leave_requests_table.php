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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->nullable()->unique();
            $table->foreignId('emp_id')->constrained('emp_data')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('leave_type', 50)->default('annual')->comment('annual, special, unpaid, permit, sick');

            // Cuti Sehari Penuh
            $table->date('full_day_start_date')->nullable();
            $table->date('full_day_end_date')->nullable();
            $table->decimal('full_day_count', 5, 1)->default(0.0);

            // Cuti Setengah Hari
            $table->date('half_day_start_date')->nullable();
            $table->date('half_day_end_date')->nullable();
            $table->decimal('half_day_count', 5, 1)->default(0.0);
            $table->enum('half_day_type', ['first_half', 'second_half', 'all'])->nullable()->comment('first_half: pagi, second_half: siang');

            // Total hari cuti
            $table->decimal('total_days', 5, 1)->default(0.0);

            // Keterangan / Alasan
            $table->text('reason')->nullable();

            // Status dan Approval
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};

