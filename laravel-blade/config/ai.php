<?php

use App\Support\AIProviderSettings;

$settings = AIProviderSettings::resolve([
    'provider' => env('AI_PROVIDER'),
    'api_key' => env('AI_API_KEY'),
    'gemini_api_key' => env('GEMINI_API_KEY'),
    'google_api_key' => env('GOOGLE_API_KEY'),
    'google_ai_api_key' => env('GOOGLE_AI_API_KEY'),
    'base_url' => env('AI_BASE_URL'),
    'model' => env('AI_MODEL'),
    'gemini_model' => env('GEMINI_MODEL'),
]);

return [
    'enabled' => (bool) env('AI_ENABLED', true),
    'conversational_response' => (bool) env('AI_CONVERSATIONAL_RESPONSE', true),
    'provider' => $settings['provider'],
    'api_key' => $settings['api_key'],
    'model' => $settings['model'],
    // API root including its version; the adapter appends /chat/completions.
    'base_url' => $settings['base_url'],
    'gemini' => $settings['gemini'],
    // The json_object switch is supported by the Gemini OpenAI-compatible
    // endpoint and improves JSON reliability. Set AI_JSON_MODE=false only for
    // providers that reject it.
    'json_mode' => (bool) env('AI_JSON_MODE', true),
    'timeout' => (int) env('AI_TIMEOUT', 15),
    // Additional attempts, capped at two by the client.
    'retry_times' => (int) env('AI_RETRY_TIMES', 1),
    'max_calls' => (int) env('AI_MAX_CALLS', 10),
    'decay_seconds' => (int) env('AI_RATE_LIMIT_DECAY', 60),
    'parse_cache_ttl' => (int) env('AI_PARSE_CACHE_TTL', 600),

];
