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
        Schema::create('payroll_results', function (Blueprint $table) {
            $table->id();
            $table->integer('month');
            $table->integer('year');
            $table->foreignId('emp_id')->constrained('emp_data');
            $table->foreignId('payroll_setting_id')->constrained('payroll_settings');
            $table->foreignId('payroll_component_id')->constrained('payroll_components');
            $table->decimal('amount', 15, 2);
            $table->string('type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_results');
    }
};
