<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\VietnameseHelper;
use App\Models\Comic;
use App\Models\SearchKeyword;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SearchService
{
    private const TTL_LIVE_SEARCH = 300; // 5 phút cache cho tìm kiếm nhanh
    private const TTL_HOT_KEYWORDS = 900; // 15 phút cache cho từ khóa hot

    /**
     * Tìm kiếm nhanh tức thì (Live Autocomplete Search).
     * Hỗ trợ gõ tiếng Việt có dấu HOẶC không dấu.
     *
     * @param  string  $keyword  Từ khóa tìm kiếm
     * @param  int     $limit    Số lượng kết quả tối đa trả về (mặc định 8)
     * @return Collection<Comic>
     */
    public function liveSearch(string $keyword, int $limit = 8): Collection
    {
        $cleanKeyword = trim($keyword);
        if (mb_strlen($cleanKeyword) < 2) {
            return collect();
        }

        $normalized = VietnameseHelper::removeAccents($cleanKeyword);
        $cacheKey = 'search.live.' . md5($normalized) . '.' . $limit;

        $results = Cache::remember($cacheKey, self::TTL_LIVE_SEARCH, function () use ($cleanKeyword, $normalized, $limit) {
            $escapedOriginal   = $this->escapeLikeString($cleanKeyword);
            $escapedNormalized = $this->escapeLikeString($normalized);

            return Comic::query()
                ->where(function (Builder $query) use ($escapedOriginal, $escapedNormalized) {
                    $query->where('title', 'like', "{$escapedOriginal}%")
                        ->orWhere('title_normalized', 'like', "{$escapedNormalized}%")
                        ->orWhere('alt_titles', 'like', "%{$escapedOriginal}%")
                        ->orWhere('alt_titles_normalized', 'like', "%{$escapedNormalized}%")
                        ->orWhereHas('authors', function (Builder $a) use ($escapedOriginal) {
                            $a->where('name', 'like', "{$escapedOriginal}%");
                        })
                        ->orWhereHas('genres', function (Builder $g) use ($escapedOriginal) {
                            $g->where('name', 'like', "{$escapedOriginal}%");
                        });
                })
                ->with(['genres:id,name,slug', 'latestChapter'])
                ->orderByDesc('views')
                ->limit(min(20, max(1, $limit)))
                ->get(['id', 'title', 'slug', 'cover_image', 'status', 'country', 'avg_rating', 'views', 'is_original']);
        });


        return $results;
    }

    /**
     * Lấy danh sách Top từ khoá tìm kiếm hot nhất (Trending Search Terms).
     *
     * @param int $limit
     * @return Collection<SearchKeyword>
     */
    public function getHotKeywords(int $limit = 10): Collection
    {
        return Cache::remember('search.hot_keywords.' . $limit, self::TTL_HOT_KEYWORDS, function () use ($limit) {
            return SearchKeyword::hot($limit)->get();
        });
    }

    /**
     * Tìm kiếm & Lọc nâng cao đa tiêu chí (Advanced Search & Filter).
     *
     * @param  array{
     *     q?: string|null,
     *     genres?: array<string>|string|null,
     *     genre_mode?: string|null,
     *     exclude_genres?: array<string>|string|null,
     *     tags?: array<string>|string|null,
     *     status?: string|null,
     *     country?: string|null,
     *     year?: int|string|null,
     *     is_original?: bool|int|string|null,
     *     min_rating?: float|int|null,
     *     min_chapters?: int|null,
     *     sort?: string|null,
     *     per_page?: int|null
     * } $params
     * @return LengthAwarePaginator
     */
    public function advancedSearch(array $params): LengthAwarePaginator
    {
        $perPage = min(50, max(1, (int) ($params['per_page'] ?? 18)));
        $query   = Comic::query()
            ->with([
                'genres:id,name,slug',
                'authors:id,name',
                'tags:id,name,slug',
                'latestChapter',
            ])
            ->withCount(['chapters' => fn($q) => $q->published()]);

        // 1. Tìm theo từ khóa (hỗ trợ có dấu và không dấu trên Title, Alt Titles, Description, Tác giả, Tag, Thể loại)
        if (!empty($params['q'])) {
            $keyword    = trim((string) $params['q']);
            $normalized = VietnameseHelper::removeAccents($keyword);

            $escapedOriginal   = $this->escapeLikeString($keyword);
            $escapedNormalized = $this->escapeLikeString($normalized);

            $query->where(function (Builder $q) use ($escapedOriginal, $escapedNormalized) {
                $q->where('title', 'like', "{$escapedOriginal}%")
                    ->orWhere('title_normalized', 'like', "{$escapedNormalized}%")
                    ->orWhere('alt_titles', 'like', "%{$escapedOriginal}%")
                    ->orWhere('alt_titles_normalized', 'like', "%{$escapedNormalized}%")
                    ->orWhere('description', 'like', "%{$escapedOriginal}%")
                    ->orWhereHas('authors', fn($a) => $a->where('name', 'like', "{$escapedOriginal}%"))
                    ->orWhereHas('genres', fn($g) => $g->where('name', 'like', "{$escapedOriginal}%"))
                    ->orWhereHas('tags', fn($t) => $t->where('name', 'like', "{$escapedOriginal}%"));
            });

            // Ghi nhận lượt tìm kiếm
            SearchKeyword::record($keyword, 1);
        }

        // 2. Lọc theo Thể loại (hỗ trợ AND hoặc OR)
        $genres = $this->normalizeArrayParam($params['genres'] ?? null);
        $genreMode = strtolower((string) ($params['genre_mode'] ?? 'and'));

        if (!empty($genres)) {
            if ($genreMode === 'or') {
                $query->whereHas('genres', fn($g) => $g->whereIn('slug', $genres));
            } else {
                foreach ($genres as $genreSlug) {
                    $query->whereHas('genres', fn($g) => $g->where('slug', $genreSlug));
                }
            }
        }

        // 3. Loại trừ Thể loại (Exclude Genres)
        $excludeGenres = $this->normalizeArrayParam($params['exclude_genres'] ?? null);
        if (!empty($excludeGenres)) {
            $query->whereDoesntHave('genres', fn($g) => $g->whereIn('slug', $excludeGenres));
        }

        // 4. Lọc theo Tags
        $tags = $this->normalizeArrayParam($params['tags'] ?? null);
        if (!empty($tags)) {
            foreach ($tags as $tagSlug) {
                $query->whereHas('tags', fn($t) => $t->where('slug', $tagSlug));
            }
        }

        // 5. Lọc theo Trạng thái (ongoing, completed, hiatus)
        if (!empty($params['status']) && strtolower((string) $params['status']) !== 'all') {
            $st = strtolower((string) $params['status']);
            $query->where(function (Builder $q) use ($st) {
                $q->where('status', $st)
                    ->orWhere('status', strtoupper($st));
            });
        }

        // 6. Lọc theo Quốc gia / Xuất xứ (JP, KR, CN, VN, manga, manhwa, manhua)
        if (!empty($params['country']) && strtolower((string) $params['country']) !== 'all') {
            $country = strtoupper((string) $params['country']);
            // Map alias: manga -> JP, manhwa -> KR, manhua -> CN, vietnam -> VN
            $countryMap = [
                'MANGA'   => 'JP',
                'MANHWA'  => 'KR',
                'MANHUA'  => 'CN',
                'VIETNAM' => 'VN',
            ];
            $targetCountry = $countryMap[$country] ?? $country;
            $query->where('country', $targetCountry);
        }

        // 7. Lọc theo Năm phát hành
        if (!empty($params['year']) && (int) $params['year'] > 1900) {
            $query->where('released_year', (int) $params['year']);
        }

        // 8. Lọc theo Originals
        if (isset($params['is_original']) && $params['is_original'] !== '' && $params['is_original'] !== 'all') {
            $isOriginal = filter_var($params['is_original'], FILTER_VALIDATE_BOOLEAN);
            $query->where('is_original', $isOriginal);
        }

        // 9. Lọc theo Điểm đánh giá tối thiểu (min_rating: 1.0 - 5.0)
        if (!empty($params['min_rating']) && (float) $params['min_rating'] > 0) {
            $query->where('avg_rating', '>=', (float) $params['min_rating']);
        }

        // 10. Lọc theo số chương tối thiểu (min_chapters)
        if (!empty($params['min_chapters']) && (int) $params['min_chapters'] > 0) {
            $minChap = (int) $params['min_chapters'];
            $query->having('chapters_count', '>=', $minChap);
        }

        // 11. Sắp xếp kết quả (Sort)
        $sortBy = (string) ($params['sort'] ?? 'views');
        match ($sortBy) {
            'rating'       => $query->orderByDesc('avg_rating')->orderByDesc('views'),
            'latest'       => $query->latestUpdated(),
            'alphabetical' => $query->orderBy('title', 'asc'),
            'trending'     => $query->trending(),
            default        => $query->orderByDesc('views'),
        };

        return $query->paginate($perPage);
    }

    /**
     * Escape các ký tự đặc biệt trong LIKE query (chống wildcard injection).
     */
    private function escapeLikeString(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Chuẩn hóa tham số mảng hoặc chuỗi phân tách bởi dấu phẩy.
     *
     * @param  array<string>|string|null  $param
     * @return array<string>
     */
    private function normalizeArrayParam(mixed $param): array
    {
        if (is_array($param)) {
            return array_values(array_filter($param, fn($v) => !empty($v) && $v !== 'all'));
        }

        if (is_string($param) && trim($param) !== '' && $param !== 'all') {
            return array_values(array_filter(explode(',', $param), fn($v) => !empty(trim($v)) && trim($v) !== 'all'));
        }

        return [];
    }
}
