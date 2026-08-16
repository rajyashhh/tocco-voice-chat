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
        Schema::create('user_luck_profiles', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $blueprint->decimal('total_bets', 15, 2)->default(0);
            $blueprint->decimal('total_profit', 15, 2)->default(0);
            $blueprint->unsignedInteger('bet_count')->default(0);
            $blueprint->unsignedInteger('win_count')->default(0);
            $blueprint->timestamp('first_bet_at')->nullable()->index();
            $blueprint->boolean('is_legacy_user')->default(false)->index();
            $blueprint->timestamp('beginner_protection_ends_at')->nullable()->index();
            $blueprint->decimal('current_deviation', 10, 6)->default(0);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_luck_profiles');
    }
};
