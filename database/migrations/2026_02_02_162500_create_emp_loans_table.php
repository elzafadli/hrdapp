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
        Schema::create('emp_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emp_id')->constrained('emp_data')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->decimal('installment_amount', 15, 2)->nullable();
            $table->date('loan_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('description')->nullable();
            $table->string('status')->default('open')->comment('open, closed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_loans');
    }
};
