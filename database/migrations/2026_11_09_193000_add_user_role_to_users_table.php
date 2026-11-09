<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            try {
                DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'project_manager', 'finance', 'cashier', 'admin_finance', 'user') NOT NULL DEFAULT 'user'");
            } catch (\Throwable $e) {
                // Fallback jika database bukan MySQL murni
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            try {
                DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'project_manager', 'finance', 'cashier', 'admin_finance') NOT NULL DEFAULT 'admin'");
            } catch (\Throwable $e) {
                // Fallback jika database bukan MySQL murni
            }
        }
    }
};

