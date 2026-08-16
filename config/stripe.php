<?php


return [
    'test_secret_key' => env('STRIPE_TEST_SECRET_KEY', 'sk_test_fallback'),
    'success_url' => env('STRIPE_SUCCESS_URL', 'http://127.0.0.1:8000/success'),
    'cancel_url' => env('STRIPE_CANCEL_URL', 'http://127.0.0.1:8000/cancel'),
    'currency' => env('STRIPE_CURRENCY', 'usd'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    'webhook_url' => env('STRIPE_WEBHOOK_URL', 'http://127.0.0.1:8000/webhook'),
];


