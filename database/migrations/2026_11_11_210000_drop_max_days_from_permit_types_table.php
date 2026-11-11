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
        if (Schema::hasTable('permit_types') && Schema::hasColumn('permit_types', 'max_days')) {
            Schema::table('permit_types', function (Blueprint $table) {
                $table->dropColumn('max_days');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('permit_types') && !Schema::hasColumn('permit_types', 'max_days')) {
            Schema::table('permit_types', function (Blueprint $table) {
                $table->decimal('max_days', 5, 1)->nullable()->after('code');
            });
        }
    }
};

