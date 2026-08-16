<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fair_luck_loss_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->bigInteger('contribution_bank')->default(0);
            $table->integer('jackpot_pity')->default(0);
            $table->integer('loss_momentum')->default(0);
            $table->decimal('loss_score', 14, 5)->default(0);
            $table->unsignedInteger('rotation_count')->default(0);
            $table->timestamp('cooldown_until')->nullable();
            $table->timestamp('last_high_multiplier_at')->nullable();
            $table->timestamps();

            $table->index(['cooldown_until', 'loss_score']);
            $table->index('rotation_count');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('fair_luck_loss_ledgers');
    }
};
