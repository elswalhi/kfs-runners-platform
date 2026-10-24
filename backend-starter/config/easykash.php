<?php

return [
    'cash_api_url' => env('EASYKASH_CASH_API_URL', 'https://back.easykash.net/api/cash-api/create'),
    'api_key' => env('EASYKASH_API_KEY'),
    'hmac_secret' => env('EASYKASH_HMAC_SECRET'),
    'expiry_duration' => (int) env('EASYKASH_EXPIRY_DURATION', 48),
];
