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
        Schema::create('user_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignId('child_user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->string('device_token')->nullable();
            $table->string('key')->unique()->nullable();
            $table->string('expire')->nullable();
            $table->boolean('is_change')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_accounts');
    }
};
