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
                if (!Schema::hasColumn('emp_data', 'gender')) {
                    $table->enum('gender', ['male', 'female'])
                        ->default('male')
                        ->after('name')
                        ->comment('Jenis Kelamin: male (Laki-laki) / female (Perempuan)');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('emp_data') && Schema::hasColumn('emp_data', 'gender')) {
            Schema::table('emp_data', function (Blueprint $table) {
                $table->dropColumn('gender');
            });
        }
    }
};

