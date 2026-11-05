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

    // Bounded read-only agent. Off by default so existing deterministic behaviour
    // (and CI) stays unchanged until a provider key is configured.
    'agent_enabled' => (bool) env('AI_AGENT_ENABLED', false),
    'agent_max_rounds' => (int) env('AI_AGENT_MAX_ROUNDS', 3),
    'agent_max_tool_calls' => (int) env('AI_AGENT_MAX_TOOL_CALLS', 2),
    'agent_history_turns' => (int) env('AI_AGENT_HISTORY_TURNS', 6),
    'agent_max_output_chars' => (int) env('AI_AGENT_MAX_OUTPUT_CHARS', 600),
    'agent_temperature' => (float) env('AI_AGENT_TEMPERATURE', 0.3),
    'agent_max_tokens' => (int) env('AI_AGENT_MAX_TOKENS', 600),

];
