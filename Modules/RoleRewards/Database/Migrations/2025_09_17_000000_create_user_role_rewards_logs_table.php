<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_history_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('receive_type', 150); 
            $table->unsignedBigInteger('rewardable_id');
            $table->string('rewardable_type', 150);
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'receive_type', 'rewardable_id', 'rewardable_type'], 'uniq_user_rewards');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_history_rewards');
    }
};
