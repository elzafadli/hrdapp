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
            if (!Schema::hasColumn('emp_loans', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('emp_id');
                $table->index('project_id');
            }

            if (!Schema::hasColumn('emp_loans', 'attachment')) {
                $table->string('attachment')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_loans', function (Blueprint $table) {
            if (Schema::hasColumn('emp_loans', 'attachment')) {
                $table->dropColumn('attachment');
            }

            if (Schema::hasColumn('emp_loans', 'project_id')) {
                $table->dropIndex(['project_id']);
                $table->dropColumn('project_id');
            }
        });
    }
};

