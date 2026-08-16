<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'now_payments' =>[
        'api_key' => env('NOWPAYMENTS_API_KEY'),
        'callback_url' => env('NOWPAYMENTS_CALLBACK_URL'),
        'ipn_secret' => env('NOWPAYMENTS_IPN_SECRET'),
    ],

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'media_analyzer' => [
        'url' => env('MEDIA_ANALYZER_URL'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'fawry' => [
        "fawry_secret"          => env('FAWRY_SECRET_KEY'),
        "fawry_merchant_code"   => env('FAWRY_MERCHANT_CODE'),
        "fawry_return_url"      => env('FAWRY_RETURN_URL','/admin/payment-with-method'),
        "fawry_url"        => env('FAWRY_URL','https://atfawry.fawrystaging.com/fawrypay-api/api/payments/init'),
        "fawry_webhook_url"        => env('FAWRY_WEBHOOK_URL','https://'),
    ],

    'utd_fawry' => [
        "utd_fawry_secret"          => env('FAWRY_SECRET_KEY'),
        "utd_fawry_merchant_code"   => env('FAWRY_MERCHANT_CODE'),
        "utd_url"               => env('UTD_URL','http://utd_backend.test/api/fawry-initial'),
        "utd_fawry_return_url"      => env('FAWRY_RETURN_URL','/admin/payment-with-method'),
        "utd_fawry_url"        => env('FAWRY_URL','https://atfawry.fawrystaging.com/fawrypay-api/api/payments/init'),
    ],

    'utd_paymob' => [
        "utd_paymob_secret"          => env('UTD_PAYMOB_SECRET'),
        "utd_paymob_merchant_code"   => env('UTD_PAYMOB_MERCHANT_CODE'),
        "utd_url"                    => env('UTD_PAYMOB_URL', 'http://utd_backend.test/api/paymob-initial'),
        "utd_paymob_return_url"      => env('UTD_PAYMOB_RETURN_URL', '/admin/payment-with-method'),
        "utd_paymob_url"             => env('UTD_PAYMOB_URL'),
    ],

    'zinipay' => [
        "api_key"          => env('ZINIPAY_API_KEY'),
        "url"              => env('ZINIPAY_URL','https://api.zinipay.com/v1/payment/create'),
    ],

    // White-label: Firebase Admin SDK credentials. Resolved at runtime via
    // Common::firebaseCredentials() (DB setting 'firebase_service_account_json'
    // FIRST, then this env-driven file-path fallback). Empty default — a clone
    // leaves it blank and sets the SA JSON from the admin panel instead.
    'firebase' => [
        'credentials' => env('FILE_NAME', ''),
    ],

    // White-label: UTD media-analyze endpoint. Per-app, env-driven, empty default
    // (a clone leaves it blank and the mp4 type-detection step is simply skipped).
    'utd_media' => [
        'analyze_url' => env('UTD_MEDIA_ANALYZE_URL', ''),
    ],

    // UTD Stream base URL. Production default is the direct backend endpoint
    // (see UtdStreamTrait); a clone (test / Meow Live) overrides via env
    // UTD_STREAM_BASE_URL or the runtime `utd_stream_base_url` config row.
    'utd_stream' => [
        'base_url' => env('UTD_STREAM_BASE_URL', 'https://engine.udt-stream.com/api/v1'),
    ],

];
