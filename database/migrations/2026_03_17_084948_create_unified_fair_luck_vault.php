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
        // 1. Get balances from all existing wallets
        $globalBalance = DB::table('fair_luck_wallets')->where('wallet_type', 'global_vault')->value('balance') ?: 0;
        $jackpotBalance = DB::table('fair_luck_wallets')->where('wallet_type', 'jackpot_wallet')->value('balance') ?: 0;
        $mediumBalance = DB::table('fair_luck_wallets')->where('wallet_type', 'medium_wallet')->value('balance') ?: 0;

        $totalConsolidatedBalance = $globalBalance + $jackpotBalance + $mediumBalance;

        // 2. Create the new unified_vault record
        DB::table('fair_luck_wallets')->insert([
            'wallet_type' => 'unified_vault',
            'balance' => $totalConsolidatedBalance,
            'last_updated' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Zero out the old wallets
        DB::table('fair_luck_wallets')->whereIn('wallet_type', ['global_vault', 'jackpot_wallet', 'medium_wallet'])
            ->update([
                'balance' => 0,
                'updated_at' => now()
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Potential logic to move balance back to global_vault if needed
        DB::table('fair_luck_wallets')->where('wallet_type', 'unified_vault')->delete();
    }
};
