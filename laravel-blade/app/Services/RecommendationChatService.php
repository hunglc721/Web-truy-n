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
    ) {}

    /** Identity and quota scopes come exclusively from the backend controller. */
    public function reply(array $input, ?User $user, array $guestQuotaScopes = []): array
    {
        $token = $user === null ? ($input['conversation_token'] ?? null) : null;
        $conversation = $this->conversations->getOrCreate($user, $token);
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
        if ($data['needs_more_info'] || ! $hasSignal) {
            return ['type' => 'question', ...$base,
                'message' => $data['follow_up_question'] ?? 'Bạn thích thể loại hoặc kiểu nhân vật chính như thế nào?',
                'quick_replies' => [],
            ];
        }

        $results = $this->recommendations->recommend($preference, 5)->map(function (array $result) {
            $comic = $result['comic'];

            return ['id' => $comic->id, 'title' => $comic->title, 'slug' => $comic->slug,
                'cover' => $comic->cover_url, 'score' => $result['score'],
                'matched_reasons' => $result['matched_reasons'], 'url' => route('comics.show', $comic->slug)];
        })->all();

        return ['type' => 'recommendations', ...$base, 'recommendations' => $results,
            'message' => $results === [] ? 'Chưa tìm thấy truyện phù hợp với các tiêu chí hiện tại.' : 'Đây là những truyện phù hợp với bạn.'];
    }
}
