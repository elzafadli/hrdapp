<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ess_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->string('event')->comment('submitted, next_level, completed, auto_completed, rejected');
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->text('message');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->longText('response_payload')->nullable();
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id'], 'ess_notif_approvable_index');
            $table->index('receiver_phone');
            $table->index('event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ess_notification_logs');
    }
};
