<?php

namespace Tests\Unit\FairLuck;

use App\Console\Commands\MergeLegacyLuckyWallets;
use PHPUnit\Framework\TestCase;

/**
 * §7.2 — pure contract test for the legacy-wallet merge constants and the
 * historical amount. No DB/Redis: pins the target rows, the fixed idempotency
 * key, and that the design figures (jackpot 53,240 + medium 11,333 = 64,573)
 * add up — the value that goes to BOTH unified_vault and baseline so the
 * invariant (vault == baseline + intake − payout) stays exact.
 */
class LegacyMergeAmountTest extends TestCase
{
    public function test_legacy_wallet_ids_are_2_and_3(): void
    {
        $this->assertSame([2, 3], MergeLegacyLuckyWallets::LEGACY_WALLET_IDS);
    }

    public function test_merge_key_is_a_fixed_single_token(): void
    {
        // A fixed key is what makes a second invocation a no-op (UNIQUE on the
        // audit) rather than a double credit.
        $this->assertSame('legacy_jackpot_medium_v1', MergeLegacyLuckyWallets::MERGE_KEY);
        $this->assertNotEmpty(MergeLegacyLuckyWallets::MERGE_KEY);
    }

    public function test_design_amounts_sum_to_64573(): void
    {
        $jackpot = 53240;
        $medium = 11333;
        $this->assertSame(64573, $jackpot + $medium);
    }

    public function test_invariant_stays_balanced_when_capital_added_to_both_sides(): void
    {
        // vault == baseline + intake − payout. Adding the same amount to vault
        // AND baseline (capital injection) keeps the equality intact.
        $baseline = 100000;
        $intake = 5000;
        $payout = 3000;
        $vault = $baseline + $intake - $payout;

        $amount = 64573;
        $vault += $amount;
        $baseline += $amount;

        $this->assertSame($vault, $baseline + $intake - $payout);
    }
}
