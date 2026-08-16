<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Centrifugo — outside-the-room realtime transport
    |--------------------------------------------------------------------------
    |
    | Dedicated config for the Centrifugo integration (REALTIME_CHAT_REBUILD_PLAN
    | section 4.2). The broadcasting publisher (config/broadcasting.php ->
    | connections.centrifugo) reads api_url/api_key for HTTP publish; this file is
    | the single source for the auth surface built in phase 3:
    |
    |   - hmac_secret  : signs the connection JWT and the 1:1 subscription tokens
    |                    (HS256). Same secret the Centrifugo node verifies tokens
    |                    with (token_hmac_secret_key on the node).
    |   - proxy_secret : shared secret Centrifugo sends on its subscribe-proxy
    |                    HTTP call so Laravel can authenticate that the request
    |                    really came from the node (not a forged client request).
    |
    | Set these secrets in your server-side .env (never commit real values).
    | Generate each one once with:  openssl rand -hex 32
    |
    */

    // Base URL of the Centrifugo HTTP API, e.g. http://centrifugo:8000/api
    'api_url' => env('CENTRIFUGO_API_URL'),

    // API key sent as the X-API-Key header on publish/broadcast calls.
    'api_key' => env('CENTRIFUGO_API_KEY'),

    // HMAC secret (HS256) used to sign connection + subscription JWTs.
    'hmac_secret' => env('CENTRIFUGO_HMAC_SECRET'),

    // Shared secret Centrifugo presents on its subscribe-proxy call so Laravel
    // can verify the caller is the node. Sent in the configured header below.
    'proxy_secret' => env('CENTRIFUGO_PROXY_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Token lifetimes (seconds)
    |--------------------------------------------------------------------------
    |
    | Connection token: refreshed by centrifuge-dart via POST /centrifugo/token
    | before exp. Keep short (1h) so a revoked/banned user loses realtime access
    | within one refresh cycle. Subscription token (1:1 chat) tracks the same.
    |
    */

    'token_ttl' => (int) env('CENTRIFUGO_TOKEN_TTL', 3600),

    'subscription_ttl' => (int) env('CENTRIFUGO_SUBSCRIPTION_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Subscribe-proxy verification
    |--------------------------------------------------------------------------
    |
    | Header Centrifugo is configured to send the proxy_secret in
    | (proxy_http_headers / the static header injected by the node config). The
    | VerifyCentrifugoProxy middleware checks this header against proxy_secret
    | with a timing-safe comparison.
    |
    */

    'proxy_secret_header' => env('CENTRIFUGO_PROXY_SECRET_HEADER', 'X-Centrifugo-Proxy-Secret'),

    /*
    |--------------------------------------------------------------------------
    | Channel namespaces (plan section 4.1)
    |--------------------------------------------------------------------------
    |
    | The canonical channel-name builders used by the auth surface. Kept here so
    | the subscription token's `channel` claim and the subscribe-proxy membership
    | check derive names from one place.
    |
    */

    'channels' => [
        // 1:1 DM channel, deterministic on the ordered participant id pair.
        'dm_prefix'    => 'chat:dm.',
        // Group channel (subscribe proxy authorizes against chat_room_members).
        'group_prefix' => 'groups:room.',
        // Per-user personal channel (user-limited via the `#` boundary).
        'user_prefix'  => 'user:#',
        // Global public banner channel (phase 9 WAVE 1 outside-room banners:
        // banner:gift / banner:lucky_box / banner:boom / banner:zego). Ephemeral,
        // server-publish only; any authenticated client may subscribe.
        'banner_prefix' => 'banner:',
    ],

];
