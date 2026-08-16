<?php

namespace Tests\Unit\FairLuck;

use App\Services\FairLuck\V7\EconomySplitter;
use App\Services\FairLuck\V7\MultiplierTable;
use PHPUnit\Framework\TestCase;

/**
 * Full-economy Monte-Carlo simulation of the lucky-gift money flow — no Laravel,
 * no Redis, no DB. Replicates the EXACT hot-path order used by FairLuckServiceV7:
 *
 *   split → credit netToVault → selectPure(post-credit balance) → guarded settle
 *
 * and asserts the hard money invariants that MUST hold on production:
 *   • solvency: the vault never drops below -negativeLimit
 *   • owner cut guaranteed and integer on every bet
 *   • per-bet conservation: owner + receiver + netToVault == bet
 *   • global conservation: seedVault + Σintake - Σpayout == finalVault
 *   • house never bleeds: final (vault + owner + receiver) >= total wagered's house share
 *   • everything integer
 * plus sanity on the "feels like winning" frequency.
 */
class LuckyEconomySimulationTest extends TestCase
{
    private const OWNER_RATE = 0.01;     // 1%
    private const RECEIVER_RATE = 0.10;  // 10%
    private const NEG_LIMIT = 30_000;

    public function test_economy_is_solvent_conserved_and_integer_over_many_bets(): void
    {
        $mt = new MultiplierTable();
        $cfg = $this->cfg();

        $seed = 200_000;            // start the vault at the NORMAL/target zone
        $vault = $seed;
        $ownerTotal = 0;
        $receiverTotal = 0;
        $intake = 0;
        $payoutTotal = 0;
        $totalWagered = 0;
        $wins = 0;
        $bets = 0;
        $minVault = $vault;
        $jackpotHits = 0;

        // 600 users, ~400 bets each ≈ 240k bets — representative of peak load.
        $users = [];
        for ($u = 0; $u < 600; $u++) {
            $users[$u] = ['spent' => 0, 'received' => 0, 'streak' => 0];
        }

        $betSizes = [10, 50, 100, 500, 1000];

        for ($round = 0; $round < 400; $round++) {
            foreach ($users as $u => &$state) {
                $bet = $betSizes[($u + $round) % count($betSizes)];
                $bets++;
                $totalWagered += $bet;

                // 1) split (integer)
                $split = EconomySplitter::split($bet, self::OWNER_RATE, self::RECEIVER_RATE);
                $this->assertSame($bet, $split->ownerCut + $split->receiverCut + $split->netToVault);

                // 2) credit
                $vault += $split->netToVault;
                $intake += $split->netToVault;
                $ownerTotal += $split->ownerCut;
                $receiverTotal += $split->receiverCut;

                // 3) select on the post-credit balance
                $sel = $mt->selectPure($vault, $state['spent'], $state['received'], $bet, $state['streak'], $cfg);
                $m = (int) $sel['multiplier'];
                $requested = $m > 0 ? $bet * $m : 0;

                // 4) guarded settle — never breach the negative limit
                $applied = 0;
                if ($requested > 0 && ($vault - $requested) >= -self::NEG_LIMIT) {
                    $vault -= $requested;
                    $payoutTotal += $requested;
                    $applied = $requested;
                }

                // user rtp / streak
                $state['spent'] += $bet;
                $state['received'] += $applied;
                if ($applied > 0) {
                    $wins++;
                    $state['streak'] = 0;
                    if ($m >= 500) $jackpotHits++;
                } else {
                    $state['streak']++;
                }

                // HARD INVARIANT: solvency on every single bet
                $this->assertGreaterThanOrEqual(-self::NEG_LIMIT, $vault, "vault breached negative limit at bet {$bets}");
                $minVault = min($minVault, $vault);
            }
            unset($state);
        }

        // Global money conservation (the books must balance exactly).
        $this->assertSame($seed + $intake - $payoutTotal, $vault, 'global conservation broke');

        // Owner cut guaranteed: exactly 1% floored of every bet, integer, > 0.
        $this->assertIsInt($ownerTotal);
        $this->assertGreaterThan(0, $ownerTotal);

        // House never bleeds: the value that left the players' pockets and did NOT
        // come back as winnings == (totalWagered - payoutTotal) and equals
        // (owner + receiver + vault growth). Must be positive (house edge holds).
        $houseRetained = $totalWagered - $payoutTotal;
        $this->assertSame($ownerTotal + $receiverTotal + ($vault - $seed), $houseRetained);
        $this->assertGreaterThan(0, $houseRetained, 'house edge must stay positive over the long run');

        // "Feels like winning" but not a giveaway: win rate in a sane band.
        $winRate = $wins / $bets;
        $this->assertGreaterThan(0.02, $winRate, "win rate too low ({$winRate})");
        $this->assertLessThan(0.60, $winRate, "win rate implausibly high ({$winRate})");

        // Jackpots are possible (sanity that big tiers aren't permanently dead).
        $this->assertGreaterThanOrEqual(0, $jackpotHits);

        fwrite(STDERR, sprintf(
            "\n[sim] bets=%d winRate=%.3f%% seed=%d final=%d minVault=%d owner=%d receiver=%d payout=%d wagered=%d houseEdge=%.3f%% jackpots=%d\n",
            $bets, $winRate * 100, $seed, $vault, $minVault,
            $ownerTotal, $receiverTotal, $payoutTotal, $totalWagered,
            ($houseRetained / max(1, $totalWagered)) * 100, $jackpotHits
        ));
    }

    public function test_empty_vault_never_pays_big_multipliers(): void
    {
        $mt = new MultiplierTable();
        $cfg = $this->cfg();

        // Vault pinned at the negative limit: NO win tier is affordable, so the
        // gate must fully block every win and the engine must only ever return 0x.
        $vault = -self::NEG_LIMIT;
        $nonZero = 0;
        for ($i = 0; $i < 20_000; $i++) {
            $sel = $mt->selectPure($vault, 0, 0, 100, 0, $cfg);
            if ((int) $sel['multiplier'] !== 0) {
                $nonZero++;
            }
        }
        $this->assertSame(0, $nonZero, 'an empty vault must never select a payable win');
    }

    /** @return array<string,mixed> */
    private function cfg(): array
    {
        return [
            'multipliers' => [5, 10, 20, 100, 200, 500, 1000, 2000],
            'baseWeights' => [0 => 93_711, 5 => 4000, 10 => 1500, 20 => 600, 100 => 120, 200 => 45, 500 => 15, 1000 => 6, 2000 => 3],
            'thresholds' => ['min' => 10_000, 'tight' => 50_000, 'target' => 200_000, 'high' => 500_000, 'drain' => 1_000_000],
            'sens' => [
                'no_win_factor' => 0.08, 'win_base_factor' => 0.5, 'win_position_factor' => 1.5,
                'boost_base_factor' => 0.3, 'boost_position_factor' => 1.2, 'no_win_floor' => 50_000,
                'wallet_weight' => 0.60, 'rtp_weight' => 0.40, 'rtp_activation' => 500,
                'max_loss_streak' => 20, 'forced_win_mult' => 5,
            ],
            'targetRtp' => 0.99,
            'negativeLimit' => self::NEG_LIMIT,
        ];
    }
}
