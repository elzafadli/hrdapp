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
        if (!Schema::hasTable('emp_educations')) {
            Schema::create('emp_educations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('emp_id')->constrained('emp_data')->onDelete('cascade');
                $table->string('jenjang', 50)->comment('Jenjang Pendidikan: SMA/SMK, S1, S2, dll');
                $table->string('institusi', 255)->comment('Nama Institusi / Universitas / Sekolah');
                $table->string('jurusan', 100)->nullable()->comment('Jurusan / Program Studi');
                $table->string('tahun_masuk', 10)->nullable()->comment('Tahun Masuk');
                $table->string('tahun_lulus', 10)->nullable()->comment('Tahun Lulus');
                $table->string('nilai', 20)->nullable()->comment('IPK / Nilai Akhir');
                $table->text('notes')->nullable()->comment('Catatan Tambahan');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_educations');
    }
};

