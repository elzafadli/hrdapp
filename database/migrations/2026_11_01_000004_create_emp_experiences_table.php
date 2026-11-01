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
        if (!Schema::hasTable('emp_experiences')) {
            Schema::create('emp_experiences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('emp_id')->constrained('emp_data')->onDelete('cascade');
                $table->string('perusahaan', 255)->comment('Nama Perusahaan / Tempat Kerja');
                $table->string('jabatan', 150)->comment('Jabatan / Posisi');
                $table->date('tanggal_mulai')->nullable()->comment('Tanggal Mulai');
                $table->date('tanggal_selesai')->nullable()->comment('Tanggal Selesai');
                $table->boolean('is_current')->default(false)->comment('Masih Aktif Bekerja');
                $table->string('gaji_terakhir', 50)->nullable()->comment('Gaji Terakhir (Opsional)');
                $table->text('deskripsi_pekerjaan')->nullable()->comment('Deskripsi Pekerjaan / Tanggung Jawab');
                $table->text('alasan_berhenti')->nullable()->comment('Alasan Berhenti / Pindah');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_experiences');
    }
};
