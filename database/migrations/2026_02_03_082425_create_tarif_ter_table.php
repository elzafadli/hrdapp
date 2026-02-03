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
        Schema::create('tarif_ter', function (Blueprint $table) {
            $table->id();
            $table->enum('status_ptkp', ['TK0', 'TK1', 'TK2', 'TK3', 'K0', 'K1', 'K2', 'K3']);
            $table->decimal('penghasilan_min', 15, 2);
            $table->decimal('penghasilan_max', 15, 2);
            $table->decimal('tarif_ter', 5, 2)->comment('persen, misal 0.25 = 0.25%');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarif_ter');
    }
};
