<?php


return [
    // ── Feature flag: Redis-authoritative vault path (DANGEROUS, money path) ──
    // OFF (default): processBet wraps the vault write in DB::transaction (legacy,
    // serializes on the single unified_vault row — current production behaviour).
    // ON: Redis becomes the single source of truth for the live vault balance and
    // all heavy DB I/O (history + transaction log + stats) is deferred to an async
    // batch job. Enabling this REQUIRES confirmed Redis persistence (AOF/RDB) and a
    // pre-deploy snapshot — see ownerNotes. The DB value (FairLuckSetting key
    // 'V7_redis_authoritative') overrides this env default at runtime.
    'redis_authoritative' => filter_var(env('FAIRLUCK_REDIS_AUTHORITATIVE', false), FILTER_VALIDATE_BOOLEAN),

    'house_edge_rate' => (float) env('FAIRLUCK_HOUSE_EDGE', 0.02),
    'high_multiplier' => [
        'tiers' => [
            100 => (float) env('FAIRLUCK_TIER_100', 3500),
            250 => (float) env('FAIRLUCK_TIER_250', 9000),
            500 => (float) env('FAIRLUCK_TIER_500', 15500),
        ],
        'probability_floors' => [
            100 => (float) env('FAIRLUCK_TIER_100_PROB', 0.12),
            250 => (float) env('FAIRLUCK_TIER_250_PROB', 0.17),
            500 => (float) env('FAIRLUCK_TIER_500_PROB', 0.22),
        ],
        'weight_boosts' => [
            100 => (float) env('FAIRLUCK_TIER_100_WEIGHT', 14),
            250 => (float) env('FAIRLUCK_TIER_250_WEIGHT', 38),
            500 => (float) env('FAIRLUCK_TIER_500_WEIGHT', 80),
        ],
        'loss_score_weight' => (float) env('FAIRLUCK_LOSS_SCORE_WEIGHT', 1.08),
        'bet_score_weight' => (float) env('FAIRLUCK_BET_SCORE_WEIGHT', 0.14),
        'pool_contribution_rate' => (float) env('FAIRLUCK_POOL_RATE', 0.4),
        'pool_cover_ratio' => (float) env('FAIRLUCK_POOL_COVER_RATIO', 0.85),
        'pool_payout_ratio' => (float) env('FAIRLUCK_POOL_PAYOUT_RATIO', 1.0),
        'big_win_penalty_weight' => (float) env('FAIRLUCK_BIG_WIN_PENALTY_WEIGHT', 3.1),
        'big_win_floor_penalty' => (float) env('FAIRLUCK_BIG_WIN_FLOOR_PENALTY', 1100),
        'small_win_penalty' => (float) env('FAIRLUCK_SMALL_WIN_PENALTY', 0.35),
        'rank_window' => (int) env('FAIRLUCK_RANK_WINDOW', 25),
        'user_ttl' => (int) env('FAIRLUCK_USER_TTL', 604800),
        'leaderboard_limit' => (int) env('FAIRLUCK_LEADERBOARD_LIMIT', 50),
        'pool_alert_floor' => (float) env('FAIRLUCK_POOL_ALERT_FLOOR', 50000),
        'pool_alert_ttl' => (int) env('FAIRLUCK_POOL_ALERT_TTL', 1800),
    ],
    'loss_rotation' => [
        'cooldown_hours' => (int) env('FAIRLUCK_LOSS_ROTATION_HOURS', 6),
        'priority_window' => (int) env('FAIRLUCK_LOSS_ROTATION_WINDOW', 3),
    ],
];
