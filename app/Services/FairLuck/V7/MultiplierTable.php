<?php

namespace App\Services\FairLuck\V7;

use App\Models\FairLuckSetting;

/**
 * MultiplierTable V7: single-step weighted selection engine (pure, no I/O on the
 * pure paths).
 *
 * PANEL CONTRACT: the ONLY owner-entered EV inputs are the three panel keys — app
 * profit (owner cut), receiver cut and target RTP. Everything else is a code
 * constant (table SHAPE, pity) or derived. The financial anchor is RTP_eff:
 *
 *   RTP_eff = min(panelRTP, 1 − owner − receiver) − pityEV         (no drift margin)
 *
 * normalizeWeights() calibrates the table so EV(payout) == RTP_eff × bet, with the
 * rounding done by FLOOR on every win tier and the remainder absorbed by the 0x
 * tier — so the rounding bias is ALWAYS ≤ 0 (in the house's favour) BY
 * CONSTRUCTION (S-ACC-4). That removed the need for RTP_DRIFT_MARGIN, which is
 * gone.
 *
 * ADAPTIVE ENGINE (§2.3.1): the table is NOT fixed-shape per draw. Modulators
 * (vault health, per-user RTP, peak hours, room activity) are injected as
 * weight MULTIPLIERS on the SHAPE *before* normalizeWeights re-anchors EV to
 * RTP_eff — so a modulator can change the variance / timing of wins but can NEVER
 * lift realised RTP above the panel. All modulator ranges/toggles are panel
 * values (zero hardcode).
 *
 * PROPORTIONAL REDISTRIBUTION (§2.3): the old jackpot gate removed blocked-tier EV
 * with no compensation (it taxed big bettors at 50–65% effective RTP). It is
 * replaced by applyGateWithRedistribution(): tiers the vault cannot pay are zeroed,
 * then the surviving win tiers are re-scaled so EV returns to targetRtp exactly
 * (the 0x tier absorbs the rest). The guarantee is EV-equality, NOT
 * distribution-equality (S-ACC-3): when tiers are blocked the SHAPE changes
 * (lower variance) but the EV is preserved while the vault can sustain it.
 *
 * SOLVENCY TAPER (§2.3 B-ECO-3): when vault < SOLVENCY_TAPER_FACTOR × |limit|,
 * targetRtp tapers linearly toward TAPER_FLOOR_FRACTION of itself at the limit — a
 * documented, panel-gated deviation from "honest RTP" in the near-bankruptcy zone
 * only.
 *
 * Octane-safe: no static mutable state, random_int(), settings cached per instance.
 */
class MultiplierTable
{
    /**
     * Active profile = "C" (§3, owner-recommended). Win-tier multipliers — code
     * constant; SHAPE only, EV comes from RTP_eff. Tail ×2000 keeps the jackpot
     * identity; ×100-and-below are the frequent-win tiers.
     */
    public const DEFAULT_MULTIPLIERS = [2, 5, 20, 100, 500, 2000];

    /**
     * Profile-C base weights (sum = 100,000) — SHAPE only ("wins ~1 in 9.5,
     * mostly ×2/×5, ×2000 ~1 in 24k wins"). normalizeWeights() forces EV onto
     * RTP_eff regardless. Win-tier shares per §3:
     *   ×2 66.1% · ×5 25.7% · ×20 6.6% · ×100 1.3% · ×500 0.29% · ×2000 0.04%.
     */
    public const DEFAULT_BASE_WEIGHTS = [
        0    => 89_470,   // 0x absorbs the no-win mass (~89.47%, hit-rate ~10.53%)
        2    => 6_960,
        5    => 2_707,
        20   => 695,
        100  => 137,
        500  => 30,
        2000 => 1,
    ];

    /** Forced small win after this many consecutive losses (streak v2 ramp base). */
    public const MAX_LOSS_STREAK = 30;

    /** Hard streak floor: a forced win is guaranteed at this consecutive-loss count. */
    public const STREAK_HARD_FLOOR = 70;

    /** Lifetime wagered coins before the per-user RTP telemetry/modulator activates. */
    public const RTP_ACTIVATION = 500;

    /**
     * Solvency taper (B-ECO-3): below SOLVENCY_TAPER_FACTOR × |negativeLimit| the
     * targetRtp tapers linearly to TAPER_FLOOR_FRACTION of itself when the vault
     * touches the negative limit. Code constants (the toggle is a panel value).
     */
    public const SOLVENCY_TAPER_FACTOR = 3.0;
    public const TAPER_FLOOR_FRACTION  = 0.70;

    /** Vault zone thresholds — TELEMETRY ONLY (zone labels) + modulator anchors. */
    private const ZONE_THRESHOLDS = [
        'min'    => 10_000,
        'tight'  => 50_000,
        'target' => 200_000,
        'high'   => 500_000,
        'drain'  => 1_000_000,
    ];

    /** Cached per instance — safe for Octane (bind, not singleton). */
    private ?float $cachedEffectiveRtp = null;

    public function preload(): self
    {
        $this->cachedEffectiveRtp = $this->effectiveTargetRtp();
        return $this;
    }

    public function multipliers(): array
    {
        return self::DEFAULT_MULTIPLIERS;
    }

    private function getThresholds(): array
    {
        return self::ZONE_THRESHOLDS;
    }

    private function getBaseWeights(): array
    {
        return self::DEFAULT_BASE_WEIGHTS;
    }

    /**
     * The EFFECTIVE table RTP used for every draw — panel RTP clamped to the
     * sustainable ceiling minus the pity EV. Single source of truth.
     */
    public function effectiveTargetRtp(): float
    {
        return $this->cachedEffectiveRtp ??= self::effectiveTargetRtpPure(
            (float) FairLuckSetting::getTargetRTP(),
            FairLuckSetting::getOwnerFeeRate(),
            FairLuckSetting::getReceiverFeeRate(),
            self::DEFAULT_BASE_WEIGHTS,
            self::DEFAULT_MULTIPLIERS
        );
    }

    /**
     * Pure RTP_eff derivation (unit-testable):
     *
     *   sustainable = 1 − owner − receiver
     *   T₀          = min(panelRtp, sustainable)
     *   T           = T₀ − pityEV(T)            (fixed point, 3 iterations)
     *   RTP_eff     = max(0, T)                 (NO drift margin — floor-bias ≤ 0)
     */
    public static function effectiveTargetRtpPure(
        float $panelRtp,
        float $ownerRate,
        float $receiverRate,
        array $baseWeights,
        array $multipliers
    ): float {
        $sustainable = max(0.0, 1.0 - $ownerRate - $receiverRate);
        $ceiling = max(0.0, min($panelRtp, $sustainable));

        $table = new self();
        $t = $ceiling;
        for ($i = 0; $i < 3; $i++) {
            $weights = $table->normalizeWeights($baseWeights, $multipliers, $t);
            $total = array_sum($weights);
            $p0 = $total > 0 ? ($weights[0] ?? 0) / $total : 1.0;
            // Streak-v2 forced wins use a dynamic floor tier (smallest multiplier);
            // value the pity EV at the smallest configured multiplier (conservative).
            $smallestMult = !empty($multipliers) ? (float) min($multipliers) : 0.0;
            // Budget the pity EV at the SAME streak length the EVAL actually fires
            // the forced floor (STREAK_HARD_FLOOR), not MAX_LOSS_STREAK. They were
            // decoupled (30 vs 70 = ~88x), so the budget over-reserved frequency
            // and silently shaved ordinary-draw RTP. Aligning them removes the
            // drift and returns the over-shaved sliver to players.
            $pityEv = self::pityRate($p0, self::STREAK_HARD_FLOOR)
                * max(0.0, $smallestMult - $t);
            $t = max(0.0, $ceiling - $pityEv);
        }

        return round($t, 6);
    }

    /**
     * Stationary per-bet rate of forced (pity) wins for loss probability $p0 and
     * streak threshold $streak: π(S) = p0^S(1−p0)/(1−p0^(S+1)).
     */
    public static function pityRate(float $p0, int $streak): float
    {
        if ($p0 <= 0.0) {
            return 0.0;
        }
        if ($p0 >= 1.0) {
            return 1.0 / max(1, $streak + 1);
        }
        $num = ($p0 ** $streak) * (1.0 - $p0);
        $den = 1.0 - ($p0 ** ($streak + 1));

        return $den > 0.0 ? $num / $den : 0.0;
    }

    /**
     * Select a multiplier via single-step weighted random selection.
     *
     * @return array{multiplier:int, walletFactor:float, rtpFactor:float, walletZone:string, jackpotGateFired:bool, forcedWin:bool}
     */
    public function select(
        int $luckyBalance,
        int $userTotalBet,
        int $userTotalReturned,
        int $grossBet,
        int $currentLossStreak = 0
    ): array {
        return $this->selectPure(
            $luckyBalance,
            $userTotalBet,
            $userTotalReturned,
            $grossBet,
            $currentLossStreak,
            $this->buildSelectConfig()
        );
    }

    /**
     * Build the config select() passes to selectPure(). Resolved ONCE per combo so
     * the batch path can call selectPure() N times in-memory (evolving vault/RTP/
     * streak) with byte-for-byte identical weights/thresholds/RTP.
     *
     * NOTE: normalizedWeights is NOT precomputed here — the adaptive engine and the
     * proportional redistribution both depend on the live vault/user state, so the
     * final weights are computed per draw inside selectPure(). The base SHAPE,
     * tiers, thresholds, targetRtp and modulator ranges are hoisted.
     */
    public function buildSelectConfig(): array
    {
        return [
            'multipliers'   => $this->multipliers(),
            'baseWeights'   => $this->getBaseWeights(),
            'thresholds'    => $this->getThresholds(),
            'targetRtp'     => $this->effectiveTargetRtp(),
            'negativeLimit' => FairLuckSetting::getVaultNegativeLimit(),
            'modulators'    => $this->resolveModulators(),
        ];
    }

    /**
     * Build a beginner-boosted config (§4.2): the boosted shape (tail dropped) with
     * RTP_boost as the anchor. Modulators are intentionally NOT applied — the boost
     * is a fixed frequent-win shape so the marketing cost is bounded.
     *
     * @param array{multipliers:int[], baseWeights:array<int,int>} $boostedShape
     */
    public function buildBoostedConfig(array $boostedShape, float $rtpBoost): array
    {
        return [
            'multipliers'   => $boostedShape['multipliers'],
            'baseWeights'   => $boostedShape['baseWeights'],
            'thresholds'    => $this->getThresholds(),
            'targetRtp'     => $rtpBoost,
            'negativeLimit' => FairLuckSetting::getVaultNegativeLimit(),
            'modulators'    => [],
        ];
    }

    /**
     * Resolve the adaptive modulator config from the panel (zero hardcode). Each is
     * on/off + a range; OFF leaves the SHAPE untouched. Read once per combo.
     *
     * @return array<string,mixed>
     */
    private function resolveModulators(): array
    {
        return [
            'wallet_enabled'       => (bool) FairLuckSetting::getByKey('mod_wallet_enabled', false),
            'wallet_strength'      => (float) FairLuckSetting::getByKey('mod_wallet_strength', 0.0),
            'rtp_enabled'          => (bool) FairLuckSetting::getByKey('mod_rtp_enabled', false),
            'rtp_strength'         => (float) FairLuckSetting::getByKey('mod_rtp_strength', 0.0),
            'peak_enabled'         => (bool) FairLuckSetting::getByKey('mod_peak_enabled', false),
            'peak_strength'        => (float) FairLuckSetting::getByKey('mod_peak_strength', 0.0),
            'peak_active'          => (bool) FairLuckSetting::getByKey('mod_peak_active', false),
            'room_activity_enabled'=> (bool) FairLuckSetting::getByKey('mod_room_activity_enabled', false),
            'room_activity_factor' => (float) FairLuckSetting::getByKey('mod_room_activity_factor', 0.0),
            'taper_enabled'        => (bool) FairLuckSetting::getByKey('solvency_taper_enabled', false),
        ];
    }

    /**
     * Pure selection pipeline — takes ALL config explicitly (no settings/cache/DB
     * reads) so it is fully unit-testable and deterministic when an `rng` is given.
     *
     * @param array $cfg multipliers, baseWeights, thresholds, targetRtp,
     *                    negativeLimit, [modulators], [rng], [sens]
     * @return array{multiplier:int, walletFactor:float, rtpFactor:float, walletZone:string, jackpotGateFired:bool, forcedWin:bool}
     */
    public function selectPure(
        int $luckyBalance,
        int $userTotalBet,
        int $userTotalReturned,
        int $grossBet,
        int $currentLossStreak,
        array $cfg
    ): array {
        $multipliers   = $cfg['multipliers'];
        $baseWeights   = $cfg['baseWeights'];
        $thresholds    = $cfg['thresholds'];
        $negativeLimit = (int) $cfg['negativeLimit'];
        $targetRtp     = (float) ($cfg['targetRtp'] ?? 0.89);
        $modulators    = $cfg['modulators'] ?? [];
        $rng           = $cfg['rng'] ?? null;

        // Telemetry factors (also drive the adaptive modulators below).
        $wFactor = $this->walletFactor($luckyBalance, $thresholds);
        $currentRTP = $userTotalBet > 0 ? $userTotalReturned / $userTotalBet : 0.0;
        $rFactor = $this->rtpFactorPure($userTotalBet, $currentRTP, $cfg['sens'] ?? [], $targetRtp);

        // 1. Apply adaptive modulators to the SHAPE (variance/timing only). Re-anchored
        //    to targetRtp by normalizeWeights below, so EV cannot rise above the panel.
        $shape = $this->applyModulators($baseWeights, $multipliers, $modulators, $wFactor, $rFactor, $userTotalBet);

        // 2. Solvency taper (B-ECO-3): in the near-bankruptcy zone reduce the EV anchor.
        $effectiveTarget = $this->applySolvencyTaper($targetRtp, $luckyBalance, $negativeLimit, $modulators);

        // 3. Proportional gate + redistribution (§2.3): block unaffordable tiers, then
        //    re-anchor the survivors to the (possibly tapered) EV target. EV-equality,
        //    not distribution-equality.
        [$weights, $gateFired] = $this->applyGateWithRedistribution(
            $shape, $multipliers, $luckyBalance, $grossBet, $negativeLimit, $effectiveTarget
        );

        // 4. Streak protection v2 — handled in the EVAL for the batch path; here it is
        //    the forced-win floor used by the legacy single-bet select(). The ramp/
        //    floor sizing on the streak's average bet lives in the EVAL (§2.4); this
        //    pure path keeps the smallest-affordable forced win as a safe baseline.
        $maxLossStreak = (int) ($cfg['sens']['max_loss_streak'] ?? self::STREAK_HARD_FLOOR);
        if ($maxLossStreak > 0 && $currentLossStreak >= $maxLossStreak) {
            $forced = $this->smallestAffordableTier($multipliers, $luckyBalance, $grossBet, $negativeLimit);
            if ($forced > 0) {
                return [
                    'multiplier'       => $forced,
                    'walletFactor'     => round($wFactor, 4),
                    'rtpFactor'        => round($rFactor, 4),
                    'walletZone'       => $this->getZone($luckyBalance, $thresholds),
                    'jackpotGateFired' => $gateFired,
                    'forcedWin'        => true,
                ];
            }
        }

        // 5. Weighted selection.
        $multiplier = $this->weightedSelect($weights, $rng);

        return [
            'multiplier'       => $multiplier,
            'walletFactor'     => round($wFactor, 4),
            'rtpFactor'        => round($rFactor, 4),
            'walletZone'       => $this->getZone($luckyBalance, $thresholds),
            'jackpotGateFired' => $gateFired,
            'forcedWin'        => false,
        ];
    }

    /**
     * Inject the adaptive modulators as multipliers on the win-tier SHAPE weights
     * (§2.3.1). Pure: each factor is bounded and applied BEFORE normalization, so it
     * only reshapes variance/timing — normalizeWeights re-anchors EV to RTP_eff.
     *
     * @param array<int,int> $baseWeights
     * @param int[]          $multipliers
     * @param array<string,mixed> $mod
     * @return array<int,float> reshaped weights keyed by multiplier (0 = no-win)
     */
    private function applyModulators(array $baseWeights, array $multipliers, array $mod, float $wFactor, float $rFactor, int $userTotalBet): array
    {
        $out = [0 => (float) ($baseWeights[0] ?? 0)];
        foreach ($multipliers as $m) {
            $out[$m] = (float) ($baseWeights[$m] ?? 0);
        }
        if (empty($mod)) {
            return $out;
        }

        $median = $this->medianMultiplier($multipliers);

        foreach ($multipliers as $m) {
            $factor = 1.0;

            // Vault health: full vault → heavier big tiers; empty → heavier small tiers.
            if (!empty($mod['wallet_enabled'])) {
                $strength = max(0.0, (float) ($mod['wallet_strength'] ?? 0.0));
                $dir = $m >= $median ? 1.0 : -1.0;
                $factor *= 1.0 + $strength * $wFactor * $dir;
            }

            // Per-user RTP: under target → nudge toward wins; over target → soothe.
            if (!empty($mod['rtp_enabled']) && $userTotalBet >= self::RTP_ACTIVATION) {
                $strength = max(0.0, (float) ($mod['rtp_strength'] ?? 0.0));
                // rFactor>0 means below target (deserves a nudge); lift small-mid tiers.
                $dir = $m <= $median ? 1.0 : -1.0;
                $factor *= 1.0 + $strength * $rFactor * $dir;
            }

            // Peak hours: lift mid tiers (more exciting shape) when active.
            if (!empty($mod['peak_enabled']) && !empty($mod['peak_active'])) {
                $strength = max(0.0, (float) ($mod['peak_strength'] ?? 0.0));
                if ($m > min($multipliers) && $m <= $median) {
                    $factor *= 1.0 + $strength;
                }
            }

            // Room activity: hotter room → higher-excitement shape (panel factor).
            if (!empty($mod['room_activity_enabled'])) {
                $activity = max(0.0, (float) ($mod['room_activity_factor'] ?? 0.0));
                if ($m >= $median) {
                    $factor *= 1.0 + $activity;
                }
            }

            $out[$m] = max(0.0, $out[$m] * $factor);
        }

        return $out;
    }

    /**
     * Apply the solvency taper to the EV target (B-ECO-3). Panel-gated; OFF returns
     * the target unchanged.
     */
    public function applySolvencyTaper(float $targetRtp, int $luckyBalance, int $negativeLimit, array $mod): float
    {
        if (empty($mod['taper_enabled']) || $negativeLimit <= 0) {
            return $targetRtp;
        }

        $threshold = (int) round(self::SOLVENCY_TAPER_FACTOR * $negativeLimit);
        if ($luckyBalance >= $threshold) {
            return $targetRtp;
        }

        // Linear from full target at the threshold down to TAPER_FLOOR_FRACTION at
        // the negative limit (-negativeLimit). Clamp the span to [floorFrac, 1].
        $span = $threshold - (-$negativeLimit);
        if ($span <= 0) {
            return $targetRtp * self::TAPER_FLOOR_FRACTION;
        }
        $pos = ($luckyBalance - (-$negativeLimit)) / $span; // 1 at threshold, 0 at -limit
        $pos = max(0.0, min(1.0, $pos));
        $frac = self::TAPER_FLOOR_FRACTION + (1.0 - self::TAPER_FLOOR_FRACTION) * $pos;

        return $targetRtp * $frac;
    }

    /**
     * Proportional gate + redistribution (§2.3), replaces applyJackpotGate.
     *
     * 1. Zero the weight of any tier whose payout (grossBet × mult) would push the
     *    vault past the negative limit (solvency, unchanged).
     * 2. If survivors remain, re-anchor them to targetRtp:
     *        k' = targetRtp × T / Σ_available(mult_i × w_i)
     *    so EV returns to targetRtp exactly; the 0x tier absorbs the rest. k' is
     *    capped so the 0x weight floors at 0 (max achievable RTP, never fabricated).
     * 3. All win tiers blocked → 0x only (solvency floor).
     *
     * Rounding uses FLOOR on win tiers + remainder to 0x → bias ≤ 0 (S-ACC-4).
     *
     * @param array<int,float> $shape   Modulated SHAPE weights keyed by mult (0 = no-win).
     * @param int[]            $multipliers
     * @return array{0:array<int,int>, 1:bool} [normalised int weights, gateFired]
     */
    public function applyGateWithRedistribution(array $shape, array $multipliers, int $luckyBalance, int $grossBet, int $negativeLimit, float $targetRtp): array
    {
        $targetRtp = max(0.0, $targetRtp);
        $precision = 100_000;

        // Fixed total reference (0x + all win-tier shape weight) — preserved so the
        // realised hit-rate shape stays stable across redistribution.
        $total = (float) ($shape[0] ?? 0);
        foreach ($multipliers as $m) {
            $total += (float) ($shape[$m] ?? 0);
        }

        // 1. Solvency gate.
        $available = [];
        $gateFired = false;
        $expectedWin = 0.0;
        foreach ($multipliers as $m) {
            $w = (float) ($shape[$m] ?? 0);
            if ($w <= 0) {
                continue;
            }
            $worstCasePayout = $grossBet * $m;
            if (($luckyBalance - $worstCasePayout) < -$negativeLimit) {
                $gateFired = true;
                continue; // blocked
            }
            $available[$m] = $w;
            $expectedWin += $m * $w;
        }

        // 3. Everything blocked or degenerate → 0x only.
        if ($total <= 0 || $expectedWin <= 0 || empty($available)) {
            return [[0 => (int) round($total * $precision)], $gateFired];
        }

        // 2. Re-anchor survivors to targetRtp; cap so 0x ≥ 0.
        $k = ($targetRtp * $total) / $expectedWin;
        $availSum = array_sum($available);
        if ($availSum > 0) {
            $kMax = $total / $availSum;
            if ($k > $kMax) {
                $k = $kMax;
            }
        }

        // FLOOR every win tier (bias ≤ 0); 0x absorbs the exact integer remainder.
        $out = [];
        $scaledWinSum = 0;
        foreach ($available as $m => $w) {
            $scaled = $w * $k * $precision;
            $iw = (int) floor($scaled);
            $out[$m] = $iw;
            $scaledWinSum += $iw;
        }
        $out[0] = max(0, (int) round($total * $precision) - $scaledWinSum);

        return [$out, $gateFired];
    }

    /**
     * Calibrate win-tier weights so EV(payout) == targetRtp exactly, the 0x tier
     * absorbing the rest. FLOOR on win tiers + remainder to 0x → bias ≤ 0
     * (S-ACC-4). Used by effectiveTargetRtpPure's fixed point and by tests.
     *
     * @param array $baseWeights keyed by multiplier (0 = no-win)
     * @param int[] $multipliers
     * @return array<int,int>
     */
    public function normalizeWeights(array $baseWeights, array $multipliers, float $targetRtp): array
    {
        $targetRtp = max(0.0, $targetRtp);

        $total = (float) ($baseWeights[0] ?? 0);
        $expectedWin = 0.0;
        foreach ($multipliers as $mult) {
            $w = (float) ($baseWeights[$mult] ?? 0);
            $total += $w;
            $expectedWin += $mult * $w;
        }

        if ($total <= 0 || $expectedWin <= 0) {
            return $baseWeights;
        }

        $k = ($targetRtp * $total) / $expectedWin;

        $winWeightSum = 0.0;
        foreach ($multipliers as $mult) {
            $winWeightSum += (float) ($baseWeights[$mult] ?? 0);
        }
        if ($winWeightSum > 0) {
            $kMax = $total / $winWeightSum;
            if ($k > $kMax) {
                $k = $kMax;
            }
        }

        $precision = 100_000;
        $out = [];
        $scaledWinSum = 0;
        foreach ($multipliers as $mult) {
            // FLOOR (was round): rounding bias on the realised RTP is ≤ 0 by
            // construction (in the house's favour). The 0x tier absorbs the
            // remainder so it never goes negative.
            $scaled = (float) ($baseWeights[$mult] ?? 0) * $k * $precision;
            $w = (int) floor($scaled);
            $out[$mult] = $w;
            $scaledWinSum += $w;
        }

        $out[0] = max(0, (int) round($total * $precision) - $scaledWinSum);

        return $out;
    }

    /** Smallest win tier the vault can pay for this bet (streak floor). 0 = none. */
    private function smallestAffordableTier(array $multipliers, int $luckyBalance, int $grossBet, int $negativeLimit): int
    {
        $sorted = $multipliers;
        sort($sorted);
        foreach ($sorted as $m) {
            if (($luckyBalance - ($grossBet * $m)) >= -$negativeLimit) {
                return (int) $m;
            }
        }
        return 0;
    }

    /** Median win multiplier (for modulator direction). */
    private function medianMultiplier(array $multipliers): float
    {
        if (empty($multipliers)) {
            return 0.0;
        }
        $sorted = $multipliers;
        sort($sorted);
        $n = count($sorted);
        $mid = intdiv($n, 2);
        return $n % 2 ? (float) $sorted[$mid] : ($sorted[$mid - 1] + $sorted[$mid]) / 2.0;
    }

    /** Piecewise linear vault health factor: [-1.0, +1.0]. Telemetry + modulator. */
    public function walletFactor(int $balance, ?array $t = null): float
    {
        $t = $t ?? $this->getThresholds();

        if ($balance <= 0) return -1.0;
        if ($balance <= $t['min']) return -1.0;
        if ($balance <= $t['tight']) {
            return -1.0 + 0.4 * (($balance - $t['min']) / max(1, $t['tight'] - $t['min']));
        }
        if ($balance <= $t['target']) {
            return -0.6 + 0.6 * (($balance - $t['tight']) / max(1, $t['target'] - $t['tight']));
        }
        if ($balance <= $t['high']) {
            return 0.0 + 0.5 * (($balance - $t['target']) / max(1, $t['high'] - $t['target']));
        }
        if ($balance <= $t['drain']) {
            return 0.5 + 0.5 * (($balance - $t['high']) / max(1, $t['drain'] - $t['high']));
        }
        return 1.0;
    }

    /** Pure RTP correction factor (telemetry + modulator): [-1.0, +1.0]. */
    public function rtpFactorPure(int $totalBet, float $currentRTP, array $sens, float $targetRtp): float
    {
        $activation = (int) ($sens['rtp_activation'] ?? self::RTP_ACTIVATION);
        if ($totalBet < $activation) {
            return 0.0;
        }

        $gap = $targetRtp - $currentRTP;
        $clamped = max(-0.15, min(0.15, $gap));

        return $clamped / 0.15;
    }

    private function weightedSelect(array $weights, ?callable $rng = null): int
    {
        $total = array_sum($weights);
        if ($total <= 0) return 0;

        $roll = $rng ? (int) $rng($total) : random_int(1, $total);
        $cumulative = 0;

        foreach ($weights as $mult => $w) {
            $cumulative += $w;
            if ($roll <= $cumulative) return (int) $mult;
        }

        return 0;
    }

    public function getZone(int $balance, ?array $t = null): string
    {
        $t = $t ?? $this->getThresholds();

        if ($balance <= $t['min']) return 'CRITICAL';
        if ($balance <= $t['tight']) return 'TIGHT';
        if ($balance <= $t['target']) return 'NORMAL';
        if ($balance <= $t['high']) return 'GENEROUS';
        return 'DRAIN';
    }
}
