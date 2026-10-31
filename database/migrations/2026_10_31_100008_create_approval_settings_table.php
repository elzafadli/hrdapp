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
        Schema::create('approval_settings', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50)->comment('Nama modul pengajuan, contoh: leave, permit, sick, overtime, dsb.');
            $table->unsignedTinyInteger('level')->default(1)->comment('Tingkat approval: 1, 2, dst.');
            $table->foreignId('jabatan_id')->constrained('emp_jabatan')->onDelete('cascade')->comment('Jabatan yang berhak approve pada level ini');
            $table->string('description')->nullable()->comment('Keterangan level, contoh: Approval Supervisor, Approval HRD');
            $table->unsignedTinyInteger('min_approvers')->default(1)->comment('Jumlah minimal approver dengan jabatan ini (default: 1 cukup salah satu)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['module', 'level', 'jabatan_id'], 'unique_module_level_jabatan');
            $table->index(['module', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_settings');
    }
};

