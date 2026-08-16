<?php

return [
    'name' => 'Chat',

    /*
    |--------------------------------------------------------------------------
    | Per-room server_seq generation
    |--------------------------------------------------------------------------
    |
    | Strategy used by NextServerSeqService to allocate the atomic, gap-free
    | per-room sequence number assigned to each chat message before broadcast.
    |
    | "db"    => atomic "UPDATE chat_rooms SET last_seq = last_seq + 1" inside a
    |           short transaction (no SELECT ... FOR UPDATE — see FairLuck 504
    |           incident). The single source of truth is chat_rooms.last_seq.
    | "redis" => per-room Redis INCR (faster under heavy bursts). The Redis key
    |           is mirrored back into chat_rooms.last_seq so the DB stays the
    |           canonical persistent counter and recovery/backfill keep working.
    |
    */
    'seq' => [
        'driver' => env('CHAT_SEQ_DRIVER', 'db'),

        // Application-level key prefix for the Redis strategy. The Redis facade
        // additionally prepends config('database.redis.options.prefix')
        // ([REMOVED]_database_), so the effective key is
        // "[REMOVED]_database_chat:room:{id}:seq".
        'redis_key_prefix' => env('CHAT_SEQ_REDIS_PREFIX', 'chat:room:'),

        // Redis connection used for the seq counter.
        'redis_connection' => env('CHAT_SEQ_REDIS_CONNECTION', 'default'),
    ],
];
