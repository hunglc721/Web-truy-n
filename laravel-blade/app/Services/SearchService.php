<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SearchService
{
    private const TTL_LIVE_SEARCH = 300; // 5 phút cache cho tìm kiếm nhanh

    /**
     * Tìm kiếm nhanh tức thì (Live Autocomplete Search).
     * Phù hợp cho ô input header search trên website.
     *
     * @param  string  $keyword  Từ khóa tìm kiếm
     * @param  int     $limit    Số lượng kết quả tối đa trả về (mặc định 6)
     * @return Collection<Comic>
     */
    public function liveSearch(string $keyword, int $limit = 6): Collection
    {
        $cleanKeyword = trim($keyword);
        if (mb_strlen($cleanKeyword) < 2) {
            return collect();
        }

        $cacheKey = 'search.live.' . md5(mb_strtolower($cleanKeyword)) . '.' . $limit;

        return Cache::remember($cacheKey, self::TTL_LIVE_SEARCH, function () use ($cleanKeyword, $limit) {
            $escaped = $this->escapeLikeString($cleanKeyword);

            return Comic::query()
                ->where(function (Builder $query) use ($escaped) {
                    $query->where('title', 'like', "%{$escaped}%")
                        ->orWhereHas('authors', function (Builder $a) use ($escaped) {
                            $a->where('name', 'like', "%{$escaped}%");
                        })
                        ->orWhereHas('genres', function (Builder $g) use ($escaped) {
                            $g->where('name', 'like', "%{$escaped}%");
                        });
                })
                ->with(['genres:id,name,slug', 'latestChapter'])
                ->orderByDesc('views')
                ->limit(min(20, max(1, $limit)))
                ->get(['id', 'title', 'slug', 'cover_image', 'status', 'avg_rating', 'views', 'is_original']);
        });
    }

    /**
     * Tìm kiếm & Lọc nâng cao đa tiêu chí (Advanced Search & Filter).
     *
     * @param  array{
     *     q?: string|null,
     *     genres?: array<string>|string|null,
     *     tags?: array<string>|string|null,
     *     status?: string|null,
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

        // 1. Tìm theo từ khóa (Title, Description, Tác giả, Tag, Thể loại)
        if (!empty($params['q'])) {
            $keyword = trim((string) $params['q']);
            $escaped = $this->escapeLikeString($keyword);

            $query->where(function (Builder $q) use ($escaped) {
                $q->where('title', 'like', "%{$escaped}%")
                    ->orWhere('description', 'like', "%{$escaped}%")
                    ->orWhereHas('authors', fn($a) => $a->where('name', 'like', "%{$escaped}%"))
                    ->orWhereHas('genres', fn($g) => $g->where('name', 'like', "%{$escaped}%"))
                    ->orWhereHas('tags', fn($t) => $t->where('name', 'like', "%{$escaped}%"));
            });
        }

        // 2. Lọc theo Thể loại (hỗ trợ nhiều thể loại)
        $genres = $this->normalizeArrayParam($params['genres'] ?? null);
        if (!empty($genres)) {
            foreach ($genres as $genreSlug) {
                $query->whereHas('genres', fn($g) => $g->where('slug', $genreSlug));
            }
        }

        // 3. Lọc theo Tags
        $tags = $this->normalizeArrayParam($params['tags'] ?? null);
        if (!empty($tags)) {
            foreach ($tags as $tagSlug) {
                $query->whereHas('tags', fn($t) => $t->where('slug', $tagSlug));
            }
        }

        // 4. Lọc theo Trạng thái (ONGOING, COMPLETED)
        if (!empty($params['status']) && strtolower((string) $params['status']) !== 'all') {
            $query->where('status', strtoupper((string) $params['status']));
        }

        // 5. Lọc theo Originals
        if (isset($params['is_original']) && $params['is_original'] !== '' && $params['is_original'] !== 'all') {
            $isOriginal = filter_var($params['is_original'], FILTER_VALIDATE_BOOLEAN);
            $query->where('is_original', $isOriginal);
        }

        // 6. Lọc theo Điểm đánh giá tối thiểu (min_rating: 1.0 - 5.0)
        if (!empty($params['min_rating']) && (float) $params['min_rating'] > 0) {
            $query->where('avg_rating', '>=', (float) $params['min_rating']);
        }

        // 7. Lọc theo số chương tối thiểu (min_chapters)
        if (!empty($params['min_chapters']) && (int) $params['min_chapters'] > 0) {
            $minChap = (int) $params['min_chapters'];
            $query->having('chapters_count', '>=', $minChap);
        }

        // 8. Sắp xếp kết quả (Sort)
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
