<?php

// Determine endpoint based on mode
$mode = env('AUTHORIZE_NET_MODE', 'sandbox');
$urls = [
    'sandbox' => 'https://apitest.authorize.net/xml/v1/request.api',
    'production' => 'https://api.authorize.net/xml/v1/request.api',
];

return [
    'api_login_id' => env('AUTHORIZE_NET_API_LOGIN_ID', '5KP3u95bQpv'),
    'transaction_key' => env('AUTHORIZE_NET_TRANSACTION_KEY', '346HZ32z3fP4hTG2'),
    'signature_key' => env('AUTHORIZE_NET_SIGNATURE_KEY', 'Simon'),
    'mode' => $mode,
    'endpoint' => $urls[$mode] ?? $urls['sandbox'],
    'urls' => $urls,
];