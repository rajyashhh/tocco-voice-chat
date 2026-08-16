<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('role_rewards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('role_id');
    
            $table->unsignedBigInteger('rewardable_id');
            $table->string('rewardable_type');
            $table->string('reward_achievement')->nullable();
            $table->enum('type', ['achievement', 'vip', 'ware', 'badge'])->nullable(false);
            $table->integer('expire')->default(0);
            $table->timestamps();

      });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_rewards');
    }
};
