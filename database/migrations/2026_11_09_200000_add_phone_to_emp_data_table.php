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
        Schema::table('emp_data', function (Blueprint $table) {
            if (!Schema::hasColumn('emp_data', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email')->comment('Nomor Handphone / WhatsApp Karyawan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_data', function (Blueprint $table) {
            if (Schema::hasColumn('emp_data', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};
