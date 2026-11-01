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
        if (!Schema::hasTable('emp_trainings')) {
            Schema::create('emp_trainings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('emp_id')->constrained('emp_data')->onDelete('cascade');
                $table->string('nama_pelatihan', 255)->comment('Nama Pelatihan / Kursus / Keahlian');
                $table->string('penyelenggara', 255)->comment('Lembaga / Penyelenggara');
                $table->string('tahun', 20)->nullable()->comment('Tahun / Periode Pelaksanaan');
                $table->string('nomor_sertifikat', 100)->nullable()->comment('Nomor Sertifikat / Lisensi');
                $table->string('masa_berlaku', 50)->nullable()->comment('Masa Berlaku / Seumur Hidup');
                $table->text('deskripsi')->nullable()->comment('Deskripsi Keahlian / Catatan');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_trainings');
    }
};
