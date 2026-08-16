<?php
return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | Centrifugo is the only realtime broadcast transport. In normal app/web
    | requests the BroadcastServiceProvider promotes this to "centrifugo"; the
    | inert default is kept for test/CLI environments (BROADCAST_DRIVER=null/log).
    |
    */

    'default' => env('BROADCAST_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        // Centrifugo HTTP API transport (the single realtime broadcaster).
        // Resolved by the custom "centrifugo" driver registered in
        // BroadcastServiceProvider. Secrets are read from .env
        // (CENTRIFUGO_API_KEY / CENTRIFUGO_HMAC_SECRET).
        'centrifugo' => [
            'driver' => 'centrifugo',
            'api_url' => env('CENTRIFUGO_API_URL'),
            'api_key' => env('CENTRIFUGO_API_KEY'),
            'hmac_secret' => env('CENTRIFUGO_HMAC_SECRET'),
            // Keep the synchronous publish fast so a slow Centrifugo never stalls
            // the request that triggered the broadcast.
            'timeout' => env('CENTRIFUGO_HTTP_TIMEOUT', 3),
            'verify' => env('CENTRIFUGO_HTTP_VERIFY', true),
            // Transport-failure retry (cURL 7/28) inside the same publish call:
            // N extra attempts with a short fixed backoff. HTTP error responses
            // are never retried. Personal-channel publishes that still fail are
            // requeued with backoff (RepublishCentrifugoPublication).
            'retries' => env('CENTRIFUGO_HTTP_RETRIES', 1),
            'retry_backoff_ms' => env('CENTRIFUGO_HTTP_RETRY_BACKOFF_MS', 200),
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
