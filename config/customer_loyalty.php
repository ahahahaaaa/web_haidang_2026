<?php

$baseUrl = env('CUSTOMER_LOYALTY_API_BASE_URL', 'https://haidangtravel.local:8443/api');

return [
    'base_url' => $baseUrl,
    'login_path' => env('CUSTOMER_LOYALTY_LOGIN_PATH', '/DashboardLogin'),
    'lookup_path' => env('CUSTOMER_LOYALTY_LOOKUP_PATH', '/frontstore/customer-points'),
    'lookup_method' => strtoupper((string) env('CUSTOMER_LOYALTY_LOOKUP_METHOD', 'GET')),
    'gifts_path' => env('CUSTOMER_LOYALTY_GIFTS_PATH', '/frontstore/gifts'),
    'gifts_method' => strtoupper((string) env('CUSTOMER_LOYALTY_GIFTS_METHOD', 'GET')),
    'redeem_path' => env('CUSTOMER_LOYALTY_REDEEM_PATH', '/frontstore/gift-redemption-requests'),
    'redeem_method' => strtoupper((string) env('CUSTOMER_LOYALTY_REDEEM_METHOD', 'POST')),
    'username' => env('CUSTOMER_LOYALTY_API_USERNAME'),
    'password' => env('CUSTOMER_LOYALTY_API_PASSWORD'),
    'token' => env('CUSTOMER_LOYALTY_API_TOKEN'),
    'token_header' => env('CUSTOMER_LOYALTY_API_TOKEN_HEADER', 'Authorization'),
    'token_prefix' => env('CUSTOMER_LOYALTY_API_TOKEN_PREFIX', 'Bearer'),
    'token_ttl_minutes' => (int) env('CUSTOMER_LOYALTY_API_TOKEN_TTL_MINUTES', 55),
    'token_refresh_buffer_seconds' => (int) env('CUSTOMER_LOYALTY_API_TOKEN_REFRESH_BUFFER_SECONDS', 300),
    'verify_ssl' => filter_var(env('CUSTOMER_LOYALTY_API_VERIFY_SSL', ! str_contains((string) $baseUrl, '.local')), FILTER_VALIDATE_BOOL),
    'connect_timeout_seconds' => (int) env('CUSTOMER_LOYALTY_API_CONNECT_TIMEOUT', 5),
    'timeout_seconds' => (int) env('CUSTOMER_LOYALTY_API_TIMEOUT', 20),
    'history_enabled' => filter_var(env('CUSTOMER_LOYALTY_HISTORY_ENABLED', false), FILTER_VALIDATE_BOOL),
];
