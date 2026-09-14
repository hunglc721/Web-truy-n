<?php

namespace App\Data;

final readonly class RecommendationPreference
{
    private const DIMENSIONS = ['genres', 'themes', 'settings', 'character_traits', 'tones', 'relationships'];

    private function __construct(private array $data) {}

    public static function fromArray(array $data): self
    {
        $normalized = [];
        $exclude = self::normalizeList($data['exclude'] ?? []);
        foreach (self::DIMENSIONS as $field) {
            $normalized[$field] = array_values(array_diff(self::normalizeList($data[$field] ?? []), $exclude));
        }
        $normalized['status'] = self::normalizeString($data['status'] ?? null);
        $normalized['exclude'] = $exclude;
        // Retain AI-02 clarification fields, without including recommendation results.
        $normalized['needs_more_info'] = ($data['needs_more_info'] ?? false) === true;
        $normalized['follow_up_question'] = $normalized['needs_more_info']
            ? self::normalizeString($data['follow_up_question'] ?? null) : null;

        return new self($normalized);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function merge(self $newPreference): self
    {
        $merged = $this->data;
        $new = $newPreference->data;
        foreach (self::DIMENSIONS as $field) {
            if ($new[$field] !== []) {
                $merged[$field] = $new[$field];
            }
        }
        $merged['status'] = $new['status'] ?? $merged['status'];
        $merged['exclude'] = array_merge($merged['exclude'], $new['exclude']);
        $merged['needs_more_info'] = $new['needs_more_info'];
        $merged['follow_up_question'] = $new['follow_up_question'];

        return self::fromArray($merged);
    }

    private static function normalizeList(mixed $values): array
    {
        $result = [];
        foreach (is_array($values) ? $values : [$values] as $value) {
            if (($value = self::normalizeString($value)) !== null) {
                $result[] = $value;
            }
        }

        return array_values(array_unique($result));
    }

    private static function normalizeString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
