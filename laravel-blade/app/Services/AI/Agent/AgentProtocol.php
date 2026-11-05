<?php

namespace App\Services\AI\Agent;

use JsonException;

/**
 * Strict decoder for the JSON tool protocol. Anything outside the contract is
 * rejected, which keeps a compromised or confused model from reaching tools.
 */
final class AgentProtocol
{
    private const MAX_ARGS_BYTES = 4096;

    private const FINAL_KEYS = ['action', 'scope', 'message', 'cited_titles', 'recommendation_ids', 'quick_replies'];

    public function __construct(private ChatToolRegistry $tools) {}

    public function decode(?string $content): ?array
    {
        if (! is_string($content) || trim($content) === '') {
            return null;
        }
        try {
            $data = json_decode($content, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
        if (! is_array($data) || ! is_string($data['action'] ?? null)) {
            return null;
        }

        return match ($data['action']) {
            'tool' => $this->toolDecision($data),
            'final' => $this->finalDecision($data),
            default => null,
        };
    }

    private function toolDecision(array $data): ?array
    {
        if (array_diff(array_keys($data), ['action', 'tool', 'args']) !== []) {
            return null;
        }
        $tool = $data['tool'] ?? null;
        $args = $data['args'] ?? null;
        if (! is_string($tool) || ! $this->tools->allows($tool) || ! is_array($args)) {
            return null;
        }
        // JSON objects decode to maps; a JSON list is not an argument object.
        if ($args !== [] && array_is_list($args)) {
            return null;
        }
        try {
            $encoded = json_encode($args, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            return null;
        }
        if (strlen($encoded) > self::MAX_ARGS_BYTES) {
            return null;
        }

        return ['action' => 'tool', 'tool' => $tool, 'args' => $args];
    }

    private function finalDecision(array $data): ?array
    {
        if (array_diff(array_keys($data), self::FINAL_KEYS) !== []) {
            return null;
        }
        $scope = $data['scope'] ?? null;
        $message = $data['message'] ?? null;
        if (! in_array($scope, ['in_scope', 'out_of_scope'], true) || ! is_string($message)) {
            return null;
        }
        $message = trim($message);
        if ($scope === 'in_scope' && $message === '') {
            return null;
        }
        if (mb_strlen($message) > 4000) {
            return null;
        }

        return [
            'action' => 'final',
            'scope' => $scope,
            'message' => $message,
            'cited_titles' => $this->strings($data['cited_titles'] ?? [], 10, 160),
            'recommendation_ids' => $this->ids($data['recommendation_ids'] ?? []),
            'quick_replies' => $this->strings($data['quick_replies'] ?? [], 4, 40),
        ];
    }

    /** @return list<string> */
    private function strings(mixed $value, int $maxItems, int $maxLength): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) > $maxItems) {
            return [];
        }
        $result = [];
        foreach ($value as $item) {
            if (! is_string($item) || mb_strlen(trim($item)) > $maxLength) {
                continue;
            }
            $item = trim($item);
            if ($item !== '') {
                $result[] = $item;
            }
        }

        return array_values(array_unique($result));
    }

    /** @return list<int> */
    private function ids(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) > 5) {
            return [];
        }
        $ids = [];
        foreach ($value as $id) {
            if (is_int($id) && $id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
