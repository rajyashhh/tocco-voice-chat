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
        Schema::create('super_admin_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('super_admin_id');
            $table->string('type');
            $table->string('target');
            $table->integer('expire')->nullable();
            $table->integer('no_reward');
            $table->integer('gave_reward_no')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_admin_rewards');
    }
};
