<?php

namespace App\Services\FairLuck\V7;

/**
 * BeginnerProtection (V7) — pure, ZERO I/O, fully unit-testable.
 *
 * Two responsibilities, both side-effect free:
 *
 *  1. Eligibility (§4.1, owner decision 2026-06-13): a candidate qualifies for the
 *     beginner boost iff BOTH conditions hold — there is NO lifetime_spent gate
 *     anymore (it was droppable: the first-paid-charge gate already makes a fake
 *     account cost real money, which is the structural fix for B-ECO-1):
 *        (a) the account has at least one REAL paid top-up on record, and
 *        (b) the account age is below `beginner_max_age_days`.
 *     The PHP caller resolves the two booleans (from users.created_at + a charge
 *     flag) and passes the resolved facts; this class only ANDs them so the rule
 *     stays in one deterministic, testable place.
 *
 *  2. Result pairs (§4.3): for every hit the PHP side draws a *pair* of outcomes —
 *     one on the BOOSTED table and one on the NORMAL table — and the batchSettle
 *     EVAL picks between them per hit against the LIVE bp_remaining. The boosted
 *     table is the chosen profile's multipliers with the tail (every tier above
 *     ×100) removed, calibrated on `RTP_boost` (default 0.92) — a "frequent early
 *     win" experience, NOT a subsidised jackpot ticket (closes B-ECO-2). The
 *     selection math reuses MultiplierTable (same normalizeWeights / weighted draw)
 *     so there is one source of EV truth.
 *
 * The hash fields (bp_remaining / bp_state) are written by the EVAL ONLY (§5.3);
 * this class never touches Redis.
 */
final class BeginnerProtection
{
    /** Beginner boosted table excludes every tier strictly above this multiplier. */
    public const TAIL_CUTOFF_MULT = 100;

    /**
     * Eligibility = (real paid charge) AND (account younger than the age window).
     * Pure: the caller supplies the two resolved facts.
     */
    public static function isEligible(bool $hasRealPaidCharge, int $accountAgeDays, int $maxAgeDays): bool
    {
        if ($maxAgeDays <= 0) {
            return false;
        }

        return $hasRealPaidCharge && $accountAgeDays < $maxAgeDays;
    }

    /**
     * Build the boosted multipliers/weights from the active profile by dropping the
     * tail (everything above ×100). Pure function of the profile inputs.
     *
     * @param int[]            $profileMultipliers The active profile win tiers.
     * @param array<int,int>   $profileBaseWeights Base shape weights keyed by mult (0 = no-win).
     * @return array{multipliers:int[], baseWeights:array<int,int>}
     */
    public static function boostedShape(array $profileMultipliers, array $profileBaseWeights): array
    {
        $multipliers = [];
        foreach ($profileMultipliers as $mult) {
            if ($mult <= self::TAIL_CUTOFF_MULT) {
                $multipliers[] = (int) $mult;
            }
        }

        // Keep the 0x weight plus only the surviving win tiers' weights.
        $weights = [0 => (int) ($profileBaseWeights[0] ?? 0)];
        foreach ($multipliers as $mult) {
            $weights[$mult] = (int) ($profileBaseWeights[$mult] ?? 0);
        }

        return ['multipliers' => $multipliers, 'baseWeights' => $weights];
    }

    /**
     * Build the per-bet result PAIR fed to batchSettle: a boosted-table payout and a
     * normal-table payout. The EVAL chooses boosted while bp_remaining > 0, else
     * normal — so the choice is made against live, atomic budget state, and the PHP
     * draw stays advisory for the response (same philosophy as applied[i]).
     *
     * @param MultiplierTable $table     The (preloaded) table — used purely for selectPure.
     * @param array           $normalCfg The config the normal draw uses (buildSelectConfig()).
     * @param array           $boostedCfg The config the boosted draw uses (boosted shape + RTP_boost).
     * @param int             $grossBet  Gross bet for this hit (payout basis).
     * @param int             $vault     Live evolving vault (for the gate, same as the normal draw sees).
     * @param int             $spent     User lifetime spent (telemetry input).
     * @param int             $received  User lifetime received (telemetry input).
     * @param int             $streak    Consecutive losses (telemetry input).
     *
     * @return array{boostedMult:int, boostedPayout:int, normalMult:int, normalPayout:int}
     */
    public static function drawPair(
        MultiplierTable $table,
        array $normalCfg,
        array $boostedCfg,
        int $grossBet,
        int $vault,
        int $spent,
        int $received,
        int $streak
    ): array {
        $normal = $table->selectPure($vault, $spent, $received, $grossBet, $streak, $normalCfg);
        $boosted = $table->selectPure($vault, $spent, $received, $grossBet, $streak, $boostedCfg);

        $normalMult = (int) $normal['multiplier'];
        $boostedMult = (int) $boosted['multiplier'];

        return [
            'boostedMult'   => $boostedMult,
            'boostedPayout' => $boostedMult > 0 ? $grossBet * $boostedMult : 0,
            'normalMult'    => $normalMult,
            'normalPayout'  => $normalMult > 0 ? $grossBet * $normalMult : 0,
        ];
    }
}
