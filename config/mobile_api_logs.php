<?php

return [
    'enabled' => env('MOBILE_API_LOG_ENABLED', false),
    'auto_cleanup' => env('MOBILE_API_LOG_AUTO_CLEANUP', false),
    'cleanup_every_days' => max(1, (int) env('MOBILE_API_LOG_CLEANUP_EVERY_DAYS', 1)),
    'retention_days' => max(1, (int) env('MOBILE_API_LOG_RETENTION_DAYS', 30)),
    'max_body_bytes' => max(0, (int) env('MOBILE_API_LOG_MAX_BODY_BYTES', 0)),
];
