<?php

namespace App\Services\AI\Agent;

/**
 * Collects the only facts the model is allowed to mention: comic ids/titles
 * seen in backend data plus every number that already exists in that data.
 */
final class AgentFactSheet
{
    public function build(array $candidates, array $toolResults): array
    {
        $titles = [];
        $ids = [];
        $meta = [];
        $numbers = [];

        foreach ($candidates as $candidate) {
            if (! is_array($candidate) || ! isset($candidate['id'], $candidate['title'])) {
                continue;
            }
            $id = (int) $candidate['id'];
            $ids[$id] = true;
            $titles[AgentText::normalize((string) $candidate['title'])] = true;
            $score = $candidate['score'] ?? null;
            $meta[$id] = [
                'score' => is_int($score) ? $score : null,
                'matched_reasons' => $this->strings($candidate['matched_reasons'] ?? []),
            ];
            $this->walk($candidate, $titles, $ids, $meta, $numbers);
        }

        foreach ($toolResults as $result) {
            if (! is_array($result) || ($result['ok'] ?? false) !== true) {
                continue;
            }
            $this->walk($result['data'] ?? null, $titles, $ids, $meta, $numbers);
        }

        // Counts the model may legitimately mention ("3 bộ", "2 chương").
        $numbers[(string) count($candidates)] = true;
        $numbers[(string) count($ids)] = true;
        $numbers[(string) count($toolResults)] = true;

        return [
            'titles' => array_keys($titles),
            'ids' => array_keys($ids),
            'meta' => $meta,
            'numbers' => array_keys($numbers),
        ];
    }

    private function walk(mixed $value, array &$titles, array &$ids, array &$meta, array &$numbers): void
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                $numbers[(string) count($value)] = true;
            }
            if (isset($value['id'], $value['title']) && is_numeric($value['id']) && is_string($value['title'])) {
                $id = (int) $value['id'];
                $ids[$id] = true;
                $titles[AgentText::normalize($value['title'])] = true;
                $score = $value['score'] ?? null;
                if (! isset($meta[$id])) {
                    $meta[$id] = [
                        'score' => is_int($score) ? $score : (is_float($score) ? (int) round($score) : null),
                        'matched_reasons' => $this->strings($value['matched_reasons'] ?? []),
                    ];
                }
            }
            foreach ($value as $item) {
                $this->walk($item, $titles, $ids, $meta, $numbers);
            }

            return;
        }

        if (is_int($value) || is_float($value)) {
            $numbers[(string) $value] = true;
        } elseif (is_string($value)) {
            foreach (AgentText::numbers($value) as $number) {
                $numbers[$number] = true;
            }
        }
    }

    /** @return list<string> */
    private function strings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_string'));
    }
}
