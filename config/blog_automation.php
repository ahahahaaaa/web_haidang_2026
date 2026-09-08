<?php

return [
    'queue' => env('BLOG_AUTOMATION_QUEUE', env('SEO_AI_QUEUE', 'seo')),
    'default_author_name' => env('BLOG_AUTOMATION_DEFAULT_AUTHOR', 'Ban biên tập'),
    'default_status' => env('BLOG_AUTOMATION_DEFAULT_STATUS', 'draft'),
    'max_reference_urls' => (int) env('BLOG_AUTOMATION_MAX_REFERENCE_URLS', 5),
    'request_timeout_seconds' => (int) env('BLOG_AUTOMATION_REQUEST_TIMEOUT', 25),
    'connect_timeout_seconds' => (int) env('BLOG_AUTOMATION_CONNECT_TIMEOUT', 10),
    'max_source_content_length' => (int) env('BLOG_AUTOMATION_MAX_SOURCE_CONTENT_LENGTH', 12000),
    'user_agent' => env(
        'BLOG_AUTOMATION_USER_AGENT',
        'ConstructionCompanyBot/1.0 (+https://example.com; compatible; blog automation)',
    ),
];
