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
    ) {}

    /** Identity and quota scopes come exclusively from the backend controller. */
    public function reply(array $input, ?User $user, array $guestQuotaScopes = []): array
    {
        $token = $user === null ? ($input['conversation_token'] ?? null) : null;
        $conversation = $this->conversations->getOrCreate($user, $token);
        $previous = RecommendationPreference::fromArray($conversation->preferences_json)->toArray();
        $scope = $user !== null ? 'user:'.$user->id : 'conversation:'.$conversation->id;
        $isQuickReply = isset($input['quick_reply']);
        $parsed = $isQuickReply
            ? $this->parser->parseQuickReply($input['quick_reply'])
            : $this->parser->parseNaturalLanguage($input['message'], $scope, $user === null ? $guestQuotaScopes : []);
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
        $question = $data['needs_more_info'] || ! $hasSignal || $genreOnly;
        $results = $question ? [] : $this->recommendations->recommend($preference, 5)->map(function (array $result) {
            $comic = $result['comic'];

            return ['id' => $comic->id, 'title' => $comic->title, 'slug' => $comic->slug,
                'cover' => $comic->cover_url, 'score' => $result['score'],
                'matched_reasons' => $result['matched_reasons'], 'url' => route('comics.show', $comic->slug)];
        })->all();

        return ['type' => $question ? 'question' : 'recommendations', ...$base,
            ...$this->responses->generate($input['quick_reply'] ?? $input['message'], $data, $results, [
                'previous' => $previous, 'delta' => $parsed['preferences'], 'question' => $question,
                'quick_reply' => $isQuickReply, 'parser_source' => $parsed['source'] ?? null,
                'parser_cached' => $parsed['cached'] ?? false, 'scope' => $scope,
                'quota_scopes' => $user === null ? $guestQuotaScopes : [],
            ])];
    }
}
