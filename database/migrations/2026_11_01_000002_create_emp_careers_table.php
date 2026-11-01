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
        if (!Schema::hasTable('emp_careers')) {
            Schema::create('emp_careers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('emp_id')->constrained('emp_data')->onDelete('cascade');
                $table->string('status_karyawan', 50)->default('PKWT')->comment('PKWT, PKWTT, Probation, dll');
                $table->string('penempatan_kerja', 100)->default('Baru Direkrut')->comment('Baru Direkrut, Promosi, Mutasi, dll');
                $table->date('tanggal_efektif')->comment('Tanggal Efektif Karir');
                $table->string('organisasi', 100)->nullable()->comment('Organisasi / Divisi / Departemen (e.g. IT)');
                $table->string('pangkat', 100)->nullable()->comment('Pangkat (e.g. Staff, Supervisor, dll)');
                $table->string('jabatan', 100)->nullable()->comment('Jabatan (e.g. Back-end Developer Staff)');
                $table->boolean('is_current')->default(true)->comment('Apakah status karir saat ini');
                $table->text('notes')->nullable()->comment('Catatan tambahan karir');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_careers');
    }
};
