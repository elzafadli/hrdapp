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
        if (Schema::hasTable('payroll_results') && !Schema::hasColumn('payroll_results', 'status')) {
            Schema::table('payroll_results', function (Blueprint $table) {
                $table->string('status', 20)->default('draft')->after('type');
                $table->index(['emp_id', 'status'], 'idx_payroll_results_emp_status');
                $table->index(['month', 'year', 'status'], 'idx_payroll_results_period_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payroll_results') && Schema::hasColumn('payroll_results', 'status')) {
            Schema::table('payroll_results', function (Blueprint $table) {
                $table->dropIndex('idx_payroll_results_emp_status');
                $table->dropIndex('idx_payroll_results_period_status');
                $table->dropColumn('status');
            });
        }
    }
};
