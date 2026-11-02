<?php

namespace App\Services\AI;

use App\Services\RecommendationTaxonomyService;
use Illuminate\Support\Str;

class RuleBasedPreferenceParser
{
    public function __construct(private RecommendationTaxonomyService $taxonomy) {}

    /**
     * Vietnamese aliases and slang for values that actually exist in the
     * taxonomy. Unknown keys are ignored because parse() only walks the
     * values returned by RecommendationTaxonomyService.
     */
    private const KEYWORDS = [
        'genres' => [
            'Fantasy' => ['fantasy', 'huyền huyễn', 'phép thuật', 'pháp thuật'],
            'Action' => ['action', 'hành động', 'chiến đấu', 'đánh nhau'],
            'Romance' => ['romance', 'tình cảm', 'ngôn tình', 'lãng mạn'],
            'Horror' => ['horror', 'kinh dị', 'ma quái', 'rùng rợn'],
            'School Life' => ['school', 'học đường', 'học viện', 'trường học', 'học sinh'],
            'Comedy' => ['comedy', 'hài hước', 'hài'],
            'Adventure' => ['adventure', 'phiêu lưu', 'mạo hiểm'],
            'Mystery' => ['mystery', 'bí ẩn', 'ly kỳ'],
            'Supernatural' => ['supernatural', 'siêu nhiên'],
            'Drama' => ['drama', 'chính kịch'],
            'Tragedy' => ['tragedy', 'bi kịch'],
            'Slice of Life' => ['slice of life', 'đời thường', 'sinh hoạt'],
            'Sports' => ['sports', 'thể thao'],
            'Historical' => ['historical', 'lịch sử', 'dã sử'],
            'Martial Arts' => ['martial arts', 'võ thuật', 'kiếm hiệp'],
            'Psychological' => ['psychological', 'tâm lý'],
            'Sci-Fi' => ['sci-fi', 'khoa học viễn tưởng', 'viễn tưởng'],
            'Detective' => ['detective', 'trinh thám', 'điều tra'],
            'Shounen' => ['shounen', 'thiếu niên'],
            'Shoujo' => ['shoujo', 'thiếu nữ'],
            'Seinen' => ['seinen'],
            'Josei' => ['josei'],
        ],
        'themes' => [
            'System' => ['system', 'hệ thống', 'giao diện hệ thống'],
            'Dungeon' => ['dungeon', 'hầm ngục', 'mê cung'],
            'Revenge' => ['revenge', 'trả thù', 'báo thù', 'phục thù', 'báo oán'],
            'Isekai' => ['isekai', 'chuyển sinh', 'dị giới', 'xuyên không', 'xuyên việt'],
            'Regression' => ['regression', 'hồi quy', 'trùng sinh', 'trở lại quá khứ', 'quay lại quá khứ', 'tái sinh'],
            'Cultivation' => ['cultivation', 'tu tiên', 'tu luyện', 'tu chân', 'luyện đan'],
            'Survival' => ['survival', 'sinh tồn', 'sống sót'],
        ],
        'settings' => [
            'Modern' => ['modern', 'hiện đại', 'đô thị', 'thế giới hiện đại'],
            'School' => ['school', 'học đường', 'trường học', 'học viện'],
            'Medieval' => ['medieval', 'trung cổ', 'thời trung cổ', 'phương tây cổ'],
            'Post Apocalypse' => ['post apocalypse', 'hậu tận thế', 'tận thế', 'apocalypse', 'thảm họa'],
        ],
        'character_traits' => [
            'Overpowered MC' => ['main bá', 'main mạnh', 'main op', 'op mc', 'bá đạo', 'vô địch',
                'giả yếu', 'giấu sức mạnh', 'giấu thực lực', 'main ngầu', 'sảng văn'],
            'Weak to Strong' => ['weak to strong', 'yếu rồi mạnh', 'từ yếu thành mạnh', 'yếu lên mạnh',
                'yếu thành mạnh', 'lột xác', 'phế vật'],
            'Smart MC' => ['smart mc', 'main thông minh', 'thông minh', 'mưu kế', 'mưu mẹo', 'tính toán'],
            'Anti Hero' => ['anti hero', 'anti-hero', 'phản anh hùng', 'phản diện', 'main ác'],
            'Cold MC' => ['cold mc', 'main lạnh', 'lạnh lùng', 'ít nói', 'vô cảm'],
        ],
        'tones' => [
            'Dark' => ['dark', 'tối', 'tăm tối', 'tối tăm', 'hắc ám', 'u ám', 'đen tối'],
            'Comedy' => ['comedy', 'hài hước', 'hài', 'vui vẻ', 'hóm hỉnh'],
            'Emotional' => ['emotional', 'cảm động', 'cảm xúc', 'xúc động', 'nước mắt', 'buồn'],
            'Serious' => ['serious', 'nghiêm túc', 'nghiêm trọng'],
        ],
        'relationships' => [
            'Low Romance' => ['low romance', 'ít romance', 'romance nhẹ', 'tình cảm nhẹ', 'ít tình cảm'],
            'Romance' => ['romance', 'tình cảm', 'ngôn tình', 'lãng mạn'],
            'Harem' => ['harem', 'hậu cung', 'nhiều gái'],
        ],
    ];

    /** Status phrases, checked before the generic completed keyword. */
    private const STATUS_KEYWORDS = [
        'ongoing' => ['đang ra', 'đang tiến hành', 'chưa hoàn thành', 'chưa full', 'chưa kết thúc', 'còn tiếp'],
        'completed' => ['hoàn thành', 'full', 'kết thúc', 'đã end', 'end', 'trọn bộ'],
        'hiatus' => ['tạm ngưng', 'tạm dừng', 'ngưng ra'],
        'cancelled' => ['bị hủy', 'đã hủy', 'drop'],
    ];

    public function parse(string $message): array
    {
        $allowed = $this->allowed();
        $preferences = array_fill_keys(array_keys($allowed), []);
        $preferences += ['status' => null, 'exclude' => [], 'needs_more_info' => false, 'follow_up_question' => null];
        $message = $this->normalize($message);

        // Discover negated taxonomy aliases first, then blank the whole spans
        // before positive matching. Discovery must not mutate the message in
        // between, otherwise one shared alias ("học đường" -> School Life and
        // School) would only exclude the first value it happens to hit.
        $negatedSpans = [];
        foreach ($allowed as $field => $values) {
            foreach ($values as $value) {
                foreach ($this->keywords($field, $value) as $keyword) {
                    foreach ($this->negativePatterns($keyword) as $pattern) {
                        if (preg_match($pattern, $message, $match, PREG_OFFSET_CAPTURE) === 1) {
                            $preferences['exclude'][] = $value;
                            $negatedSpans[] = [$match[0][1], strlen($match[0][0])];
                            break;
                        }
                    }
                }
            }
        }
        $preferences['exclude'] = array_values(array_unique($preferences['exclude']));
        usort($negatedSpans, static fn (array $a, array $b): int => $b[0] <=> $a[0]);
        foreach ($negatedSpans as [$offset, $length]) {
            $message = substr_replace($message, str_repeat(' ', $length), $offset, $length);
        }

        foreach ($allowed as $field => $values) {
            foreach ($values as $value) {
                if (in_array($value, $preferences['exclude'], true)) {
                    continue;
                }
                foreach ($this->keywords($field, $value) as $keyword) {
                    if ($this->contains($message, $keyword)) {
                        $preferences[$field][] = $value;
                        break;
                    }
                }
            }
        }

        $preferences['status'] = $this->status($message);

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

    private function status(string $message): ?string
    {
        foreach (self::STATUS_KEYWORDS as $status => $keywords) {
            if (! in_array($status, RecommendationTaxonomyService::STATUSES, true)) {
                continue;
            }
            foreach ($keywords as $keyword) {
                if ($this->contains($message, $keyword)) {
                    return $status;
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

    /** @return list<string> normalized aliases for one taxonomy value */
    private function keywords(string $field, string $value): array
    {
        $aliases = [...(self::KEYWORDS[$field][$value] ?? []), $value];
        $normalized = [];
        foreach ($aliases as $alias) {
            $alias = $this->normalize($alias);
            if ($alias !== '') {
                $normalized[] = $alias;
            }
        }

        return array_values(array_unique($normalized));
    }

    /** Lowercase, strip Vietnamese diacritics and punctuation, collapse spaces. */
    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        // "tối" (dark) and "tôi" (I) both fold to "toi". Map the unambiguous
        // accented form before folding so "fantasy tối" still means the Dark
        // tone, while an unaccented "toi" is never treated as a preference.
        $text = preg_replace('/(?<![\p{L}\p{N}])tối(?![\p{L}\p{N}])/u', ' dark ', $text) ?? $text;
        $text = Str::ascii($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function contains(string $message, string $keyword): bool
    {
        $keyword = $this->normalize($keyword);

        return $keyword !== '' && preg_match($this->pattern($keyword), $message) === 1;
    }

    /**
     * A handful of one-syllable aliases become ambiguous once diacritics are
     * stripped ("hài" -> "hai" is also the number two / "hại não" / "hải tặc").
     * Skip those collisions while still accepting the diacritic-free spelling.
     */
    private const ALIAS_GUARDS = [
        'hai' => '(?!\s+(?:main|nam|nu|nguoi|ban|dua|con|phe|ben|mat|canh|chiec|nao|tac|long|dao|quan|cang|san|duong|phong|ngoai)\b)',
    ];

    private function pattern(string $keyword): string
    {
        return '/(?<![\p{L}\p{N}])'.preg_quote($keyword, '/').(self::ALIAS_GUARDS[$keyword] ?? '').'(?![\p{L}\p{N}])/u';
    }

    /**
     * Negation variants. Words that collide with common Vietnamese words once
     * diacritics are stripped ("dùng" vs "đừng", "bộ" vs "bỏ", "tranh" vs
     * "tránh", "thể loại") are matched with narrow, unambiguous patterns.
     *
     * @return list<string>
     */
    private function negativePatterns(string $keyword): array
    {
        $keyword = $this->normalize($keyword);
        if ($keyword === '') {
            return [];
        }
        $k = preg_quote($keyword, '/').(self::ALIAS_GUARDS[$keyword] ?? '');
        $start = '(?<![\p{L}\p{N}])';
        $end = '(?![\p{L}\p{N}])';
        $fillers = '(?:(?:can|muon|thich|co|dung|qua|he|han|phai|phan|duoc|hoan toan|het|may|nua|la|'
            .'doc|xem|nhieu|truyen|tranh|bo|the loai|kieu|dang|ve)\s+)*';

        return [
            // không / không cần / không thích / không quá / không có / trừ ...
            '/'.$start.'(?:khong|khg|ko|k|chang|cha|hong|hem|ngoai tru|tru|cam)\s+'.$fillers.$k.$end.'/u',
            // loại bỏ X (not "thể loại X")
            '/'.$start.'loai\s+bo\s+'.$fillers.$k.$end.'/u',
            // bỏ X / bỏ hẳn X / bỏ qua X (not "bộ truyện X")
            '/'.$start.'bo\s+(?:(?:han|hoan toan|het|may|luon|qua)\s+)*'.$k.$end.'/u',
            // đừng có X / đừng đọc X (not "dùng hệ thống")
            '/'.$start.'dung\s+(?:co|can|muon|thich|doc|xem|cho|them|nha|nhe|nao|lam)\s+'.$fillers.$k.$end.'/u',
            // miễn X
            '/'.$start.'mien\s+'.$fillers.$k.$end.'/u',
            // tránh X / tránh xa X (not "truyện tranh X")
            '/(?<!truyen )'.$start.'tranh\s+(?:xa\s+)?'.$fillers.$k.$end.'/u',
            // Trailing negation: "harem thì không", "romance thì thôi".
            '/'.$start.$k.'(?:\s+(?:thi|thoi|nua))?\s+(?:khong|chang|dung)$/u',
        ];
    }
}
