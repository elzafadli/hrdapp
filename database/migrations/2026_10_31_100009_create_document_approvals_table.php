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
        Schema::create('document_approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable'); // approvable_type & approvable_id (bisa untuk LeaveRequest, PermitRequest, SickRequest, dll)
            $table->string('module', 50)->comment('Nama modul, contoh: leave, permit, sick, dsb.');
            $table->unsignedTinyInteger('level')->default(1)->comment('Tingkat tahapan approval');
            $table->foreignId('jabatan_id')->constrained('emp_jabatan')->onDelete('cascade')->comment('Jabatan yang berhak approve pada level ini');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('User yang melakukan tindakan approval');
            $table->timestamp('action_at')->nullable()->comment('Waktu tindakan approval/rejection');
            $table->text('notes')->nullable()->comment('Catatan approval atau alasan penolakan');
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id', 'level', 'status'], 'idx_approvable_level_status');
            $table->index(['jabatan_id', 'status'], 'idx_jabatan_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_approvals');
    }
};

