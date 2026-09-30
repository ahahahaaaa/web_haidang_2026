<?php

return [
    'enabled' => (bool) env('PUBLIC_URL_MAPPINGS_ENABLED', true),
    'cache_seconds' => (int) env('PUBLIC_URL_MAPPINGS_CACHE_SECONDS', 300),
];
