<?php

namespace App\Services\AI\Agent;

/**
 * Central place for the agent's hard limits. The agent is opt-in and only runs
 * when a real provider is configured, so existing deterministic behaviour stays
 * the default when no key is present.
 */
final class AgentPolicy
{
    public const OUT_OF_SCOPE_MESSAGE = 'Mình chỉ giúp về truyện và việc đọc trên Comicx thôi. Bạn muốn tìm truyện theo thể loại hay nhân vật chính nào?';

    public const IN_SCOPE_TOPICS = [
        'comic catalog, chapters, genres, tags, authors, status, ratings',
        'finding comics by taste',
        'the signed-in user\'s own library and reading history',
        'how to read comics on Comicx',
    ];

    public function enabled(): bool
    {
        return (bool) config('ai.enabled', false)
            && (bool) config('ai.agent_enabled', false)
            && $this->providerReady();
    }

    /** Mirrors the client guard so we never start a loop that cannot work. */
    public function providerReady(): bool
    {
        $url = rtrim((string) config('ai.base_url'), '/');

        return config('ai.provider') === 'openai_compatible'
            && (string) config('ai.api_key') !== ''
            && (string) config('ai.model') !== ''
            && filter_var($url, FILTER_VALIDATE_URL)
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && parse_url($url, PHP_URL_USER) === null
            && parse_url($url, PHP_URL_QUERY) === null;
    }

    public function maxRounds(): int
    {
        return max(1, min(4, (int) config('ai.agent_max_rounds', 3)));
    }

    public function maxToolCalls(): int
    {
        return max(0, min(5, (int) config('ai.agent_max_tool_calls', 2)));
    }

    public function maxHistoryTurns(): int
    {
        return max(0, min(8, (int) config('ai.agent_history_turns', 6)));
    }

    public function maxOutputChars(): int
    {
        return max(120, min(1200, (int) config('ai.agent_max_output_chars', 600)));
    }

    public function temperature(): float
    {
        return max(0.0, min(1.0, (float) config('ai.agent_temperature', 0.3)));
    }

    public function maxTokens(): int
    {
        return max(64, min(2000, (int) config('ai.agent_max_tokens', 600)));
    }
}
