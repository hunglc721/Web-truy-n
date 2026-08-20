<?php

namespace App\Services;

use App\Models\Comic;
use App\Models\ReadingHistory;
use App\Models\Library;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * RecommendationService
 *
 * Gợi ý truyện dựa trên hành vi đọc & tương tác của người dùng (Content-Based Filtering).
 *
 * Thuật toán (Multi-Signal Content Filtering):
 *  1. Lấy preferred genres từ Lịch sử đọc 30 ngày gần nhất + Tủ sách của user (Library)
 *  2. Tìm các bộ truyện cùng thể loại chưa đọc, xếp hạng theo: avg_rating DESC -> views DESC
 *  3. Fallback mượt mà: nếu chưa đủ số lượng, bổ sung Trending / Top Rated Comics
 *  4. Hỗ trợ gợi ý truyện tương tự theo comic cụ thể (Similar Comics)
 *  5. Tích hợp đa tầng Cache (User-level & Guest-level & Comic-level)
 */
class RecommendationService
{
    /**
     * Gợi ý truyện cá nhân hóa cho User đã đăng nhập.
     *
     * @param  User   $user
     * @param  int    $limit
     * @param  array  $excludeComicIds
     * @return Collection<Comic>
     */
    public function forUser(User $user, int $limit = 6, array $excludeComicIds = []): Collection
    {
        $cacheKey = "recommendations.user.{$user->id}.limit_{$limit}";

        return Cache::remember($cacheKey, 600, function () use ($user, $limit, $excludeComicIds) {
            // 1. Lấy danh sách comic IDs đã đọc trong 30 ngày gần nhất
            $readComicIds = ReadingHistory::where('user_id', $user->id)
                ->where('last_read_at', '>=', now()->subDays(30))
                ->pluck('comic_id')
                ->toArray();

            // 2. Lấy comic IDs trong Tủ sách (Bookmark)
            $libraryComicIds = Library::where('user_id', $user->id)
                ->pluck('comic_id')
                ->toArray();

            $allInteractedComicIds = array_unique(array_merge($readComicIds, $libraryComicIds));
            $excludeIds = array_unique(array_merge($allInteractedComicIds, $excludeComicIds));

            // 3. Khai phá preferred genre IDs từ các bộ truyện đã tương tác
            $preferredGenreIds = DB::table('comic_genre')
                ->whereIn('comic_id', $allInteractedComicIds)
                ->pluck('genre_id')
                ->unique()
                ->toArray();

            $recommendations = collect();

            // 4. Tìm truyện cùng thể loại yêu thích (chưa từng đọc/thêm tủ sách)
            if (!empty($preferredGenreIds)) {
                $recommendations = Comic::whereHas('genres', function ($q) use ($preferredGenreIds) {
                        $q->whereIn('genres.id', $preferredGenreIds);
                    })
                    ->whereNotIn('id', $excludeIds)
                    ->with(['genres', 'latestChapter', 'tags', 'authors'])
                    ->orderByDesc('avg_rating')
                    ->orderByDesc('views')
                    ->limit($limit)
                    ->get();
            }

            // 5. Fallback nếu user mới (ít dữ liệu) hoặc chưa đủ $limit
            if ($recommendations->count() < $limit) {
                $needed = $limit - $recommendations->count();
                $alreadyCollectedIds = array_merge($excludeIds, $recommendations->pluck('id')->toArray());

                $fallback = Comic::trending()
                    ->whereNotIn('id', $alreadyCollectedIds)
                    ->with(['genres', 'latestChapter', 'tags', 'authors'])
                    ->limit($needed)
                    ->get();

                $recommendations = $recommendations->concat($fallback);
            }

            // 6. Nếu vẫn chưa đủ (kho truyện nhỏ), nới lỏng không loại trừ excludeIds
            if ($recommendations->count() < $limit) {
                $needed = $limit - $recommendations->count();
                $alreadyCollectedIds = $recommendations->pluck('id')->toArray();

                $extra = Comic::orderByDesc('views')
                    ->whereNotIn('id', $alreadyCollectedIds)
                    ->with(['genres', 'latestChapter', 'tags', 'authors'])
                    ->limit($needed)
                    ->get();

                $recommendations = $recommendations->concat($extra);
            }

            return $recommendations->take($limit);
        });
    }

    /**
     * Gợi ý truyện tương tự theo một bộ truyện cụ thể (Similar Comics).
     *
     * @param  Comic  $comic
     * @param  int    $limit
     * @return Collection<Comic>
     */
    public function forComic(Comic $comic, int $limit = 6): Collection
    {
        $cacheKey = "recommendations.comic.{$comic->id}.limit_{$limit}";

        return Cache::remember($cacheKey, 1800, function () use ($comic, $limit) {
            $genreIds = $comic->genres->pluck('id')->toArray();

            $query = Comic::where('id', '!=', $comic->id)
                ->with(['genres', 'latestChapter', 'tags', 'authors']);

            if (!empty($genreIds)) {
                $query->whereHas('genres', function ($q) use ($genreIds) {
                    $q->whereIn('genres.id', $genreIds);
                });
            }

            $similar = $query->orderByDesc('avg_rating')
                ->orderByDesc('views')
                ->limit($limit)
                ->get();

            if ($similar->count() < $limit) {
                $needed = $limit - $similar->count();
                $excludeIds = array_merge([$comic->id], $similar->pluck('id')->toArray());

                $trending = Comic::trending()
                    ->whereNotIn('id', $excludeIds)
                    ->with(['genres', 'latestChapter', 'tags', 'authors'])
                    ->limit($needed)
                    ->get();

                $similar = $similar->concat($trending);
            }

            return $similar->take($limit);
        });
    }

    /**
     * Gợi ý cho khách vãng lai (Guest) — Trending & Top Rated, cache 15 phút.
     *
     * @param  int    $limit
     * @param  array  $excludeComicIds
     * @return Collection<Comic>
     */
    public function forGuest(int $limit = 6, array $excludeComicIds = []): Collection
    {
        $cacheKey = "recommendations.guest.limit_{$limit}";

        return Cache::remember($cacheKey, 900, function () use ($limit, $excludeComicIds) {
            $query = Comic::trending();

            if (!empty($excludeComicIds)) {
                $query->whereNotIn('id', $excludeComicIds);
            }

            return $query->with(['genres', 'latestChapter', 'tags', 'authors'])
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Xóa cache gợi ý khi user có hành vi đọc mới hoặc theo dõi truyện.
     */
    public function invalidateForUser(int $userId): void
    {
        // Xóa các key cache theo các limit phổ biến (4, 6, 8, 10, 12)
        foreach ([4, 6, 8, 10, 12] as $limit) {
            Cache::forget("recommendations.user.{$userId}.limit_{$limit}");
        }
        Cache::forget("recommendations.user.{$userId}");
    }

    /**
     * Xóa cache truyện tương tự khi comic có cập nhật metadata.
     */
    public function invalidateForComic(int $comicId): void
    {
        foreach ([4, 6, 8, 10, 12] as $limit) {
            Cache::forget("recommendations.comic.{$comicId}.limit_{$limit}");
        }
    }
}
