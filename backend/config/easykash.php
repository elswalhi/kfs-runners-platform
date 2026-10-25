<?php

return [
    'pay_api_url' => env('EASYKASH_PAY_API_URL', 'https://back.easykash.net/api/directpayv1/pay'),
    'api_key' => env('EASYKASH_API_KEY'),
    'hmac_secret' => env('EASYKASH_HMAC_SECRET'),
    'cash_expiry' => (int) env('EASYKASH_CASH_EXPIRY', env('EASYKASH_EXPIRY_DURATION', 48)),
    'redirect_url' => env('EASYKASH_REDIRECT_URL', rtrim(env('APP_URL', 'http://localhost'), '/') . '/register'),
];
