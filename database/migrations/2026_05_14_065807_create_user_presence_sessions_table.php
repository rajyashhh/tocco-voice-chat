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
        Schema::create('user_presence_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();

            $table->timestamp('connected_at');
            $table->timestamp('disconnected_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();

            $table->string('device_type')->nullable(); // mobile, web, desktop
            $table->string('app_version')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'connected_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_presence_sessions');
    }
};
