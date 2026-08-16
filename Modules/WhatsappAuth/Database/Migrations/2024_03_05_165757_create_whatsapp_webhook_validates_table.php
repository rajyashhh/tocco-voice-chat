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
        Schema::create('whatsapp_webhook_validates', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('verification_code');
            $table->string('phone_number')->nullable();
            $table->string('profile_name')->nullable();
            $table->string('status');
            $table->string('app_id');
            $table->DateTime('requested_at');
            $table->DateTime('expires_at');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_validates');
    }
};
