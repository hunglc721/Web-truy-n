<?php

return [
    'provider' => env('AI_PROVIDER', 'openai_compatible'),
    'api_key' => env('AI_API_KEY', ''),
    'model' => env('AI_MODEL', ''),
    // API root including its version; the adapter appends /chat/completions.
    'base_url' => env('AI_BASE_URL', ''),
    'timeout' => (int) env('AI_TIMEOUT', 15),
    // Additional attempts, capped at two by the client.
    'retry_times' => (int) env('AI_RETRY_TIMES', 1),

    'taxonomy' => [
        'themes' => ['System', 'Dungeon', 'Revenge', 'Survival', 'Isekai', 'Regression', 'Cultivation'],
        'settings' => ['Modern', 'School', 'Medieval', 'Post Apocalypse'],
        'character_traits' => ['Overpowered MC', 'Weak to Strong', 'Smart MC', 'Anti Hero', 'Cold MC'],
        'tones' => ['Dark', 'Comedy', 'Emotional', 'Serious'],
        'relationships' => ['No Romance', 'Low Romance', 'Romance', 'Harem'],
    ],
    'statuses' => ['ongoing', 'completed', 'hiatus', 'cancelled'],
];
