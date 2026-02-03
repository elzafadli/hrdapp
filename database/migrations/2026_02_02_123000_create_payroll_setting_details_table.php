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
        Schema::create('payroll_setting_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_setting_id')->constrained('payroll_settings')->onDelete('cascade');
            $table->foreignId('payroll_component_id')->constrained('payroll_components')->onDelete('cascade');
            $table->decimal('base_amount', 12, 2)->nullable()->comment('Base amount for BPJS etc');
            $table->decimal('value', 5, 3)->nullable()->comment('Percentage value for BPJS etc');
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_setting_details');
    }
};
