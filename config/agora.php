<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Agora App ID
    |--------------------------------------------------------------------------
    |
    | The App ID from your Agora Console project. Used by both the backend
    | token generator and the Flutter client (via /config/settings).
    |
    */
    'app_id' => env('AGORA_APP_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Agora App Certificate
    |--------------------------------------------------------------------------
    |
    | The App Certificate from your Agora Console project. This is used
    | ONLY on the server side to sign RTC tokens. It must NEVER be exposed
    | to the client.
    |
    */
    'app_certificate' => env('AGORA_APP_CERTIFICATE', ''),

    /*
    |--------------------------------------------------------------------------
    | Agora Token Expiry (seconds)
    |--------------------------------------------------------------------------
    |
    | Default lifetime for generated RTC tokens. Max allowed by Agora is
    | 86400 (24 h). The Flutter client should request a refresh before
    | expiry via the onTokenPrivilegeWillExpire callback.
    |
    */
    'token_expiry' => (int) env('AGORA_TOKEN_EXPIRY', 3600),
];
