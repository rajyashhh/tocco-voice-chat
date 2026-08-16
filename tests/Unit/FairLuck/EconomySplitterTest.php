<?php

namespace Tests\Unit\FairLuck;

use App\Services\FairLuck\V7\EconomySplitter;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for the money split — no Laravel, no Redis, no DB.
 * The single most important property: ownerCut + receiverCut + netToVault === bet.
 */
class EconomySplitterTest extends TestCase
{
    public function test_brief_example_100_coins_1pct_owner_10pct_receiver(): void
    {
        $r = EconomySplitter::split(100, 0.01, 0.10);

        $this->assertSame(1, $r->ownerCut);
        $this->assertSame(10, $r->receiverCut);
        $this->assertSame(89, $r->netToVault);
    }

    public function test_conservation_invariant_holds_for_many_values_and_rates(): void
    {
        $rates = [
            [0.01, 0.10], [0.015, 0.10], [0.02, 0.05], [0.02, 0.20],
            [0.0, 0.0], [0.005, 0.123], [0.033, 0.077],
        ];

        foreach ($rates as [$owner, $receiver]) {
            for ($bet = 1; $bet <= 5000; $bet += 7) {
                $r = EconomySplitter::split($bet, $owner, $receiver);

                $this->assertSame(
                    $bet,
                    $r->ownerCut + $r->receiverCut + $r->netToVault,
                    "conservation broke at bet={$bet} owner={$owner} receiver={$receiver}"
                );
                $this->assertGreaterThanOrEqual(0, $r->ownerCut);
                $this->assertGreaterThanOrEqual(0, $r->receiverCut);
                $this->assertGreaterThanOrEqual(0, $r->netToVault);
            }
        }
    }

    public function test_remainder_always_stays_in_vault_never_overpays_cuts(): void
    {
        // 7 coins @ 1% owner, 10% receiver: floor(7*100/10000)=0 owner, floor(7*1000/10000)=0 receiver
        $r = EconomySplitter::split(7, 0.01, 0.10);
        $this->assertSame(0, $r->ownerCut);
        $this->assertSame(0, $r->receiverCut);
        $this->assertSame(7, $r->netToVault);
    }

    public function test_zero_and_negative_bet_yield_zero_split(): void
    {
        foreach ([0, -5, -100] as $bet) {
            $r = EconomySplitter::split($bet, 0.01, 0.10);
            $this->assertSame(0, $r->ownerCut);
            $this->assertSame(0, $r->receiverCut);
            $this->assertSame(0, $r->netToVault);
        }
    }

    public function test_misconfigured_rates_over_100pct_are_clamped_and_invariant_holds(): void
    {
        // owner 80% + receiver 50% = 130% → clamp so vault never goes negative.
        $r = EconomySplitter::split(1000, 0.80, 0.50);

        $this->assertSame(1000, $r->ownerCut + $r->receiverCut + $r->netToVault);
        $this->assertGreaterThanOrEqual(0, $r->netToVault);
        $this->assertSame(800, $r->ownerCut);   // owner keeps priority
        $this->assertSame(200, $r->receiverCut); // receiver takes the remaining room
        $this->assertSame(0, $r->netToVault);
    }

    public function test_all_amounts_are_integers(): void
    {
        $r = EconomySplitter::split(12345, 0.015, 0.10);
        $this->assertIsInt($r->ownerCut);
        $this->assertIsInt($r->receiverCut);
        $this->assertIsInt($r->netToVault);
    }
}
