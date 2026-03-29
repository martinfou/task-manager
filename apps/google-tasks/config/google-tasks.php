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

    /*
    |--------------------------------------------------------------------------
    | Dashboard stats cache (US-040)
    |--------------------------------------------------------------------------
    |
    | Rows in dashboard_stats_cache are "fresh" for this many minutes. Set
    | higher than your refresh cadence (e.g. GitHub Actions hourly) so the
    | pre-warm job keeps the fast path; stale rows are still served instantly
    | while a background refresh runs.
    |
    */

    'dashboard_stats_cache_ttl_minutes' => (int) env('DASHBOARD_STATS_CACHE_TTL_MINUTES', 90),

    /*
    |--------------------------------------------------------------------------
    | Undo toast (US-029) — deferred delete timer
    |--------------------------------------------------------------------------
    |
    | Default delay before a deleted task is removed on the server, unless the
    | user undoes. Users may override this in Profile; null on the user uses
    | this default.
    |
    */

    'undo_toast_delay_ms' => (int) env('GOOGLE_TASKS_UNDO_TOAST_DELAY_MS', 10_000),

    /*
    |--------------------------------------------------------------------------
    | Semantic search (US-018) — OpenAI-compatible embeddings API
    |--------------------------------------------------------------------------
    |
    | Requires OPENAI_API_KEY (or compatible endpoint) to index task text and
    | run meaning-based search. See docs/SEMANTIC_SEARCH.md.
    */

    'semantic_search' => [
        'enabled' => (bool) env('SEMANTIC_SEARCH_ENABLED', false),
        'openai_api_key' => env('OPENAI_API_KEY'),
        'openai_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'openai_url' => env('OPENAI_EMBEDDINGS_URL', 'https://api.openai.com/v1/embeddings'),
        'index_batch_size' => (int) env('SEMANTIC_INDEX_BATCH_SIZE', 32),
        'max_results' => (int) env('SEMANTIC_SEARCH_MAX_RESULTS', 75),
    ],

];
