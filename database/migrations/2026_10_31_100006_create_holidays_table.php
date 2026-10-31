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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique()->comment('Tanggal hari libur');
            $table->string('name')->comment('Nama hari libur, contoh: Tahun Baru, Idul Fitri, Hari Kemerdekaan');
            $table->year('year')->comment('Tahun kalender untuk filtering');
            $table->boolean('is_cuti_bersama')->default(false)->comment('true jika merupakan cuti bersama pemerintah');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['year', 'holiday_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};

