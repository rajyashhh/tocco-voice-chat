<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $jackpotBalance = DB::table('fair_luck_wallets')->where('wallet_type', 'jackpot_wallet')->value('balance') ?: 0;
        $mediumBalance = DB::table('fair_luck_wallets')->where('wallet_type', 'medium_wallet')->value('balance') ?: 0;

        // Add to global_vault
        DB::table('fair_luck_wallets')
            ->where('wallet_type', 'global_vault')
            ->increment('balance', $jackpotBalance + $mediumBalance);

        // Zero out others
        DB::table('fair_luck_wallets')->where('wallet_type', 'jackpot_wallet')->update(['balance' => 0]);
        DB::table('fair_luck_wallets')->where('wallet_type', 'medium_wallet')->update(['balance' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Difficult to reverse perfectly without keeping track of what was there, 
        // but we can just leave it as is or move everything back to global if needed.
    }
};
