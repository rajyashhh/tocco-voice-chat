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
        Schema::table('user_luck_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_luck_profiles', 'medium_wallet_wins')) {
                $table->decimal('medium_wallet_wins', 15, 2)->default(0)->after('total_profit');
            }
            if (!Schema::hasColumn('user_luck_profiles', 'jackpot_wallet_wins')) {
                $table->decimal('jackpot_wallet_wins', 15, 2)->default(0)->after('medium_wallet_wins');
            }
        });

        // Add indexes separately to avoid errors if they already exist
        try {
            Schema::table('user_luck_profiles', function (Blueprint $table) {
                $table->index(['user_id', 'medium_wallet_wins']);
                $table->index(['user_id', 'jackpot_wallet_wins']);
            });
        } catch (\Exception $e) {
            // Indexes may already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_luck_profiles', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'medium_wallet_wins']);
            $table->dropIndex(['user_id', 'jackpot_wallet_wins']);
            $table->dropColumn(['medium_wallet_wins', 'jackpot_wallet_wins']);
        });
    }
};