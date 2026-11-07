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
        Schema::table('emp_loans', function (Blueprint $table) {
            if (!Schema::hasColumn('emp_loans', 'remaining_amount')) {
                $table->decimal('remaining_amount', 15, 2)->nullable()->after('amount')->comment('Sisa saldo pinjaman saat ini');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_loans', function (Blueprint $table) {
            if (Schema::hasColumn('emp_loans', 'remaining_amount')) {
                $table->dropColumn('remaining_amount');
            }
        });
    }
};

