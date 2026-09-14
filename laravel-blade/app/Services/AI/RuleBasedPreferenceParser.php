<?php

namespace App\Services\AI;

use App\Services\RecommendationTaxonomyService;

class RuleBasedPreferenceParser
{
    public function __construct(private RecommendationTaxonomyService $taxonomy) {}

    private const KEYWORDS = [
        'genres' => [
            'Fantasy' => ['fantasy'], 'Action' => ['action'], 'Romance' => ['romance'],
            'Horror' => ['horror'], 'School Life' => ['school', 'học đường'],
        ],
        'themes' => [
            'System' => ['system', 'hệ thống'], 'Dungeon' => ['dungeon'],
            'Revenge' => ['trả thù', 'revenge'], 'Isekai' => ['isekai', 'chuyển sinh'],
            'Regression' => ['regression', 'hồi quy'], 'Cultivation' => ['cultivation', 'tu tiên'],
            'Survival' => ['survival', 'sinh tồn'],
        ],
        'character_traits' => [
            'Overpowered MC' => ['main bá', 'main mạnh'],
            'Weak to Strong' => ['yếu rồi mạnh', 'từ yếu thành mạnh', 'yếu lên mạnh'],
            'Smart MC' => ['main thông minh'], 'Anti Hero' => ['anti hero', 'phản anh hùng'],
        ],
        'settings' => ['Modern' => ['hiện đại'], 'School' => ['học đường']],
        'tones' => ['Dark' => ['dark', 'tối', 'tăm tối'], 'Comedy' => ['hài'], 'Emotional' => ['cảm động']],
    ];

    public function parse(string $message): array
    {
        $allowed = $this->allowed();
        $preferences = array_fill_keys(array_keys($allowed), []);
        $preferences += ['status' => null, 'exclude' => [], 'needs_more_info' => false, 'follow_up_question' => null];
        $message = mb_strtolower($message);
        $originalMessage = $message;

        // Consume negated taxonomy aliases before any positive matching.
        foreach ($allowed as $field => $values) {
            foreach ($values as $value) {
                foreach ($this->keywords($field, $value) as $keyword) {
                    $negative = '/(?<![\p{L}\p{N}])(?:không(?:\s+(?:cần|muốn|thích|có))?|bỏ|loại(?:\s+bỏ)?)\s+'
                        .preg_quote($keyword, '/').'(?![\p{L}\p{N}])/u';
                    if (preg_match($negative, $originalMessage)) {
                        $preferences['exclude'][] = $value;
                        $message = preg_replace($negative, ' ', $message);
                    }
                }
            }
        }
        $preferences['exclude'] = array_values(array_unique($preferences['exclude']));
        foreach ($allowed as $field => $values) {
            foreach ($values as $value) {
                $keywords = $this->keywords($field, $value);
                foreach ($keywords as $keyword) {
                    if ($this->contains($message, $keyword) && ! in_array($value, $preferences['exclude'], true)) {
                        $preferences[$field][] = $value;
                        break;
                    }
                }
            }
        }
        foreach (['completed' => ['hoàn thành', 'full'], 'ongoing' => ['đang ra']] as $status => $keywords) {
            foreach ($keywords as $keyword) {
                if ($this->contains($message, $keyword) && in_array($status, RecommendationTaxonomyService::STATUSES, true)) {
                    $preferences['status'] = $status;
                    break 2;
                }
            }
        }
        $hasSignal = $preferences['status'] !== null || $preferences['exclude'] !== [];
        foreach (array_keys($allowed) as $field) {
            $hasSignal = $hasSignal || $preferences[$field] !== [];
        }
        $preferences['needs_more_info'] = ! $hasSignal;
        $preferences['follow_up_question'] = $hasSignal ? null : 'Bạn thích thể loại hoặc kiểu nhân vật như thế nào?';

        return $preferences;
    }

    /** Quick replies return a delta, never a merged preference state. */
    public function quickReply(string $label): ?array
    {
        $label = mb_strtolower(trim($label));
        $remove = str_starts_with($label, 'bỏ ');
        $allow = str_starts_with($label, 'cho phép ');
        if ($remove || $allow) {
            $label = mb_substr($label, $remove ? 3 : 9);
        }
        $aliases = [
            'main op' => ['character_traits', 'Overpowered MC'],
            'weak → strong' => ['character_traits', 'Weak to Strong'],
            'completed' => ['status', 'completed'],
        ];
        $allowed = $this->allowed();
        $allowed['status'] = RecommendationTaxonomyService::STATUSES;
        foreach ($allowed as $field => $values) {
            foreach ($values as $value) {
                if ($label === mb_strtolower($value) || ($aliases[$label] ?? null) === [$field, $value]) {
                    if ($allow && $field !== 'status') {
                        return ['remove' => ['exclude' => [$value]]];
                    }
                    if ($remove && $field !== 'status') {
                        return ['remove' => [$field => [$value]]];
                    }
                    if ($remove || $allow) {
                        return null;
                    }
                    return [$field => $field === 'status' ? $value : [$value]];
                }
            }
        }

        return null;
    }

    private function allowed(): array
    {
        $allowed = $this->taxonomy->all();
        unset($allowed['statuses']);

        return $allowed;
    }

    private function keywords(string $field, string $value): array
    {
        return [...(self::KEYWORDS[$field][$value] ?? []), mb_strtolower($value)];
    }

    private function contains(string $message, string $keyword): bool
    {
        return preg_match($this->pattern($keyword), $message) === 1;
    }

    private function pattern(string $keyword): string
    {
        return '/(?<![\p{L}\p{N}])'.preg_quote($keyword, '/').'(?![\p{L}\p{N}])/u';
    }
}
