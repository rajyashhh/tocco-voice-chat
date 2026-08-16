<?php

return [
    'apiKey' => env('FIREBASE_API_KEY'),
    'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
    'projectId' => env('FIREBASE_PROJECT_ID'),
    'storageBucket' => env('FIREBASE_STORAGE_BUCKET'),
    'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID'),
    'appId' => env('FIREBASE_APP_ID'),
    'vapid_key' => env('FIREBASE_VAPID_KEY'),
    // White-label: Firebase Admin SDK credentials file path. Resolved at runtime
    // via Common::firebaseCredentials() which reads the DB setting
    // 'firebase_service_account_json' FIRST, then falls back to this file path.
    // Empty (no FILE_NAME env) yields a neutral empty default instead of the
    // base path, so a clone without the env stays blank until the admin sets it.
    'credentials' => env('FILE_NAME') ? base_path(env('FILE_NAME')) : '',
];
