<?php

namespace App\Services\AI\Agent;

use App\Models\User;
use App\Services\AI\RuleBasedPreferenceParser;

/**
 * The model may phrase an answer, but every claim must be traceable to backend
 * data. Out-of-scope answers are replaced by a backend-owned message.
 */
final class GroundedMessageValidator
{
    private const FORBIDDEN = '/\b(?:system prompt|prompt he thong|api key|mat khau|database|co so du lieu|sql|schema)\b/iu';

    private const FALLBACK_REPLIES = ['Fantasy', 'Action', 'Romance', 'Completed'];

    public function __construct(
        private AgentPolicy $policy,
        private RuleBasedPreferenceParser $quickReplies,
    ) {}

    /**
     * @param  array{titles: list<string>, ids: list<int>, numbers: list<string>}  $facts
     * @return array{ok: bool, reason?: string, message?: string, recommendation_ids?: list<int>, quick_replies?: list<string>}
     */
    public function validate(array $decision, array $facts, ?User $user = null): array
    {
        if (($decision['scope'] ?? null) === 'out_of_scope') {
            return [
                'ok' => true,
                'message' => AgentPolicy::OUT_OF_SCOPE_MESSAGE,
                'recommendation_ids' => [],
                'quick_replies' => $this->fallbackReplies(),
            ];
        }

        $message = AgentText::clean($decision['message'] ?? null, $this->policy->maxOutputChars() + 1);
        if ($message === '') {
            return $this->failure('empty_message');
        }
        if (mb_strlen($message) > $this->policy->maxOutputChars()) {
            return $this->failure('message_too_long');
        }
        if (preg_match('/[<>{}`]/u', $message) || preg_match('/https?:\/\/|www\./iu', $message)) {
            return $this->failure('unsafe_markup');
        }
        if (preg_match(self::FORBIDDEN, AgentText::normalize($message))) {
            return $this->failure('forbidden_topic');
        }

        $knownNumbers = array_fill_keys(array_map('strval', $facts['numbers'] ?? []), true);
        foreach (AgentText::numbers($message) as $number) {
            if (! isset($knownNumbers[$number])) {
                return $this->failure('ungrounded_number');
            }
        }

        $knownTitles = array_fill_keys($facts['titles'] ?? [], true);
        foreach ($decision['cited_titles'] ?? [] as $title) {
            if (! isset($knownTitles[AgentText::normalize($title)])) {
                return $this->failure('ungrounded_title');
            }
        }

        $knownIds = array_fill_keys(array_map('intval', $facts['ids'] ?? []), true);
        $ids = array_values(array_unique(array_map('intval', $decision['recommendation_ids'] ?? [])));
        foreach ($ids as $id) {
            if (! isset($knownIds[$id])) {
                return $this->failure('ungrounded_recommendation');
            }
        }

        return [
            'ok' => true,
            'message' => $message,
            'recommendation_ids' => $ids,
            'quick_replies' => $this->replies($decision['quick_replies'] ?? [], $user),
        ];
    }

    /** Only quick replies the existing parser already understands are usable. */
    private function replies(array $labels, ?User $user): array
    {
        $replies = [];
        foreach ($labels as $label) {
            if (! is_string($label)) {
                continue;
            }
            $label = AgentText::clean($label, 40);
            if ($label === '' || isset($replies[$label])) {
                continue;
            }
            if ($this->quickReplies->quickReply($label) !== null) {
                $replies[$label] = $label;
            }
            if (count($replies) >= 4) {
                break;
            }
        }

        return $replies === [] ? $this->fallbackReplies() : array_values($replies);
    }

    private function fallbackReplies(): array
    {
        $replies = [];
        foreach (self::FALLBACK_REPLIES as $label) {
            if ($this->quickReplies->quickReply($label) !== null) {
                $replies[] = $label;
            }
        }

        return array_slice($replies, 0, 4);
    }

    private function failure(string $reason): array
    {
        return ['ok' => false, 'reason' => $reason];
    }
}
