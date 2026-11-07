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
        if (Schema::hasTable('leave_requests') && !Schema::hasColumn('leave_requests', 'substitute_emp_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->foreignId('substitute_emp_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('emp_data')
                    ->nullOnDelete()
                    ->comment('Karyawan pengganti / penanggung jawab tugas selama cuti');
            });
        }

        if (Schema::hasTable('document_approvals') && !Schema::hasColumn('document_approvals', 'approver_emp_id')) {
            Schema::table('document_approvals', function (Blueprint $table) {
                $table->foreignId('approver_emp_id')
                    ->nullable()
                    ->after('jabatan_id')
                    ->constrained('emp_data')
                    ->nullOnDelete()
                    ->comment('Spesifik karyawan yang ditunjuk sebagai approver (misal: karyawan pengganti level 1)');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('document_approvals') && Schema::hasColumn('document_approvals', 'approver_emp_id')) {
            Schema::table('document_approvals', function (Blueprint $table) {
                $table->dropForeign(['approver_emp_id']);
                $table->dropColumn('approver_emp_id');
            });
        }

        if (Schema::hasTable('leave_requests') && Schema::hasColumn('leave_requests', 'substitute_emp_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropForeign(['substitute_emp_id']);
                $table->dropColumn('substitute_emp_id');
            });
        }
    }
};
