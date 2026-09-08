<?php

return [
    'canonical_url' => env('FRONTSITE_CANONICAL_URL', env('APP_ENV') === 'production' ? 'https://haidangtravel.com' : env('APP_URL', 'http://localhost')),

    'canonical_redirect_enabled' => (bool) env('FRONTSITE_CANONICAL_REDIRECT_ENABLED', env('APP_ENV') === 'production'),

    'canonical_redirect_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FRONTSITE_CANONICAL_REDIRECT_HOSTS', 'haidangtravel.com,www.haidangtravel.com,haidangtravel.dtaa-tech.com'))
    ))),

    'company' => [
        'international_travel_license_fallback' => env('COMPANY_INTERNATIONAL_TRAVEL_LICENSE', '79-723/2017/TCDL-GP LHQT'),
    ],
];
