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
        Schema::table('emp_loans', function (Blueprint $table) {
            if (!Schema::hasColumn('emp_loans', 'budget_id')) {
                $table->unsignedBigInteger('budget_id')->nullable()->after('status');
                $table->index('budget_id');
            }

            if (!Schema::hasColumn('emp_loans', 'budget_reference_number')) {
                $table->string('budget_reference_number', 100)->nullable()->after('budget_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_loans', function (Blueprint $table) {
            if (Schema::hasColumn('emp_loans', 'budget_reference_number')) {
                $table->dropColumn('budget_reference_number');
            }

            if (Schema::hasColumn('emp_loans', 'budget_id')) {
                $table->dropIndex(['budget_id']);
                $table->dropColumn('budget_id');
            }
        });
    }
};

