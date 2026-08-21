<?php

namespace App\Services;

use App\Models\Comic;
use App\Models\ReadingHistory;
use App\Models\Library;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * RecommendationService
 *
 * Gợi ý truyện dựa trên hành vi đọc & tương tác của người dùng (Content-Based Filtering).
 *
 * Thuật toán (Multi-Signal Content Filtering):
 *  1. Lấy preferred genres + tags từ Lịch sử đọc 30 ngày gần nhất + Tủ sách (Library)
 *  2. Tìm truyện cùng thể loại & tag chưa đọc, xếp hạng theo avg_rating DESC → views DESC
 *  3. Fallback mượt mà: Trending → Top-Viewed
 *  4. Gợi ý truyện tương tự theo comic cụ thể (Similar Comics — genre + tag signal)
 *  5. Cache đa tầng (User / Guest / Comic) dùng version key thay vì xóa theo limit cụ thể
 *
 * Cache invalidation dùng VERSION KEY pattern:
 *   - "rec_ver.user.{id}" lưu số phiên bản hiện tại (tự động tăng khi invalidate)
 *   - Cache key thực tế nhúng version này nên stale cache tự động bị bỏ qua
 *   - Không cần biết limit nào đã được cache → invalidation an toàn và hoàn toàn
 */
class RecommendationService
{
    // Số ngày lấy lịch sử đọc để tính preferred genres/tags
    private const HISTORY_DAYS = 30;

    // TTL (giây) cho từng loại cache
    private const TTL_USER    = 600;   // 10 phút
    private const TTL_COMIC   = 1800;  // 30 phút
    private const TTL_GUEST   = 900;   // 15 phút
    private const TTL_VERSION = 86400; // 24 giờ — version key tồn tại lâu hơn dữ liệu

    // ─────────────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────────────

    /**
     * Gợi ý truyện cá nhân hóa cho User đã đăng nhập.
     * User ID chỉ lấy từ Auth, không nhận tùy ý từ request.
     *
     * @param  User   $user            Object User từ auth() — KHÔNG nhận từ query param
     * @param  int    $limit
     * @param  array  $excludeComicIds
     * @return Collection<Comic>
     */
    public function forUser(User $user, int $limit = 6, array $excludeComicIds = []): Collection
    {
        $version  = $this->getUserVersion($user->id);
        $cacheKey = "recommendations.user.{$user->id}.v{$version}.limit_{$limit}";

        return Cache::remember($cacheKey, self::TTL_USER, function () use ($user, $limit, $excludeComicIds) {
            // 1. Lấy danh sách comic IDs đã đọc trong 30 ngày gần nhất
            $readComicIds = ReadingHistory::where('user_id', $user->id)
                ->where('last_read_at', '>=', now()->subDays(self::HISTORY_DAYS))
                ->pluck('comic_id')
                ->toArray();

            // 2. Lấy comic IDs trong Tủ sách (Bookmark)
            $libraryComicIds = Library::where('user_id', $user->id)
                ->pluck('comic_id')
                ->toArray();

            $allInteractedComicIds = array_unique(array_merge($readComicIds, $libraryComicIds));
            $excludeIds = array_unique(array_merge($allInteractedComicIds, $excludeComicIds));

            // Early-return nếu user hoàn toàn mới (chưa có bất kỳ tương tác nào)
            if (empty($allInteractedComicIds)) {
                return $this->fetchFallback($excludeIds, $limit);
            }

            // 3. Khai phá preferred genre IDs từ các bộ truyện đã tương tác
            $preferredGenreIds = DB::table('comic_genre')
                ->whereIn('comic_id', $allInteractedComicIds)
                ->pluck('genre_id')
                ->unique()
                ->toArray();

            // 4. Khai phá preferred tag IDs (tag signal) từ các bộ truyện đã tương tác
            $preferredTagIds = DB::table('comic_tag')
                ->whereIn('comic_id', $allInteractedComicIds)
                ->pluck('tag_id')
                ->unique()
                ->toArray();

            $recommendations = collect();

            // 5. Tìm truyện có cùng thể loại yêu thích (genre signal — trọng số cao hơn)
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

            // 6. Bổ sung thêm truyện cùng tag nếu chưa đủ limit (tag signal — trọng số thấp hơn)
            if ($recommendations->count() < $limit && !empty($preferredTagIds)) {
                $needed = $limit - $recommendations->count();
                $alreadyIds = array_merge($excludeIds, $recommendations->pluck('id')->toArray());

                $tagBased = Comic::whereHas('tags', function ($q) use ($preferredTagIds) {
                        $q->whereIn('tags.id', $preferredTagIds);
                    })
                    ->whereNotIn('id', $alreadyIds)
                    ->with(['genres', 'latestChapter', 'tags', 'authors'])
                    ->orderByDesc('avg_rating')
                    ->orderByDesc('views')
                    ->limit($needed)
                    ->get();

                $recommendations = $recommendations->concat($tagBased);
            }

            // 7. Fallback nếu vẫn chưa đủ limit
            if ($recommendations->count() < $limit) {
                $alreadyIds = array_merge($excludeIds, $recommendations->pluck('id')->toArray());
                $fallback   = $this->fetchFallback($alreadyIds, $limit - $recommendations->count());
                $recommendations = $recommendations->concat($fallback);
            }

            return $recommendations->take($limit);
        });
    }

    /**
     * Gợi ý truyện tương tự theo một bộ truyện cụ thể (Similar Comics).
     * Dùng cả Genre signal lẫn Tag signal.
     *
     * @param  Comic  $comic
     * @param  int    $limit
     * @return Collection<Comic>
     */
    public function forComic(Comic $comic, int $limit = 6): Collection
    {
        $version  = $this->getComicVersion($comic->id);
        $cacheKey = "recommendations.comic.{$comic->id}.v{$version}.limit_{$limit}";

        return Cache::remember($cacheKey, self::TTL_COMIC, function () use ($comic, $limit) {
            // Ensure genres & tags đã loaded (tránh N+1 khi gọi từ ngoài)
            if (!$comic->relationLoaded('genres')) {
                $comic->load('genres', 'tags');
            } elseif (!$comic->relationLoaded('tags')) {
                $comic->load('tags');
            }

            $genreIds = $comic->genres->pluck('id')->toArray();
            $tagIds   = $comic->tags->pluck('id')->toArray();

            $baseQuery = Comic::where('id', '!=', $comic->id)
                ->with(['genres', 'latestChapter', 'tags', 'authors']);

            // Guard: nếu comic không có genre thì không query unfiltered
            if (empty($genreIds) && empty($tagIds)) {
                // Fallback hoàn toàn về trending khi comic không có metadata
                return $this->fetchFallback([$comic->id], $limit);
            }

            // Genre signal (ưu tiên trước)
            if (!empty($genreIds)) {
                $baseQuery->whereHas('genres', function ($q) use ($genreIds) {
                    $q->whereIn('genres.id', $genreIds);
                });
            } elseif (!empty($tagIds)) {
                // Dùng tag signal nếu không có genre
                $baseQuery->whereHas('tags', function ($q) use ($tagIds) {
                    $q->whereIn('tags.id', $tagIds);
                });
            }

            $similar = $baseQuery
                ->orderByDesc('avg_rating')
                ->orderByDesc('views')
                ->limit($limit)
                ->get();

            // Bổ sung tag-based nếu chưa đủ (tag signal bổ sung)
            if ($similar->count() < $limit && !empty($tagIds)) {
                $needed     = $limit - $similar->count();
                $excludeIds = array_merge([$comic->id], $similar->pluck('id')->toArray());

                $tagBased = Comic::where('id', '!=', $comic->id)
                    ->whereNotIn('id', $excludeIds)
                    ->whereHas('tags', function ($q) use ($tagIds) {
                        $q->whereIn('tags.id', $tagIds);
                    })
                    ->with(['genres', 'latestChapter', 'tags', 'authors'])
                    ->orderByDesc('avg_rating')
                    ->orderByDesc('views')
                    ->limit($needed)
                    ->get();

                $similar = $similar->concat($tagBased);
            }

            // Fallback trending nếu vẫn chưa đủ
            if ($similar->count() < $limit) {
                $excludeIds = array_merge([$comic->id], $similar->pluck('id')->toArray());
                $fallback   = $this->fetchFallback($excludeIds, $limit - $similar->count());
                $similar    = $similar->concat($fallback);
            }

            return $similar->take($limit);
        });
    }

    /**
     * Gợi ý cho khách vãng lai (Guest) — Trending & Top Rated.
     *
     * Fix: cache key bao gồm hash của excludeComicIds để tránh
     * 2 request khác nhau (khác exclude) share cùng cache sai.
     *
     * @param  int    $limit
     * @param  array  $excludeComicIds
     * @return Collection<Comic>
     */
    public function forGuest(int $limit = 6, array $excludeComicIds = []): Collection
    {
        // Nhúng hash của excludeComicIds vào key để tránh cache conflict
        $excludeHash = empty($excludeComicIds) ? 'all' : md5(implode(',', $excludeComicIds));
        $cacheKey    = "recommendations.guest.limit_{$limit}.ex_{$excludeHash}";

        return Cache::remember($cacheKey, self::TTL_GUEST, function () use ($limit, $excludeComicIds) {
            $query = Comic::trending();

            if (!empty($excludeComicIds)) {
                $query->whereNotIn('id', $excludeComicIds);
            }

            return $query->with(['genres', 'latestChapter', 'tags', 'authors'])
                ->limit($limit)
                ->get();
        });
    }

    // ─────────────────────────────────────────────────────────────
    // CACHE INVALIDATION (Version Key Pattern)
    // ─────────────────────────────────────────────────────────────

    /**
     * Vô hiệu hóa cache gợi ý khi user có hành vi đọc mới hoặc theo dõi truyện.
     * Tích hợp DEBOUNCE (mặc định 60 giây) để tránh invalidate liên tục khi user cuộn đọc nhiều request trong thời gian ngắn,
     * giúp cache recommendation có tỷ lệ cache hit cao (> 0) mà vẫn đảm bảo cập nhật dữ liệu.
     *
     * @param int  $userId
     * @param bool $force           Nếu true, bỏ qua debounce và ép buộc tăng version ngay
     * @param int  $debounceSeconds Thời gian debounce giữa 2 lần invalidate (mặc định 60s)
     */
    public function invalidateForUser(int $userId, bool $force = false, int $debounceSeconds = 60): void
    {
        if (!$force) {
            $debounceKey = "rec_debounce.user.{$userId}";
            // Cache::add() chỉ thành công nếu key CHƯA tồn tại trong cache
            // Nếu vừa mới invalidate trong vòng debounceSeconds → bỏ qua để giữ cache hit
            if (!Cache::add($debounceKey, true, $debounceSeconds)) {
                return;
            }
        }

        $versionKey = "rec_ver.user.{$userId}";
        $current    = (int) Cache::get($versionKey, 0);
        Cache::put($versionKey, $current + 1, self::TTL_VERSION);
    }

    /**
     * Vô hiệu hóa cache truyện tương tự khi comic có cập nhật metadata (genres/tags).
     *
     * Tăng version key → tất cả cache forComic với mọi limit đều bị invalidate.
     */
    public function invalidateForComic(int $comicId): void
    {
        $versionKey = "rec_ver.comic.{$comicId}";
        $current    = (int) Cache::get($versionKey, 0);
        Cache::put($versionKey, $current + 1, self::TTL_VERSION);
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────

    /**
     * Lấy version hiện tại của user recommendation cache.
     * Version = 0 nếu chưa có (user mới chưa từng bị invalidate).
     */
    private function getUserVersion(int $userId): int
    {
        return (int) Cache::get("rec_ver.user.{$userId}", 0);
    }

    /**
     * Lấy version hiện tại của comic recommendation cache.
     */
    private function getComicVersion(int $comicId): int
    {
        return (int) Cache::get("rec_ver.comic.{$comicId}", 0);
    }

    /**
     * Fallback: lấy truyện trending, nếu không đủ thì lấy thêm theo views DESC.
     *
     * @param  array  $excludeIds IDs đã có, không lấy lại
     * @param  int    $needed     Số lượng cần thêm
     * @return Collection<Comic>
     */
    private function fetchFallback(array $excludeIds, int $needed): Collection
    {
        if ($needed <= 0) {
            return collect();
        }

        $trending = Comic::trending()
            ->whereNotIn('id', $excludeIds)
            ->with(['genres', 'latestChapter', 'tags', 'authors'])
            ->limit($needed)
            ->get();

        if ($trending->count() < $needed) {
            $stillNeeded   = $needed - $trending->count();
            $alreadyHasIds = array_merge($excludeIds, $trending->pluck('id')->toArray());

            $extra = Comic::orderByDesc('views')
                ->whereNotIn('id', $alreadyHasIds)
                ->with(['genres', 'latestChapter', 'tags', 'authors'])
                ->limit($stillNeeded)
                ->get();

            $trending = $trending->concat($extra);
        }

        return $trending;
    }
}
