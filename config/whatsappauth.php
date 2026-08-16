<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for your WhatsApp API service.
    | Example: https://api.whatsapp.example.com
    |
    */

    'base_url' => env('WHATSAPP_BASE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp API Token
    |--------------------------------------------------------------------------
    |
    | API token for authentication (stored in Redis as 'whatsapp_token')
    |
    */

    'token' => env('WHATSAPP_TOKEN', ''),

];
