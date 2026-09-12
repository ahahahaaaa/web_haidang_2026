<?php

return [
    'enabled' => env('SEO_OPTIMIZATION_ENABLED', true),
    'site_id' => env('SEO_OPTIMIZATION_SITE_ID', 'haidang-'.env('APP_ENV', 'production')),
    'locale' => 'vi',
    'mcp_enabled' => env('SEO_OPTIMIZATION_MCP_ENABLED', false),
    'mcp_allowed_origins' => [],
    'mcp_max_request_bytes' => 1048576,
    'apply_enabled' => env('SEO_OPTIMIZATION_APPLY_ENABLED', true),
    'rule_version' => 'direct-content-audit-2.2',
    'lease_minutes' => 20,
    'max_attempts' => 3,
    'processing_score_threshold' => max(0, min(100, (float) env('SEO_OPTIMIZATION_PROCESSING_SCORE_THRESHOLD', 80))),
    'max_patch_characters' => 100000,
    'auto_media_enabled' => (bool) env('SEO_OPTIMIZATION_AUTO_MEDIA_ENABLED', true),
    'schedule_batch_limit' => max(1, min(10, (int) env('SEO_OPTIMIZATION_SCHEDULE_BATCH_LIMIT', 3))),
    'media_max_bytes' => 10485760,
    'media_max_pixels' => 24000000,
    'media_webp_quality' => max(1, min(100, (int) env('SEO_OPTIMIZATION_MEDIA_WEBP_QUALITY', 82))),
];
