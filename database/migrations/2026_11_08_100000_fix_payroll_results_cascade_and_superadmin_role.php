<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambahkan role 'superadmin' ke kolom enum users.role jika belum ada
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            try {
                DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'project_manager', 'finance', 'cashier', 'admin_finance') NOT NULL DEFAULT 'admin'");
            } catch (\Throwable $e) {
                // Fallback jika database bukan MySQL murni
            }
        }

        // 2. Perbarui foreign key payroll_results_emp_id_foreign agar memiliki ON DELETE CASCADE
        if (Schema::hasTable('payroll_results')) {
            Schema::table('payroll_results', function (Blueprint $table) {
                try {
                    $table->dropForeign('payroll_results_emp_id_foreign');
                } catch (\Throwable $e) {
                    try {
                        $table->dropForeign(['emp_id']);
                    } catch (\Throwable $ex) {
                    }
                }

                $table->foreign('emp_id')
                    ->references('id')
                    ->on('emp_data')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payroll_results')) {
            Schema::table('payroll_results', function (Blueprint $table) {
                try {
                    $table->dropForeign('payroll_results_emp_id_foreign');
                } catch (\Throwable $e) {
                    try {
                        $table->dropForeign(['emp_id']);
                    } catch (\Throwable $ex) {
                    }
                }

                $table->foreign('emp_id')
                    ->references('id')
                    ->on('emp_data');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            try {
                DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'project_manager', 'finance', 'cashier', 'admin_finance') NOT NULL DEFAULT 'admin'");
            } catch (\Throwable $e) {
            }
        }
    }
};

