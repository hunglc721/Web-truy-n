<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Short-lived, per-conversation transcript used to keep multi-turn chats coherent.
 *
 * The transcript is advisory context only: every preference, recommendation and
 * quota decision still comes from the backend. Storage is the application cache,
 * so no schema change is required and entries expire on their own.
 */
class RecommendationChatMemory
{
    private const ROLES = ['user', 'assistant'];

    /** @return list<array{role: string, content: string}> */
    public function recent(string $scope): array
    {
        $entries = Cache::get($this->key($scope));
        if (! is_array($entries)) {
            return [];
        }

        $clean = [];
        foreach ($entries as $entry) {
            if (is_array($entry) && in_array($entry['role'] ?? null, self::ROLES, true)
                && is_string($entry['content'] ?? null) && trim($entry['content']) !== '') {
                $clean[] = ['role' => $entry['role'], 'content' => mb_substr(trim($entry['content']), 0, 400)];
            }
        }

        return $clean;
    }

    public function append(string $scope, string $role, string $content): void
    {
        $content = preg_replace('/\s+/u', ' ', trim($content)) ?? '';
        if (! in_array($role, self::ROLES, true) || $content === '') {
            return;
        }
        $entries = $this->recent($scope);
        $entries[] = ['role' => $role, 'content' => mb_substr($content, 0, 400)];
        $turns = max(2, (int) config('ai.memory_turns', 8));
        $ttl = max(60, (int) config('ai.memory_ttl', 21600));
        Cache::put($this->key($scope), array_slice($entries, -$turns), $ttl);
    }

    public function forget(string $scope): void
    {
        Cache::forget($this->key($scope));
    }

    private function key(string $scope): string
    {
        return 'ai:memory:'.hash('sha256', $scope);
    }
}
