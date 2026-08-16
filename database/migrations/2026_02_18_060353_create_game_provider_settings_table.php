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
        Schema::create('game_provider_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider_code')->unique();
            $table->string('provider_name');
            $table->string('app_key');
            $table->string('app_secret')->nullable();
            $table->string('base_url')->nullable();
            $table->string('callback_url')->nullable();
            $table->json('extra_settings')->nullable();
            $table->json('webhook_routes')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_provider_settings');
    }
};
