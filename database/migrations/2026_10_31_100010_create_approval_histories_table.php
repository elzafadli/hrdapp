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
        Schema::create('approval_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable'); // approvable_type & approvable_id
            $table->string('module', 50)->comment('Nama modul, contoh: leave, permit, sick, dsb.');
            $table->unsignedTinyInteger('level')->nullable()->comment('Level approval saat aksi dilakukan');
            $table->foreignId('jabatan_id')->nullable()->constrained('emp_jabatan')->nullOnDelete()->comment('Jabatan approver');
            $table->string('action', 50)->comment('Jenis aksi: submitted, approved, rejected, resubmitted, cancelled, auto_completed');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('User yang melakukan tindakan');
            $table->text('notes')->nullable()->comment('Catatan approval, alasan penolakan, dsb.');
            $table->json('metadata')->nullable()->comment('Snapshot data atau informasi tambahan');
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id', 'created_at'], 'idx_approvable_history');
            $table->index(['user_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_histories');
    }
};

