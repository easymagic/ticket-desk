<?php

return [
    'api_token' => env('TICKET_DESK_API_TOKEN', 'testing-token'),

    /*
    | The shop the agent talks to. "local" uses in-process services (same app).
    | "http" calls the real /api/shop endpoints at shop_url, which is what you
    | want when the catalogue lives in another service.
    */
    'shop_driver' => env('SHOP_DRIVER', 'local'),
    'shop_url' => env('SHOP_API_URL', env('APP_URL', 'http://localhost')),

    'max_steps' => (int) env('TICKET_DESK_MAX_STEPS', 8),
    'max_input_tokens' => (int) env('TICKET_DESK_MAX_INPUT_TOKENS', 16000),
    'job_timeout' => (int) env('TICKET_DESK_JOB_TIMEOUT', 120),

    /* Refunds at or above this amount are also sent to the refund reviewer. */
    'refund_review_threshold' => (float) env('TICKET_DESK_REFUND_REVIEW_THRESHOLD', 100),

    /*
    | When false (the default in tests and a fresh checkout), help-doc embeddings
    | are hashed locally so RAG works without an API key. Set true once you have
    | configured OPENAI_COMPATIBLE_URL or another embeddings provider.
    */
    'embeddings_enabled' => (bool) env('TICKET_DESK_EMBEDDINGS_ENABLED', false),
    'embedding_dimensions' => (int) env('TICKET_DESK_EMBEDDING_DIMENSIONS', 16),
];
