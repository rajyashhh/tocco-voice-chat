<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

/**
 * Unified abusive-behaviour guard (owner policy 2026-06-11).
 *
 * A human's legitimate ceiling for repeated actions (tap-hearts, lucky-gift
 * sends, …) is ~[maxPerSecond] per second. Auto-clickers blow far past it.
 * Escalation per user per action family:
 *   strike 1, 2  → explicit warning (returned to the client, shown verbatim)
 *   strike 3     → 60s block from ALL guarded actions
 *   strike 4+    → 600s block
 * Strikes decay after [STRIKE_TTL]. The block is GLOBAL across actions —
 * an abuser is paused entirely, not just on the abused action.
 *
 * All state lives in Redis (atomic, survives Octane workers, costs O(1)).
 */
class ActionAbuseGuard
{
    private const STRIKE_TTL = 3600; // strikes window (1h)
    private const SHORT_BLOCK = 60;
    private const LONG_BLOCK = 600;

    /** A burst this many seconds' worth of tokens deep is still human. */
    private const BURST_SECONDS = 4;

    /** Minimum gap between strikes, in seconds (one burst ≠ multiple strikes). */
    private const STRIKE_GATE = 2;

    /**
     * Register [count] occurrences of [action] for [userId] and evaluate the
     * rate against [maxPerSecond].
     *
     * @return array{ok: bool, credited: int, warning: ?string, blocked_for: int}
     *   ok=false ⇒ the user is blocked; credited = how much of [count] may be
     *   counted toward product totals (capped at the legit ceiling, so a
     *   cheater's overflow never lands in stored numbers).
     */
    public static function register(
        int $userId,
        string $action,
        int $count = 1,
        int $maxPerSecond = 5,
    ): array {
        $blockedFor = self::blockedRemaining($userId);
        if ($blockedFor > 0) {
            return [
                'ok' => false,
                'credited' => 0,
                'warning' => self::blockMessage($blockedFor),
                'blocked_for' => $blockedFor,
            ];
        }

        $count = max(1, $count);
        $capacity = $maxPerSecond * self::BURST_SECONDS;

        // Token bucket, atomic in Redis: refills [maxPerSecond] tokens/sec up
        // to [capacity], credits up to what is available. Elapsed time is
        // measured server-side between calls, so client flush jitter can never
        // be mistaken for a burst — the previous fixed 2s INCRBY window struck
        // honest tappers whenever two ~2s-apart flushes landed inside one
        // window (network latency), reading ≤5 taps/sec as double the ceiling.
        $script = '
            local now = tonumber(ARGV[1])
            local rate = tonumber(ARGV[2])
            local capacity = tonumber(ARGV[3])
            local cost = tonumber(ARGV[4])
            local data = redis.call("hmget", KEYS[1], "t", "ts")
            local tokens = tonumber(data[1])
            local ts = tonumber(data[2])
            if not tokens or not ts or now < ts then
                tokens = capacity
                ts = now
            end
            tokens = math.min(capacity, tokens + (now - ts) * rate / 1000.0)
            local credited = math.min(cost, math.floor(tokens))
            redis.call("hset", KEYS[1], "t", tokens - credited, "ts", now)
            redis.call("expire", KEYS[1], 120)
            return credited
        ';
        $credited = (int) Redis::eval(
            $script,
            1,
            "abuse:bucket:{$action}:{$userId}",
            (int) (microtime(true) * 1000),
            $maxPerSecond,
            $capacity,
            $count,
        );

        // Fully credited: within the human ceiling.
        if ($credited >= $count) {
            return ['ok' => true, 'credited' => $count, 'warning' => null, 'blocked_for' => 0];
        }

        // Partial spill of up to one second's worth while still creditable is
        // timer/latency noise, not an auto-clicker: credit what fits silently
        // (stored totals stay capped either way). Escalate only when the
        // bucket ran DRY (credited 0 ⇒ sustained over-rate) or the overflow
        // exceeds a full second's allowance.
        $uncredited = $count - $credited;
        if ($credited > 0 && $uncredited <= $maxPerSecond) {
            return ['ok' => true, 'credited' => $credited, 'warning' => null, 'blocked_for' => 0];
        }

        $strikeGate = "abuse:strike_gate:{$action}:{$userId}";
        if (!Redis::set($strikeGate, 1, 'EX', self::STRIKE_GATE, 'NX')) {
            return ['ok' => true, 'credited' => $credited, 'warning' => null, 'blocked_for' => 0];
        }

        $strikeKey = "abuse:strikes:{$action}:{$userId}";
        $strikes = (int) Redis::incr($strikeKey);
        if ($strikes === 1) {
            Redis::expire($strikeKey, self::STRIKE_TTL);
        }

        if ($strikes <= 2) {
            return [
                'ok' => true,
                'credited' => $credited,
                'warning' => self::warningMessage($maxPerSecond, $strikes),
                'blocked_for' => 0,
            ];
        }

        $duration = $strikes === 3 ? self::SHORT_BLOCK : self::LONG_BLOCK;
        Redis::setex("abuse:block:{$userId}", $duration, $action);

        return [
            'ok' => false,
            'credited' => $credited,
            'warning' => self::blockMessage($duration),
            'blocked_for' => $duration,
        ];
    }

    /** Seconds remaining of a global action-block for [userId] (0 = none). */
    public static function blockedRemaining(int $userId): int
    {
        $ttl = (int) Redis::ttl("abuse:block:{$userId}");

        return max(0, $ttl);
    }

    private static function warningMessage(int $maxPerSecond, int $strike): string
    {
        return "⚠️ تنبيه ({$strike}/2): سرعة استخدامك غير طبيعية — تجاوزت الحد المسموح ({$maxPerSecond} ضغطات في الثانية). "
            . 'لو تكرر ذلك سيتم إيقافك مؤقتاً عن التفاعل.';
    }

    private static function blockMessage(int $seconds): string
    {
        $label = $seconds >= 120 ? (int) ($seconds / 60) . ' دقائق' : 'دقيقة واحدة';

        return "🚫 تم إيقافك عن التفاعل لمدة {$label} بسبب الاستخدام المفرط "
            . '(تجاوزت الحد المسموح من الضغطات في الثانية). عند انتهاء المدة يمكنك المتابعة بشكل طبيعي.';
    }
}
