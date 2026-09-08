<?php

return [
    'token' => env('AGENCY_EXPORT_API_TOKEN'),
    'per_page' => (int) env('AGENCY_EXPORT_API_PER_PAGE', 100),
    'max_per_page' => (int) env('AGENCY_EXPORT_API_MAX_PER_PAGE', 500),
];
