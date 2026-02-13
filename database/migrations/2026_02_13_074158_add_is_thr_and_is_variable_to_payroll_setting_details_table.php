<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payroll_setting_details', function (Blueprint $table) {
            $table->boolean('is_thr')->default(false);
            $table->boolean('is_variable')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_setting_details', function (Blueprint $table) {
            $table->dropColumn(['is_thr', 'is_variable']);
        });
    }
};
