<?php

return [
    'enabled' => env('FRONTSITE_CACHE_ENABLED', env('APP_ENV') !== 'testing'),

    'middleware' => [
        'enabled' => env('FRONTSITE_RESPONSE_CACHE_ENABLED', env('APP_ENV') !== 'testing'),
        'header' => 'X-Frontsite-Cache',
    ],

    'ttl' => [
        'default' => (int) env('FRONTSITE_CACHE_TTL', 1800),
        'chrome' => (int) env('FRONTSITE_CACHE_CHROME_TTL', 21600),
        'query' => (int) env('FRONTSITE_CACHE_QUERY_TTL', 600),
        'sitemap' => (int) env('FRONTSITE_CACHE_SITEMAP_TTL', 3600),
        'response' => (int) env('FRONTSITE_RESPONSE_CACHE_TTL', 1800),
    ],

    'stale' => [
        'enabled' => env('FRONTSITE_CACHE_STALE_ENABLED', true),
        'sitemap' => [
            (int) env('FRONTSITE_CACHE_SITEMAP_FRESH_TTL', 3600),
            (int) env('FRONTSITE_CACHE_SITEMAP_STALE_TTL', 21600),
        ],
    ],
];
