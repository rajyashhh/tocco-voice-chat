<?php

return [
    'base_url' => env('CODAPAY_BASE_URL', 'https://sandbox.codapayments.com/airtime/api/restful/v2.0/Payment/init.json'),
    'api_key' => env('CODAPAY_API_KEY'),
    'project_id' => env('CODAPAY_PROJECT_ID', 289),
    'country' => env('CODAPAY_COUNTRY', 818),
    'pay_type' => env('CODAPAY_PAY_TYPE', 338),
    'currency' => env('CODAPAY_CURRENCY', 818),
];




