<?php

namespace App\Services;

use App\Data\RecommendationPreference;
use App\Models\User;
use App\Services\AI\PreferenceParser;
use Illuminate\Validation\ValidationException;

class RecommendationChatService
{
    public function __construct(
        private RecommendationConversationService $conversations,
        private PreferenceParser $parser,
        private PreferenceRecommendationService $recommendations,
        private RecommendationResponseService $responses,
        private RecommendationChatMemory $memory,
    ) {}

    /** Identity and quota scopes come exclusively from the backend controller. */
    public function reply(array $input, ?User $user, array $guestQuotaScopes = []): array
    {
        $token = $user === null ? ($input['conversation_token'] ?? null) : null;
        $conversation = $this->conversations->getOrCreate($user, $token);
        $previous = RecommendationPreference::fromArray($conversation->preferences_json)->toArray();
        $scope = $user !== null ? 'user:'.$user->id : 'conversation:'.$conversation->id;
        $isQuickReply = isset($input['quick_reply']);
        $userMessage = (string) ($isQuickReply ? $input['quick_reply'] : $input['message']);
        // Recent turns are advisory context for the model only; state still lives in the DTO.
        $memoryScope = 'conversation:'.$conversation->id;
        $history = $this->memory->recent($memoryScope);
        $parsed = $isQuickReply
            ? $this->parser->parseQuickReply($input['quick_reply'])
            : $this->parser->parseNaturalLanguage($userMessage, $scope,
                $user === null ? $guestQuotaScopes : [], $previous, $history);
        if (! $parsed['success']) {
            throw ValidationException::withMessages([
                $isQuickReply ? 'quick_reply' : 'message' => 'Lựa chọn hoặc nội dung không hợp lệ.',
            ]);
        }

        $preference = $this->conversations->updatePreference($conversation->id,
            RecommendationPreference::fromArray($parsed['preferences']), $user, $conversation->session_token);
        $data = $preference->toArray();
        $base = ['conversation_token' => $conversation->session_token, 'preferences' => $data];
        $hasSignal = $data['status'] !== null;
        foreach (['genres', 'themes', 'character_traits', 'settings', 'tones'] as $field) {
            $hasSignal = $hasSignal || $data[$field] !== [];
        }
        $genreOnly = $data['genres'] !== [] && $data['status'] === null && $data['exclude'] === [];
        foreach (['themes', 'character_traits', 'settings', 'tones', 'relationships'] as $field) {
            $genreOnly = $genreOnly && $data[$field] === [];
        }
        // A vague follow-up ("cảm ơn", "bộ nào vui nhất?") keeps the current results
        // instead of resetting the chat to a clarifying question.
        $question = ! $hasSignal || $genreOnly;
        $results = $question ? [] : $this->recommendations->recommend($preference, 5)->map(function (array $result) {
            $comic = $result['comic'];

            return ['id' => $comic->id, 'title' => $comic->title, 'slug' => $comic->slug,
                'cover' => $comic->cover_url, 'score' => $result['score'],
                'matched_reasons' => $result['matched_reasons'], 'url' => route('comics.show', $comic->slug)];
        })->all();

        $response = ['type' => $question ? 'question' : 'recommendations', ...$base,
            ...$this->responses->generate($userMessage, $data, $results, [
                'previous' => $previous, 'delta' => $parsed['preferences'], 'question' => $question,
                'quick_reply' => $isQuickReply, 'parser_source' => $parsed['source'] ?? null,
                'parser_cached' => $parsed['cached'] ?? false, 'scope' => $scope,
                'history' => $history,
                'quota_scopes' => $user === null ? $guestQuotaScopes : [],
            ])];

        // Keep the transcript so the next turn can resolve "thêm", "cái đó", or a greeting.
        $this->memory->append($memoryScope, 'user', $userMessage);
        $this->memory->append($memoryScope, 'assistant', (string) ($response['message'] ?? ''));

        // Debug-only, non-secret diagnostics for diagnosing a fallback parser.
        // Hidden when APP_DEBUG is off so public responses keep their shape.
        if (config('app.debug')) {
            $response['ai_debug'] = [
                'source' => $parsed['source'] ?? null,
                'cached' => (bool) ($parsed['cached'] ?? false),
                'fallback_reason' => $parsed['fallback_reason'] ?? null,
                'enabled' => (bool) config('ai.enabled'),
                'provider' => config('ai.provider'),
                'gemini' => (bool) config('ai.gemini'),
                'model' => config('ai.model'),
                'base_url_host' => parse_url((string) config('ai.base_url'), PHP_URL_HOST) ?: null,
                'key_set' => (bool) config('ai.api_key'),
            ];
        }

        return $response;
    }
}
