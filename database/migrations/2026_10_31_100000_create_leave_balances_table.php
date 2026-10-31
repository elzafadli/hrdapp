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
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emp_id')->constrained('emp_data')->onDelete('cascade');
            $table->year('year');
            $table->decimal('total_leave', 5, 1)->default(12.0)->comment('Total hak cuti tahunan');
            $table->decimal('used_leave', 5, 1)->default(0.0)->comment('Total cuti yang sudah dipakai');
            $table->decimal('remaining_leave', 5, 1)->default(12.0)->comment('Sisa cuti yang tersedia');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['emp_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};

