<?php

namespace App\Services;

use App\Models\Comic;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * RecommendationService
 *
 * Gợi ý truyện dựa trên hành vi đọc của người dùng (Content-Based Filtering).
 * Không cần ML/package — dùng genre overlap + popularity fallback.
 *
 * Algorithm:
 *  1. Lấy genre IDs từ lịch sử đọc 30 ngày gần nhất của user
 *  2. Tìm comics cùng genre, chưa đọc, sort avg_rating DESC → views DESC
 *  3. Fallback: nếu < 3 kết quả → bổ sung trending comics
 */
class RecommendationService
{
    /**
     * Gợi ý cho user đã đăng nhập.
     *
     * @param  User  $user
     * @param  int   $limit
     * @return Collection<Comic>
     */
    public function forUser(User $user, int $limit = 6): Collection
    {
        $cacheKey = "recommendations.user.{$user->id}";

        return Cache::remember($cacheKey, 600, function () use ($user, $limit) {
            // 1. Lấy comic IDs đã đọc trong 30 ngày
            $readComicIds = ReadingHistory::where('user_id', $user->id)
                ->where('last_read_at', '>=', now()->subDays(30))
                ->pluck('comic_id');

            // 2. Lấy genre IDs từ các comic đã đọc
            $preferredGenreIds = \DB::table('comic_genre')
                ->whereIn('comic_id', $readComicIds)
                ->pluck('genre_id')
                ->unique();

            $recommendations = collect();

            if ($preferredGenreIds->isNotEmpty()) {
                // 3. Tìm comics cùng genre, chưa đọc
                $recommendations = Comic::whereHas('genres', function ($q) use ($preferredGenreIds) {
                        $q->whereIn('genres.id', $preferredGenreIds);
                    })
                    ->whereNotIn('id', $readComicIds)
                    ->with(['genres', 'latestChapter', 'tags'])
                    ->orderByDesc('avg_rating')
                    ->orderByDesc('views')
                    ->limit($limit)
                    ->get();
            }

            // 4. Fallback: bổ sung trending nếu không đủ $limit kết quả
            if ($recommendations->count() < $limit) {
                $needed    = $limit - $recommendations->count();
                $existIds  = $recommendations->pluck('id')->merge($readComicIds);

                $trending = Comic::trending()
                    ->whereNotIn('id', $existIds)
                    ->with(['genres', 'latestChapter', 'tags'])
                    ->limit($needed)
                    ->get();

                $recommendations = $recommendations->concat($trending);
            }

            return $recommendations->take($limit);
        });
    }

    /**
     * Gợi ý cho khách (guest) — trending comics, cache 15 phút.
     *
     * @param  int  $limit
     * @return Collection<Comic>
     */
    public function forGuest(int $limit = 6): Collection
    {
        return Cache::remember('recommendations.guest', 900, function () use ($limit) {
            return Comic::trending()
                ->with(['genres', 'latestChapter', 'tags'])
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Xóa cache gợi ý khi user đọc truyện mới (gọi từ ChapterController).
     */
    public function invalidateForUser(int $userId): void
    {
        Cache::forget("recommendations.user.{$userId}");
    }
}
