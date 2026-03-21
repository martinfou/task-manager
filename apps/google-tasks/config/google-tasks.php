<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Tasks REST API
    |--------------------------------------------------------------------------
    */

    'api_base' => env('GOOGLE_TASKS_API_BASE', 'https://tasks.googleapis.com/tasks/v1'),

    /*
    |--------------------------------------------------------------------------
    | Client polling (browser → this app → Google)
    |--------------------------------------------------------------------------
    |
    | Default interval for refreshing lists/tasks in the UI. On HTTP 429 from
    | Google, the client increases delay up to max_backoff_ms (exponential).
    */

    'poll_interval_ms' => (int) env('GOOGLE_TASKS_POLL_INTERVAL_MS', 5000),

    'max_backoff_ms' => (int) env('GOOGLE_TASKS_MAX_BACKOFF_MS', 120_000),

];
