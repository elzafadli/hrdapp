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
        if (Schema::hasTable('emp_loans')) {
            Schema::table('emp_loans', function (Blueprint $table) {
                if (!Schema::hasColumn('emp_loans', 'paid_amount')) {
                    $table->decimal('paid_amount', 15, 2)->default(0.00)->after('amount')->comment('Total nominal pinjaman yang sudah dibayar');
                }
                if (!Schema::hasColumn('emp_loans', 'remaining_amount')) {
                    $table->decimal('remaining_amount', 15, 2)->nullable()->after('paid_amount')->comment('Sisa saldo pinjaman saat ini');
                }
            });

            // Sinkronisasi data awal untuk existing records
            DB::statement("UPDATE emp_loans SET remaining_amount = amount WHERE remaining_amount IS NULL");
            DB::statement("UPDATE emp_loans SET paid_amount = GREATEST(0, amount - remaining_amount) WHERE paid_amount = 0 AND remaining_amount IS NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('emp_loans') && Schema::hasColumn('emp_loans', 'paid_amount')) {
            Schema::table('emp_loans', function (Blueprint $table) {
                $table->dropColumn('paid_amount');
            });
        }
    }
};

