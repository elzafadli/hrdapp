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
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->unsignedBigInteger('payroll_reference')->nullable()->comment('Self reference for grouping or hierarchy');
            $table->foreign('payroll_reference')->references('id')->on('payroll_components')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_components', function (Blueprint $table) {
            // It's good practice to drop the foreign key first, usually 'table_column_foreign'
            $table->dropForeign(['payroll_reference']);
            $table->dropColumn('payroll_reference');
        });
    }
};
