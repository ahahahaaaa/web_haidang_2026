<?php

$baseUrl = env('TOUR_SYNC_API_BASE_URL') ?: env('CUSTOMER_LOYALTY_API_BASE_URL', 'https://haidangtravel.local:8443/api');

return [
    'base_url' => $baseUrl,
    'login_path' => env('TOUR_SYNC_LOGIN_PATH') ?: '/DashboardLogin',
    'tours_path' => env('TOUR_SYNC_TOURS_PATH') ?: '/tour/agency/sync/tours',
    'startdates_path' => env('TOUR_SYNC_STARTDATES_PATH') ?: '/tour/agency/sync/startdates',
    'push_enabled' => filter_var(env('TOUR_SYNC_PUSH_ENABLED', true), FILTER_VALIDATE_BOOL),
    'push_path' => env('TOUR_SYNC_PUSH_PATH') ?: '/tour/agency/sync/cms-updates',
    'push_queue' => env('TOUR_SYNC_PUSH_QUEUE', 'default'),
    'username' => env('TOUR_SYNC_API_USERNAME') ?: env('CUSTOMER_LOYALTY_API_USERNAME'),
    'password' => env('TOUR_SYNC_API_PASSWORD') ?: env('CUSTOMER_LOYALTY_API_PASSWORD'),
    'token' => env('TOUR_SYNC_API_TOKEN') ?: env('CUSTOMER_LOYALTY_API_TOKEN'),
    'token_ttl_minutes' => (int) (env('TOUR_SYNC_API_TOKEN_TTL_MINUTES') ?: env('CUSTOMER_LOYALTY_API_TOKEN_TTL_MINUTES', 55)),
    'verify_ssl' => filter_var(env('TOUR_SYNC_API_VERIFY_SSL', ! str_contains((string) $baseUrl, '.local')), FILTER_VALIDATE_BOOL),
    'connect_timeout_seconds' => (int) env('TOUR_SYNC_API_CONNECT_TIMEOUT', 5),
    'timeout_seconds' => (int) env('TOUR_SYNC_API_TIMEOUT', 20),
    'per_page' => (int) env('TOUR_SYNC_API_PER_PAGE', 100),
];
