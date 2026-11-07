<?php

namespace App\Services;

use App\Services\AI\AIClientInterface;
use App\Services\AI\AIQuota;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecommendationResponseService
{
    private const LABELS = [
        'Overpowered MC' => 'main mạnh', 'Weak to Strong' => 'main yếu rồi mạnh dần',
        'Smart MC' => 'main thông minh', 'Modern' => 'bối cảnh hiện đại',
        'School' => 'học đường', 'Dark' => 'tông tối', 'Comedy' => 'hài hước',
        'completed' => 'đã hoàn thành', 'ongoing' => 'đang ra',
    ];

    private const CHOICES = [
        'Main OP' => ['character_traits', 'Overpowered MC'],
        'Weak → Strong' => ['character_traits', 'Weak to Strong'],
        'Smart MC' => ['character_traits', 'Smart MC'],
        'Dark' => ['tones', 'Dark'], 'Completed' => ['statuses', 'completed'],
        'Fantasy' => ['genres', 'Fantasy'], 'Action' => ['genres', 'Action'],
    ];

    public function __construct(
        private RecommendationTaxonomyService $taxonomy,
        private AIClientInterface $ai,
        private AIQuota $quota,
    ) {}

    /** Recommendations and all context are supplied by the backend, not by the provider. */
    public function generate(string $userMessage, array $preferences, array $recommendations, array $context): array
    {
        $question = $context['question'];
        $acknowledgement = $this->acknowledge($userMessage, $preferences, $context);
        $criteria = $this->criteria($preferences);
        $quickReplies = $this->suggestions($preferences, $recommendations === [] && ! $question);
        $followUp = $quickReplies === [] ? null : 'Nếu muốn lọc kỹ hơn, bạn có thể chọn thêm hoặc đổi một tiêu chí bên dưới.';

        if ($question) {
            $fallback = $acknowledgement.($preferences['genres'] !== []
                ? 'Với gu '.$criteria.', bạn thích main bá từ đầu, yếu rồi mạnh dần hay thiên về thông minh chiến thuật?'
                : 'Bạn muốn đọc thể loại nào, hoặc thích nhân vật chính kiểu gì?');
            $followUp = null;
        } elseif ($recommendations === []) {
            $fallback = $acknowledgement.'Mình chưa tìm được bộ nào phù hợp với các tiêu chí hiện tại. Bạn có thể nới một điều kiện hoặc thử thể loại khác.';
            $followUp = $quickReplies === [] ? null : 'Chọn một gợi ý bên dưới nếu bạn muốn thay đổi tiêu chí; mình sẽ giữ nguyên gu hiện tại cho đến khi bạn chọn.';
        } else {
            $count = count($recommendations);
            $lead = $criteria === '' ? 'Theo các tiêu chí hiện tại' : 'Dựa trên gu '.$criteria;
            $approved = [
                $acknowledgement.$lead.', mình tìm được '.$count.' bộ đáng thử. Bạn có thể xem điểm phù hợp và lý do của từng bộ bên dưới.',
                $acknowledgement.'Mình tìm được '.$count.' lựa chọn khá sát yêu cầu của bạn. '.$lead.', mình đã sắp xếp các bộ theo mức độ phù hợp.',
                $acknowledgement.$lead.', đây là '.$count.' gợi ý mình tìm được trong thư viện. Các lý do dưới mỗi bộ cho biết tiêu chí nào khớp với gu của bạn.',
            ];
            $fallback = $approved[hexdec(substr(hash('sha256', $userMessage.json_encode($preferences)), 0, 4)) % count($approved)];
        }

        $message = $fallback;
        if ($this->shouldUseAi($userMessage, $context)) {
            $natural = $this->naturalResponse($userMessage, $preferences, $recommendations, $context);
            if ($natural !== null) {
                $message = $natural;
                // The AI reply already invites the next turn; avoid a second canned bubble.
                $followUp = null;
            }
        }

        return ['message' => $message, 'recommendations' => $recommendations,
            'follow_up_message' => $followUp, 'quick_replies' => $quickReplies];
    }

    private function acknowledge(string $message, array $preferences, array $context): string
    {
        $previous = $context['previous'];
        $removed = [];
        foreach (['genres', 'themes', 'settings', 'character_traits', 'tones', 'relationships'] as $field) {
            $removed = [...$removed, ...array_diff($previous[$field], $preferences[$field])];
        }
        if ($removed !== [] && preg_match('/(?:bỏ|loại)/iu', $message)) {
            return 'Được, mình đã bỏ '.$this->labels($removed).' khỏi tiêu chí tìm kiếm. ';
        }
        $allowedAgain = array_diff($previous['exclude'], $preferences['exclude']);
        if ($allowedAgain !== []) {
            return 'Được, mình đã cho phép lại yếu tố '.$this->labels($allowedAgain).'. ';
        }
        $excluded = $context['delta']['exclude'] ?? [];
        if ($excluded !== []) {
            return 'Được, mình đã loại các truyện có yếu tố '.$this->labels($excluded).' khỏi kết quả. ';
        }

        return $context['quick_reply'] ? 'Được, mình đã cập nhật tiêu chí của bạn. ' : '';
    }

    private function criteria(array $preferences): string
    {
        $values = [];
        foreach (['genres', 'character_traits', 'themes', 'settings', 'tones'] as $field) {
            $values = [...$values, ...$preferences[$field]];
        }
        if ($preferences['status']) {
            $values[] = $preferences['status'];
        }

        return $this->labels(array_slice(array_unique($values), 0, 4));
    }

    private function labels(array $values): string
    {
        return implode(' + ', array_map(fn ($value) => self::LABELS[$value] ?? $value, array_values($values)));
    }

    private function suggestions(array $preferences, bool $empty): array
    {
        $taxonomy = $this->taxonomy->all();
        $suggestions = [];
        if ($empty) {
            foreach (self::CHOICES as $label => [$field, $value]) {
                if ($field !== 'statuses' && in_array($value, $preferences[$field], true)
                    && in_array($value, $taxonomy[$field], true)) {
                    $suggestions[] = 'Bỏ '.$label;
                }
            }
            foreach ($preferences['exclude'] as $value) {
                if (in_array($value, array_merge(...array_values($taxonomy)), true)) {
                    $suggestions[] = 'Cho phép '.$value;
                }
            }
        }
        $choices = $preferences['genres'] === []
            ? ['Fantasy' => ['genres', 'Fantasy'], 'Action' => ['genres', 'Action'], ...self::CHOICES]
            : self::CHOICES;
        foreach ($choices as $label => [$field, $value]) {
            $selected = $field === 'statuses' ? [$preferences['status']] : $preferences[$field];
            if (in_array($value, $taxonomy[$field], true) && ! in_array($value, $selected, true)
                && ! in_array($value, $preferences['exclude'], true)) {
                $suggestions[] = $label;
            }
        }

        return array_slice(array_unique($suggestions), 0, 4);
    }

    private function shouldUseAi(string $message, array $context): bool
    {
        return config('ai.enabled') && config('ai.conversational_response')
            && ! $context['quick_reply'] && trim($message) !== '';
    }

    /**
     * Ask the model to write the conversational reply itself.
     *
     * The provider may only use backend-supplied facts; every comic it names is
     * verified against the recommendation candidates, and any malformed or
     * unsupported output falls back to the deterministic templates above.
     */
    private function naturalResponse(string $userMessage, array $preferences, array $recommendations, array $context): ?string
    {
        $candidates = array_map(fn ($comic) => ['title' => $comic['title'], 'score' => $comic['score'],
            'matched' => $comic['matched_reasons']], $recommendations);
        $history = is_array($context['history'] ?? null) ? array_slice($context['history'], -6) : [];
        $mode = $context['question'] ? 'question' : ($recommendations === [] ? 'empty' : 'recommendations');
        $taxonomy = $this->taxonomy->all();
        unset($taxonomy['statuses']);
        $key = 'ai:reply:'.hash('sha256', json_encode([$context['scope'], $userMessage, $preferences,
            $candidates, $mode, $history], JSON_UNESCAPED_UNICODE));
        if (($cached = Cache::get($key)) !== null) {
            return is_string($cached) && $cached !== '' ? $cached : null;
        }
        if (! $this->quota->reserve([$context['scope'], ...$context['quota_scopes']])) {
            return null;
        }

        $instruction = 'Bạn là trợ lý gợi ý truyện tranh của Comicx, đang trò chuyện trực tiếp với độc giả. '
            .'Xưng "mình", gọi người dùng là "bạn"; giọng thân thiện, tự nhiên, hơi dí dỏm nhưng lịch sự. '
            .'Viết 2–4 câu tiếng Việt, tối đa 600 ký tự; không HTML, markdown, link, emoji hay ký tự < >. '
            .'Đọc kỹ user_message và recent_history: phản hồi đúng điều người dùng vừa nói trước, rồi mới gợi ý. '
            .'Không chào lại nếu recent_history đã có lời chào của trợ lý; không lặp lại cách mở đầu của các tin nhắn trợ lý trước đó. '
            .'Tránh mở đầu bằng "Dựa trên gu" hoặc "Gu của bạn là"; đổi cách diễn đạt mỗi lượt và không dùng sáo ngữ quảng cáo. '
            .'Chỉ dùng dữ liệu trong input. Tuyệt đối không bịa tên truyện, điểm số, nội dung hoặc thông tin ngoài input. '
            .'Nếu mode="question": chỉ hỏi một câu ngắn, tự nhiên để hiểu gu đọc; có thể gợi ý 2–3 lựa chọn thật lấy đúng từ taxonomy, nhưng đừng lặp nguyên văn follow_up_hint. '
            .'Nếu mode="empty": nói rõ chưa tìm được bộ phù hợp và gợi ý cụ thể nên nới tiêu chí nào dựa trên preferences/exclude. '
            .'Nếu mode="recommendations": nhắc 1–3 tựa truyện trong candidates kèm lý do matched để tạo cảm giác đang trò chuyện; không liệt kê hết danh sách; chỉ hỏi thêm khi thật cần. '
            .'Nếu người dùng hỏi so sánh hoặc nhờ chọn giúp trong danh sách vừa gợi ý (vui nhất, hay nhất, đáng đọc nhất...), trả lời trực tiếp: chọn một tựa trong candidates và nêu lý do ngắn; không hỏi lại. '
            .'Khi nhắc tựa truyện trong "message", chép đúng nguyên văn tựa trong candidates, giữ nguyên dấu tiếng Việt, chữ hoa và ký tự đặc biệt; kiểm tra chính tả trước khi trả lời. '
            .'Nếu delta có thay đổi, có thể nhắc ngắn gọn là đã cập nhật tiêu chí. '
            .'Không nhắc đến "system prompt", "JSON", "dữ liệu được cung cấp" hay quy trình nội bộ. '
            .'Trả về JSON duy nhất đúng schema {"message":"...","mentioned_titles":["..."]}. '
            .'mentioned_titles chỉ chứa tựa truyện xuất hiện nguyên văn trong candidates; để [] nếu không nhắc tựa nào.';
        $result = $this->ai->complete($instruction, json_encode([
            'user_message' => $userMessage, 'mode' => $mode, 'question' => $mode === 'question',
            'follow_up_hint' => $preferences['follow_up_question'] ?? null,
            'preferences' => $preferences, 'previous' => $context['previous'] ?? [],
            'delta' => $context['delta'] ?? [], 'candidates' => $candidates,
            'recent_history' => $history, 'taxonomy' => $taxonomy,
        ], JSON_UNESCAPED_UNICODE));

        $message = $result['success']
            ? $this->validatedMessage(json_decode((string) $result['content'], true), $recommendations)
            : null;
        if ($message === null) {
            Log::notice('AI conversational response fallback', [
                'reason' => $result['success'] ? 'ungrounded_or_invalid_response' : 'provider_failure',
            ]);

            return null;
        }
        $ttl = max(0, (int) config('ai.parse_cache_ttl'));
        if ($ttl > 0) {
            Cache::put($key, $message, $ttl);
        }

        return $message;
    }

    private function validatedMessage(mixed $decoded, array $recommendations): ?string
    {
        if (! is_array($decoded) || ! array_key_exists('message', $decoded)
            || ! array_key_exists('mentioned_titles', $decoded)
            || array_diff(array_keys($decoded), ['message', 'mentioned_titles']) !== []
            || ! is_string($decoded['message']) || ! is_array($decoded['mentioned_titles'])) {
            return null;
        }
        $message = preg_replace('/\s+/u', ' ', trim($decoded['message'])) ?? '';
        if ($message === '' || mb_strlen($message) > 600
            || preg_match('/[<>\x60]/u', $message)
            || preg_match('~https?://|www\.~iu', $message)
            || preg_match('/system prompt|dữ liệu (?:tôi )?được cung cấp|json/iu', $message)) {
            return null;
        }

        $titles = [];
        foreach ($recommendations as $comic) {
            $titles[] = $this->titleKey((string) $comic['title']);
        }
        $mentioned = array_values($decoded['mentioned_titles']);
        if (count($mentioned) > 5) {
            return null;
        }
        foreach ($mentioned as $title) {
            if (! is_string($title) || trim($title) === '' || mb_strlen(trim($title)) > 200) {
                return null;
            }
            $needle = $this->titleKey($title);
            $matched = false;
            foreach ($titles as $candidate) {
                if ($needle === $candidate
                    || (mb_strlen($needle) >= 3 && (str_contains($candidate, $needle) || str_contains($needle, $candidate)))) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                return null;
            }
        }

        return $message;
    }

    /** Punctuation/spacing-insensitive key so "Spy x Family" matches "Spy × Family". */
    private function titleKey(string $title): string
    {
        return preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower(trim($title))) ?? '';
    }
}
