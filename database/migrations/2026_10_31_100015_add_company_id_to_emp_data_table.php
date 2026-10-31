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
        if (Schema::hasTable('emp_data')) {
            Schema::table('emp_data', function (Blueprint $table) {
                if (!Schema::hasColumn('emp_data', 'company_id')) {
                    $table->foreignId('company_id')
                        ->nullable()
                        ->after('project_id')
                        ->constrained('companies')
                        ->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('emp_data') && Schema::hasColumn('emp_data', 'company_id')) {
            Schema::table('emp_data', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};

