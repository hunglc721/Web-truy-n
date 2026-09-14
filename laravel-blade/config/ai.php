<?php

return [
    'enabled' => (bool) env('AI_ENABLED', true),
    'conversational_response' => (bool) env('AI_CONVERSATIONAL_RESPONSE', true),
    'provider' => env('AI_PROVIDER', 'openai_compatible'),
    'api_key' => env('AI_API_KEY', ''),
    'model' => env('AI_MODEL', ''),
    // API root including its version; the adapter appends /chat/completions.
    'base_url' => env('AI_BASE_URL', ''),
    'timeout' => (int) env('AI_TIMEOUT', 15),
    // Additional attempts, capped at two by the client.
    'retry_times' => (int) env('AI_RETRY_TIMES', 1),
    'max_calls' => (int) env('AI_MAX_CALLS', 10),
    'decay_seconds' => (int) env('AI_RATE_LIMIT_DECAY', 60),
    'parse_cache_ttl' => (int) env('AI_PARSE_CACHE_TTL', 600),

];
