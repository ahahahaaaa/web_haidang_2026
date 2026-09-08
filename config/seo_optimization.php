<?php

return [
    'enabled' => env('SEO_OPTIMIZATION_ENABLED', true),
    'site_id' => env('SEO_OPTIMIZATION_SITE_ID', 'haidang-'.env('APP_ENV', 'production')),
    'locale' => 'vi',
    'mcp_enabled' => env('SEO_OPTIMIZATION_MCP_ENABLED', false),
    'mcp_allowed_origins' => [],
    'mcp_max_request_bytes' => 1048576,
    'apply_enabled' => env('SEO_OPTIMIZATION_APPLY_ENABLED', true),
    'rule_version' => 'keyword-audit-1.0',
    'lease_minutes' => 20,
    'max_attempts' => 3,
    'max_patch_characters' => 100000,
    'spreadsheet_id' => env('SEO_OPTIMIZATION_SPREADSHEET_ID'),
    'sheet_endpoint' => env('SEO_OPTIMIZATION_SHEET_ENDPOINT'),
    'sheet_secret' => env('SEO_OPTIMIZATION_SHEET_SECRET'),
    'media_max_bytes' => 10485760,
    'media_max_pixels' => 24000000,
];
