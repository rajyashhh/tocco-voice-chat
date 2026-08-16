<?php

namespace App\Services\FairLuck\V7;

use App\Models\FairLuckWallet;

/**
 * PoolManager V7: the atomic money core for the lucky-gift vault.
 *
 * The whole per-bet money mutation is performed by TWO small, auditable Lua
 * scripts that run server-side on the durable `fairluck` Redis connection
 * (AOF + noeviction). All vault/owner/RTP state lives on that ONE instance so a
 * single EVAL is fully atomic — no DB row locks on the hot path, no partial state.
 *
 *   creditAndRead()  — script A: accrue owner cut, credit netToVault, bump the
 *                      reconciliation intake counter, and read the user's RTP
 *                      snapshot. One round trip. Returns the post-credit balance
 *                      + the stats the pure selector needs.
 *   settle()         — script B: atomically (re)check affordability and debit the
 *                      win payout (never overpaying past the negative limit), bump
 *                      the payout counter, and update the user's RTP hash + TTL.
 *                      One round trip. Returns the ACTUAL applied payout.
 *
 * Selection itself is pure PHP (MultiplierTable) between the two calls, so the
 * money-decision logic stays fully unit-testable without a live Redis.
 *
 * Invariant for reconciliation: vault == baseline + intake - payout (see
 * fairluck:reconcile). Owner accrual leaves to owner_wallet separately and does
 * not affect the vault.
 */
class PoolManager
{
    public const KEY_VAULT         = 'fairluck:wallet:unified_vault';
    public const KEY_OWNER_ACCRUAL = 'fairluck:accrual:owner';
    public const KEY_STAT_INTAKE   = 'fairluck:stat:intake';
    public const KEY_STAT_PAYOUT   = 'fairluck:stat:payout';
    public const KEY_STAT_BASELINE = 'fairluck:stat:baseline';

    /**
     * Persistent sub-coin owner-fee remainder, measured in bps-coins (1/10000 of a
     * coin). Never given a TTL — it is durable money state like the vault, carrying
     * the fractional owner fee across batches until it crosses a whole coin, which
     * the batchSettle EVAL then realizes (moving the coin vault→owner accrual). The
     * EVAL is the ONLY writer (single-writer atomicity); the sweep never touches it.
     */
    public const KEY_OWNER_FRACTION_BPS = 'fairluck:accrual:owner_bps';

    /**
     * Persistent sub-coin RECEIVER-fee remainder, PER receiver, in bps-coins
     * (1/10000 of a coin). HASH: field = receiverId, value = 0..9999 after each
     * flush (the whole-coin part is realized out every batch). No TTL — owed money
     * state, symmetric to KEY_OWNER_FRACTION_BPS but credited to the receiver's
     * wallet (not an accrual sink) when it crosses a whole coin. The batchSettle
     * EVAL is the ONLY writer (single-writer atomicity).
     */
    public const KEY_RECEIVER_FRACTION_BPS = 'fairluck:accrual:receiver_bps';

    /**
     * TTL of the batch-intent proof key: 7 days, paired with the reconcile rule
     * that a proof-less intent older than 6 days becomes `needs_review` (never
     * auto-refunded) — so an expired proof can never be misread as "EVAL never
     * ran" and re-refund a settled batch.
     */
    public const INTENT_PROOF_TTL  = 604_800;

    public static function userKey(int $userId): string
    {
        return 'fairluck:V7:user:' . $userId;
    }

    /**
     * Proof key written ATOMICALLY inside the batchSettle EVAL. Its existence is
     * the authoritative answer to "did the EVAL commit?" for a given intent nonce
     * (value = totalPaid), used by lucky:reconcile-intents to decide between
     * refunding the upfront debit (EVAL never ran) and crediting the win
     * (EVAL ran but the client saw an error).
     */
    public static function intentProofKey(string $nonce): string
    {
        return 'fairluck:batch:intent:' . $nonce;
    }

    private function redis()
    {
        return FairLuckWallet::vaultRedis();
    }

    public function getBalance(): int
    {
        return FairLuckWallet::getRedisBalance(FairLuckWallet::TYPE_UNIFIED_VAULT);
    }

    /**
     * Script A — atomic credit + read.
     *
     * Ensures the vault key is seeded from the DB snapshot first (getBalance does
     * this), then runs the atomic block.
     *
     * @return object{vault:int, totalSpent:int, totalReceived:int, consecutiveLosses:int}
     */
    public function creditAndRead(int $netToVault, int $ownerCut, int $userId): object
    {
        // Make sure the live vault key exists (seed from DB snapshot if cold).
        $this->getBalance();

        $script = <<<'LUA'
        local netToVault = tonumber(ARGV[1])
        local ownerCut   = tonumber(ARGV[2])
        if ownerCut > 0 then redis.call('INCRBY', KEYS[2], ownerCut) end
        local vault
        if netToVault ~= 0 then
            vault = redis.call('INCRBY', KEYS[1], netToVault)
            if netToVault > 0 then redis.call('INCRBY', KEYS[3], netToVault) end
        else
            vault = tonumber(redis.call('GET', KEYS[1]) or 0)
        end
        local ts = tonumber(redis.call('HGET', KEYS[4], 'total_spent') or 0)
        local tr = tonumber(redis.call('HGET', KEYS[4], 'total_received') or 0)
        local cl = tonumber(redis.call('HGET', KEYS[4], 'consecutive_losses') or 0)
        return {vault, ts, tr, cl}
        LUA;

        $res = $this->redis()->eval(
            $script,
            4,
            self::KEY_VAULT,
            self::KEY_OWNER_ACCRUAL,
            self::KEY_STAT_INTAKE,
            self::userKey($userId),
            $netToVault,
            $ownerCut
        );

        return (object) [
            'vault'             => (int) ($res[0] ?? 0),
            'totalSpent'        => (int) ($res[1] ?? 0),
            'totalReceived'     => (int) ($res[2] ?? 0),
            'consecutiveLosses' => (int) ($res[3] ?? 0),
        ];
    }

    /**
     * Script B — atomic settle (guarded debit + RTP update + TTL).
     *
     * The payout is debited ONLY if the vault can still afford it without breaching
     * the negative limit (re-checked against the live balance to stay solvent under
     * concurrency). The ACTUAL applied payout is returned: callers must treat
     * applied < requested as the real outcome (no phantom wins).
     *
     * @return object{applied:int, vault:int}
     */
    public function settle(int $userId, int $betAmount, int $requestedPayout, int $negativeLimit, int $ttlSeconds): object
    {
        $script = <<<'LUA'
        local payout   = tonumber(ARGV[1])
        local limit    = tonumber(ARGV[2])
        local bet      = tonumber(ARGV[3])
        local ttl      = tonumber(ARGV[4])
        local applied  = 0
        if payout > 0 then
            local bal = tonumber(redis.call('GET', KEYS[1]) or 0)
            if (bal - payout) >= -limit then
                redis.call('DECRBY', KEYS[1], payout)
                redis.call('INCRBY', KEYS[3], payout)
                applied = payout
            end
        end
        -- RTP / streak bookkeeping (integer only)
        redis.call('HINCRBY', KEYS[2], 'total_spent', bet)
        redis.call('HINCRBY', KEYS[2], 'bet_count', 1)
        if applied > 0 then
            redis.call('HINCRBY', KEYS[2], 'total_received', applied)
            redis.call('HINCRBY', KEYS[2], 'win_count', 1)
            redis.call('HSET', KEYS[2], 'consecutive_losses', 0)
        else
            redis.call('HINCRBY', KEYS[2], 'consecutive_losses', 1)
        end
        if redis.call('HEXISTS', KEYS[2], 'first_bet_ts') == 0 then
            redis.call('HSET', KEYS[2], 'first_bet_ts', ARGV[5])
        end
        if ttl > 0 then redis.call('EXPIRE', KEYS[2], ttl) end
        local fv = tonumber(redis.call('GET', KEYS[1]) or 0)
        return {applied, fv}
        LUA;

        $res = $this->redis()->eval(
            $script,
            3,
            self::KEY_VAULT,
            self::userKey($userId),
            self::KEY_STAT_PAYOUT,
            $requestedPayout,
            $negativeLimit,
            $betAmount,
            $ttlSeconds,
            time()
        );

        return (object) [
            'applied' => (int) ($res[0] ?? 0),
            'vault'   => (int) ($res[1] ?? 0),
        ];
    }

    /** Daily-rolling key for the global beginner-protection payout cap (§4.1). */
    public static function beginnerDailyCapKey(string $ymd): string
    {
        return 'fairluck:bp:daily_cap:' . $ymd;
    }

    /**
     * Script C — atomic BATCH credit + settle for a whole combo (x1..xN) in ONE
     * round trip, now including BEGINNER PROTECTION (§4.3) and STREAK v2 (§2.4)
     * fully inside the EVAL — the ONLY writer of bp_remaining / bp_state /
     * consecutive_losses / streak_bet_sum (§5.3).
     *
     * Semantically identical to running creditAndRead()+settle() per bet, but the
     * evolving vault/streak/bp state lives entirely inside Redis for the EVAL so
     * every per-bet gate runs against the LIVE state as the combo drains it.
     *
     * For each bet the PHP side draws a PAIR of outcomes (boosted-table payout and
     * normal-table payout, §4.3). The EVAL picks boosted while bp_remaining > 0 else
     * normal — so the boosted/normal decision is made against live, atomic budget,
     * and PHP stays advisory (same as applied[i]). bp is seeded inside the EVAL on
     * the first bet (HSETNX-equivalent: only when bp_state is unset and the candidate
     * is eligible and the global daily cap is not exhausted) — a refunded combo never
     * ran the EVAL, so it never seeds a budget (closes B-ACC-1).
     *
     * bp consumption per hit (§4.3 / B-ACC-2):
     *   consumed_i = min(bp_remaining, max(0, applied_i − floor(bet × RTP_boost_bps/10000)))
     * baseline = the BOOSTED table's own expected return (not the normal RTP), and
     * the boosted table has NO tail (≤ ×100) so the per-bet overrun is bounded.
     * When bp_remaining hits 0 → bp_state=2 and the rest of the combo is normal.
     *
     * STREAK v2 (§2.4 / S-ECO-2): the hash tracks streak_bet_sum alongside
     * consecutive_losses. At the hard floor a win is FORCED, but its payout is sized
     * on the streak's AVERAGE bet (streak_bet_sum / streak), capped at
     * 3 × avg × smallestMult — so a cheap streak then a huge bet only yields a payout
     * proportional to the cheap bets. The forced floor synthesises a payout from
     * avg × smallestMult; the natural per-bet draw stays PHP-side and is honoured when
     * it already exceeds the floor. The cost of these forced floor wins is pre-funded
     * in the calibration (MultiplierTable::pityEv subtracts it from RTP_eff), so the
     * realised RTP stays anchored to the panel.
     *
     * ARGV layout (G5; results are PAIRS per bet):
     *   [1] = N (bet count)
     *   [2] = negativeLimit
     *   [3] = ttlSeconds
     *   [4] = now (unix ts)
     *   [5] = bpEligible        (0/1 — candidate passed §4.1 conditions)
     *   [6] = bpBudget          (coins to seed if eligible & cap allows)
     *   [7] = rtpBoostBps       (RTP_boost × 10000, for the consumed baseline)
     *   [8] = bpDailyCapTotal   (global daily cap; 0 = disabled)
     *   [9] = streakHardFloor   (forced-win streak threshold, e.g. 70)
     *   [10]= smallestMult      (smallest configured multiplier — floor sizing)
     *   [11]= ownerBps          (owner fee in basis points; constant per combo)
     *   [12]= receiverBps        (receiver fee in basis points; constant per combo)
     *   then the per-bet block, bet i at PB = 13 + i*7 (i = 0..N-1):
     *      [+0] netToVault_i     (int; = full unitPrice — owner AND receiver sub-coin
     *                             shares both ride inside it, realized out at flush)
     *      [+1] ownerCut_i       (int >= 0; wire-compat only, EVAL ignores it)
     *      [+2] betAmount_i      (gross bet, int)
     *      [+3] boostedPayout_i  (int >= 0; chosen when bp_remaining > 0)
     *      [+4] normalPayout_i   (int >= 0; chosen otherwise)
     *      [+5] expectedBoostReturn_i (floor(bet × rtpBoostBps/10000); consumed base)
     *      [+6] receiverId_i     (int; the receiver this bet's fee accrues to)
     *   trailing arg at offset 13 + N*7: intent-proof TTL (0 = skip).
     *
     * KEYS: [1]=vault [2]=owner_accrual [3]=intake [4]=payout [5]=user_rtp_hash
     *       [6]=intent proof key  [7]=global bp daily-cap counter (today)
     *       [8]=owner sub-coin fraction remainder (bps-coins, persistent)
     *       [9]=receiver sub-coin fraction remainder (HASH field=receiverId, bps-coins)
     *
     * Owner fee is accrued EXACTLY inside the EVAL from bet × ownerBps (ARGV[11]) in
     * bps-coins; whole coins are realized at flush (moved vault→owner accrual) and
     * the reconcile intake counter is decremented by the same amount so the
     * invariant vault == baseline + intake − payout stays exact. The RECEIVER fee is
     * accrued IDENTICALLY (bet × receiverBps, ARGV[12]) but PER receiver in the hash
     * KEYS[9]; realized whole coins leave the vault + intake the same way, and the
     * per-receiver realized integer is RETURNED (not sent to an accrual) so the post
     * job credits the receiver's wallet exactly. The per-bet ownerCut_i ARGV field is
     * wire-compat only (the EVAL ignores it).
     *
     * @param array<int, array{netToVault:int, ownerCut:int, betAmount:int, boostedPayout:int, normalPayout:int, expectedBoostReturn:int, receiverId:int}> $bets
     * @param array{eligible:bool, budget:int, rtpBoostBps:int, dailyCapTotal:int, hardFloor:int, smallestMult:int} $bp
     * @param int $ownerBps Owner fee in basis points (e.g. 1% → 100), constant per combo.
     * @param int $receiverBps Receiver fee in basis points (e.g. 1% → 100), constant per combo.
     * @return object{applied:int[], totalPaid:int, vault:int, bpConsumed:int, receiverRealized:array<int,int>}
     */
    public function batchSettle(int $userId, array $bets, int $negativeLimit, int $ttlSeconds, ?string $intentNonce = null, array $bp = [], int $ownerBps = 0, int $receiverBps = 0): object
    {
        $n = count($bets);
        if ($n === 0) {
            return (object) ['applied' => [], 'totalPaid' => 0, 'vault' => $this->getBalance(), 'bpConsumed' => 0, 'receiverRealized' => []];
        }

        // Seed the live vault key from the DB snapshot if cold so the first INCRBY is
        // against a correct baseline.
        $this->getBalance();

        $script = <<<'LUA'
        local n           = tonumber(ARGV[1])
        local limit       = tonumber(ARGV[2])
        local ttl         = tonumber(ARGV[3])
        local nowTs       = ARGV[4]
        local bpEligible  = tonumber(ARGV[5])
        local bpBudget    = tonumber(ARGV[6])
        local rtpBoostBps = tonumber(ARGV[7])
        local capTotal    = tonumber(ARGV[8])
        local hardFloor   = tonumber(ARGV[9])
        local smallMult   = tonumber(ARGV[10])
        local ownerBps    = tonumber(ARGV[11])
        local receiverBps = tonumber(ARGV[12])

        -- Per-user RTP + streak counters hoisted; flushed once at the end. These
        -- (and bp_remaining/bp_state/streak_bet_sum) are written ONLY here (§5.3).
        local totalSpent = tonumber(redis.call('HGET', KEYS[5], 'total_spent') or 0)
        local totalRecv  = tonumber(redis.call('HGET', KEYS[5], 'total_received') or 0)
        local betCount   = tonumber(redis.call('HGET', KEYS[5], 'bet_count') or 0)
        local winCount   = tonumber(redis.call('HGET', KEYS[5], 'win_count') or 0)
        local consec     = tonumber(redis.call('HGET', KEYS[5], 'consecutive_losses') or 0)
        local streakSum  = tonumber(redis.call('HGET', KEYS[5], 'streak_bet_sum') or 0)
        local bpState    = tonumber(redis.call('HGET', KEYS[5], 'bp_state') or 0)
        local bpRemain   = tonumber(redis.call('HGET', KEYS[5], 'bp_remaining') or 0)

        -- BP seeding (HSETNX-equivalent): only on first sight (bp_state unset/0),
        -- only when eligible, and only while the GLOBAL daily cap has room. A
        -- refunded combo never reached this EVAL, so it never seeds (B-ACC-1).
        local bpConsumed = 0
        if bpState == 0 and bpEligible == 1 and bpBudget > 0 then
            local capOk = true
            local seed  = bpBudget
            if capTotal > 0 then
                local usedToday = tonumber(redis.call('GET', KEYS[7]) or 0)
                local room = capTotal - usedToday
                if room <= 0 then
                    capOk = false
                else
                    if seed > room then seed = room end
                end
            end
            if capOk and seed > 0 then
                bpRemain = seed
                bpState  = 1
            end
        end

        local bal         = tonumber(redis.call('GET', KEYS[1]) or 0)
        local ownerBpsAccr = 0  -- exact owner fee for THIS batch, in bps-coins
        local recvBpsAccr  = {} -- exact receiver fee THIS batch, per receiverId, bps-coins
        local intakeSum   = 0
        local payoutSum = 0
        local totalPaid = 0
        local applied   = {}

        local PB = 13  -- per-bet block base (header now 12 scalars incl receiverBps; bet 0 starts at 13)
        local K  = 7   -- per-bet stride (6 numeric fields + receiverId)

        for i = 0, n - 1 do
            local net      = tonumber(ARGV[PB + i*K])
            -- ARGV[PB + i*K + 1] (owner_i) is wire-compat only; EVAL accrues owner
            -- exactly from bet × ownerBps in bps-coins below (no per-bet flooring).
            local bet      = tonumber(ARGV[PB + i*K + 2])
            local boostPay = tonumber(ARGV[PB + i*K + 3])
            local normPay  = tonumber(ARGV[PB + i*K + 4])
            local boostBase= tonumber(ARGV[PB + i*K + 5])
            local recvId   = ARGV[PB + i*K + 6]  -- receiver id (string hash field)

            -- CREDIT phase (net to vault + intake). The vault receives the FULL
            -- netToVault (= unitPrice — both owner AND receiver sub-coin shares ride
            -- inside it); whole owner/receiver coins are moved OUT once at FLUSH so
            -- conservation/reconcile stay exact. Fees accrued EXACTLY in bps-coins.
            if bet > 0 and ownerBps > 0 then
                ownerBpsAccr = ownerBpsAccr + (bet * ownerBps)
            end
            if bet > 0 and receiverBps > 0 and recvId ~= nil and recvId ~= '' then
                recvBpsAccr[recvId] = (recvBpsAccr[recvId] or 0) + (bet * receiverBps)
            end
            if net ~= 0 then
                bal = bal + net
                if net > 0 then intakeSum = intakeSum + net end
            end

            -- Choose boosted vs normal against LIVE bp_remaining (§4.3).
            local useBoost = (bpState == 1 and bpRemain > 0)
            local reqPay = normPay
            if useBoost then reqPay = boostPay end

            -- STREAK v2 hard floor (§2.4 / S-ECO-2): force a win sized on the
            -- streak's AVERAGE bet, not the current (possibly huge) bet.
            local forced = false
            if hardFloor > 0 and consec >= hardFloor and smallMult > 0 then
                local avg = 0
                if consec > 0 then avg = math.floor(streakSum / consec) end
                if avg < 1 then avg = bet end
                local floorPay = avg * smallMult
                local cap = 3 * avg * smallMult
                if floorPay > cap then floorPay = cap end
                if floorPay > reqPay then
                    reqPay = floorPay
                    forced = true
                end
            end

            -- SETTLE phase: guarded debit re-checked against the LIVE balance.
            local appPay = 0
            if reqPay > 0 then
                if (bal - reqPay) >= -limit then
                    bal = bal - reqPay
                    payoutSum = payoutSum + reqPay
                    appPay = reqPay
                end
            end

            -- BP consumption (§4.3 / B-ACC-2): cost above the boosted baseline,
            -- clamped to remaining budget. Only when this hit actually used boost.
            if useBoost and appPay > 0 then
                local over = appPay - boostBase
                if over < 0 then over = 0 end
                local take = over
                if take > bpRemain then take = bpRemain end
                if take > 0 then
                    bpRemain   = bpRemain - take
                    bpConsumed = bpConsumed + take
                end
                if bpRemain <= 0 then
                    bpState  = 2   -- exhausted: remaining combo is normal
                    bpRemain = 0
                end
            end

            -- RTP / streak bookkeeping (integer only).
            totalSpent = totalSpent + bet
            betCount   = betCount + 1
            if appPay > 0 then
                totalRecv = totalRecv + appPay
                winCount  = winCount + 1
                consec    = 0
                streakSum = 0
                totalPaid = totalPaid + appPay
            else
                consec    = consec + 1
                streakSum = streakSum + bet
            end

            applied[i + 1] = appPay
        end

        -- FLUSH phase.
        -- Realize whole owner coins from the persistent bps-coin remainder:
        --   carry in -> add this batch's exact fee -> split whole / remainder.
        local ownerCoins = 0
        if ownerBpsAccr > 0 then
            local fracBps = tonumber(redis.call('GET', KEYS[8]) or 0) + ownerBpsAccr
            ownerCoins = math.floor(fracBps / 10000)
            local remBps = fracBps - ownerCoins * 10000          -- 0..9999
            redis.call('SET', KEYS[8], remBps)
            if ownerCoins > 0 then
                -- The fraction was credited into the vault as part of netToVault; move
                -- the now-whole coin OUT of the vault into the owner accrual, and back
                -- it out of intake so the reconcile invariant
                -- (vault == baseline + intake - payout) holds EXACTLY.
                bal = bal - ownerCoins
                intakeSum = intakeSum - ownerCoins
            end
        end

        -- RECEIVER realization (parallel to owner, but PER receiver and credited to
        -- the receiver's wallet via the returned map — NOT to an accrual sink).
        --   carry-in (KEYS[9] hash field) + this batch -> whole coins / remainder.
        local recvRealized   = {}   -- flat [id, coins, id, coins, ...]
        local recvCoinsTotal = 0
        for rid, accr in pairs(recvBpsAccr) do
            if accr > 0 then
                local carry = tonumber(redis.call('HGET', KEYS[9], rid) or 0) + accr
                local coins = math.floor(carry / 10000)
                local rem   = carry - coins * 10000          -- 0..9999
                redis.call('HSET', KEYS[9], rid, rem)
                if coins > 0 then
                    recvCoinsTotal = recvCoinsTotal + coins
                    recvRealized[#recvRealized + 1] = rid
                    recvRealized[#recvRealized + 1] = coins
                end
            end
        end
        -- The realized receiver coins were credited into the vault as part of net
        -- (= full unitPrice). Move them OUT of the vault and back out of intake so
        -- vault == baseline + intake - payout holds EXACTLY (identical to owner).
        if recvCoinsTotal > 0 then
            bal       = bal - recvCoinsTotal
            intakeSum = intakeSum - recvCoinsTotal
        end

        redis.call('SET', KEYS[1], bal)
        if ownerCoins > 0 then redis.call('INCRBY', KEYS[2], ownerCoins) end
        if intakeSum ~= 0 then redis.call('INCRBY', KEYS[3], intakeSum) end
        if payoutSum > 0 then redis.call('INCRBY', KEYS[4], payoutSum) end

        redis.call('HSET', KEYS[5],
            'total_spent', totalSpent,
            'total_received', totalRecv,
            'bet_count', betCount,
            'win_count', winCount,
            'consecutive_losses', consec,
            'streak_bet_sum', streakSum,
            'bp_state', bpState,
            'bp_remaining', bpRemain)
        if redis.call('HEXISTS', KEYS[5], 'first_bet_ts') == 0 then
            redis.call('HSET', KEYS[5], 'first_bet_ts', nowTs)
        end
        if ttl > 0 then redis.call('EXPIRE', KEYS[5], ttl) end

        -- Advance the global bp daily-cap counter by what THIS combo consumed
        -- (atomic with the payout). Day-rolling key (caller passes today's key).
        if bpConsumed > 0 and capTotal > 0 then
            redis.call('INCRBY', KEYS[7], bpConsumed)
            redis.call('EXPIRE', KEYS[7], 172800) -- 2 days, self-pruning
        end

        -- Intent commit-proof in the SAME atomic block (value = totalPaid).
        local proofTtl = tonumber(ARGV[PB + n*K]) or 0
        if proofTtl > 0 then
            redis.call('SET', KEYS[6], totalPaid, 'EX', proofTtl)
        end

        applied[n + 1] = totalPaid
        applied[n + 2] = bal
        applied[n + 3] = bpConsumed
        -- Trailing per-receiver realized block: M pairs then [id,coins]×M. Lua can
        -- only return arrays, so the map is shipped length-prefixed and last; PHP
        -- parses it into receiverRealized for the post job to credit each receiver.
        applied[n + 4] = math.floor(#recvRealized / 2)
        for k = 1, #recvRealized do
            applied[n + 4 + k] = recvRealized[k]
        end
        return applied
        LUA;

        $rtpBoostBps   = (int) ($bp['rtpBoostBps'] ?? 9200);
        $argv = [
            $n,
            $negativeLimit,
            $ttlSeconds,
            time(),
            !empty($bp['eligible']) ? 1 : 0,
            (int) ($bp['budget'] ?? 0),
            $rtpBoostBps,
            (int) ($bp['dailyCapTotal'] ?? 0),
            (int) ($bp['hardFloor'] ?? MultiplierTable::STREAK_HARD_FLOOR),
            (int) ($bp['smallestMult'] ?? 0),
            $ownerBps,
            $receiverBps,
        ];
        // Per-bet block: 7 fields each, in declaration order (receiverId last).
        foreach ($bets as $b) {
            $argv[] = (int) $b['netToVault'];
            $argv[] = (int) $b['ownerCut'];
            $argv[] = (int) $b['betAmount'];
            $argv[] = (int) ($b['boostedPayout'] ?? 0);
            $argv[] = (int) ($b['normalPayout'] ?? $b['requestedPayout'] ?? 0);
            $argv[] = (int) ($b['expectedBoostReturn'] ?? 0);
            $argv[] = (int) ($b['receiverId'] ?? 0);
        }
        // Trailing intent-proof TTL (0 disables the stamp — key 6 untouched).
        $argv[] = $intentNonce !== null ? self::INTENT_PROOF_TTL : 0;

        $res = $this->redis()->eval(
            $script,
            9,
            self::KEY_VAULT,
            self::KEY_OWNER_ACCRUAL,
            self::KEY_STAT_INTAKE,
            self::KEY_STAT_PAYOUT,
            self::userKey($userId),
            self::intentProofKey($intentNonce ?? 'none'),
            self::beginnerDailyCapKey(gmdate('Ymd')),
            self::KEY_OWNER_FRACTION_BPS,
            self::KEY_RECEIVER_FRACTION_BPS,
            ...$argv
        );

        // Ordered tail parse (the realized-receiver block is now LAST, so the
        // array_pop trio no longer works): [..applied(N).., totalPaid, vault,
        // bpConsumed, M, (id,coins)×M].
        $res = is_array($res) ? array_map('intval', $res) : [];
        $applied    = array_slice($res, 0, $n);
        $totalPaid  = (int) ($res[$n] ?? 0);
        $vaultAfter = (int) ($res[$n + 1] ?? 0);
        $bpConsumed = (int) ($res[$n + 2] ?? 0);
        $m          = (int) ($res[$n + 3] ?? 0);
        $receiverRealized = [];
        for ($k = 0; $k < $m; $k++) {
            $rid   = (int) ($res[$n + 4 + $k * 2] ?? 0);
            $coins = (int) ($res[$n + 5 + $k * 2] ?? 0);
            if ($rid > 0 && $coins > 0) {
                $receiverRealized[$rid] = ($receiverRealized[$rid] ?? 0) + $coins;
            }
        }

        return (object) [
            'applied'          => $applied,
            'totalPaid'        => $totalPaid,
            'vault'            => $vaultAfter,
            'bpConsumed'       => $bpConsumed,
            'receiverRealized' => $receiverRealized,
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Legacy helpers retained for back-compat (still used by the legacy
    // DB-transaction path and existing unit tests). The hot path uses the atomic
    // creditAndRead()/settle() above.
    // ────────────────────────────────────────────────────────────────────────

    public function creditBet(int $netBet): void
    {
        if ($netBet <= 0) return;

        $key = self::KEY_VAULT;
        try {
            FairLuckWallet::increaseBalance(FairLuckWallet::TYPE_UNIFIED_VAULT, $netBet, 'V7 bet credit', null);
        } catch (\Throwable $e) {
            FairLuckWallet::vaultRedis()->decrby($key, $netBet);
            throw $e;
        }
    }

    public function creditBetRedisOnly(int $netBet): void
    {
        if ($netBet <= 0) return;

        FairLuckWallet::incrementRedisBalance(FairLuckWallet::TYPE_UNIFIED_VAULT, $netBet);
    }

    public function debitPayout(int $amount): bool
    {
        if ($amount <= 0) return true;

        $negativeLimit = \App\Models\FairLuckSetting::getVaultNegativeLimit();
        $key = self::KEY_VAULT;

        $luaScript = "local bal = tonumber(redis.call('GET', KEYS[1]) or 0) "
            . "local amt = tonumber(ARGV[1]) "
            . "local lim = tonumber(ARGV[2]) "
            . "if (bal - amt) >= -lim then redis.call('DECRBY', KEYS[1], amt) return 1 end "
            . "return 0";

        $result = FairLuckWallet::vaultRedis()->eval($luaScript, 1, $key, $amount, $negativeLimit);

        if (!$result) {
            return false;
        }

        try {
            FairLuckWallet::persistDecreaseBalance(FairLuckWallet::TYPE_UNIFIED_VAULT, $amount, 'V7 win payout', null);
        } catch (\Throwable $e) {
            FairLuckWallet::vaultRedis()->incrby($key, $amount);
            return false;
        }

        return true;
    }

    public function debitPayoutRedisOnly(int $amount): bool
    {
        if ($amount <= 0) return true;

        $negativeLimit = \App\Models\FairLuckSetting::getVaultNegativeLimit();
        $key = self::KEY_VAULT;

        $luaScript = "local bal = tonumber(redis.call('GET', KEYS[1]) or 0) "
            . "local amt = tonumber(ARGV[1]) "
            . "local lim = tonumber(ARGV[2]) "
            . "if (bal - amt) >= -lim then redis.call('DECRBY', KEYS[1], amt) return 1 end "
            . "return 0";

        return (bool) FairLuckWallet::vaultRedis()->eval($luaScript, 1, $key, $amount, $negativeLimit);
    }

    public function getWalletBalances(): array
    {
        return ['lucky_wallet' => $this->getBalance()];
    }
}
