<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comic;
use App\Models\Comment;
use App\Models\Library;
use App\Models\Rating;
use App\Models\ReadingHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserStatisticsService
{
    /**
     * Lấy tổng quan các chỉ số đọc truyện của người dùng.
     *
     * @return array{
     *     total_library_comics: int,
     *     total_chapters_read: int,
     *     total_ratings: int,
     *     total_comments: int,
     *     total_likes: int,
     *     reading_streak_days: int,
     *     reader_tier: array{level: int, name: string, icon: string, next_level_chapters: int, progress_percent: float}
     * }
     */
    public function getOverview(User $user): array
    {
        $libraryCount   = Library::where('user_id', $user->id)->count();
        $historyCount   = ReadingHistory::where('user_id', $user->id)->count();
        $ratingsCount   = Rating::where('user_id', $user->id)->count();
        $commentsCount  = Comment::where('user_id', $user->id)->count();
        $likesCount     = DB::table('comic_likes')->where('user_id', $user->id)->count();

        $streakDays     = $this->calculateStreakDays($user);
        $tierInfo       = $this->calculateReaderTier($historyCount);

        return [
            'total_library_comics' => $libraryCount,
            'total_chapters_read'  => $historyCount,
            'total_ratings'        => $ratingsCount,
            'total_comments'       => $commentsCount,
            'total_likes'          => $likesCount,
            'reading_streak_days'  => $streakDays,
            'reader_tier'          => $tierInfo,
        ];
    }

    /**
     * Tính toán chuỗi ngày đọc truyện liên tục (Reading Streak).
     */
    public function calculateStreakDays(User $user): int
    {
        $readDates = ReadingHistory::where('user_id', $user->id)
            ->whereNotNull('last_read_at')
            ->orderByDesc('last_read_at')
            ->pluck('last_read_at')
            ->map(fn($dt) => Carbon::parse($dt)->toDateString())
            ->unique()
            ->values();

        if ($readDates->isEmpty()) {
            return 0;
        }

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $firstReadDate = Carbon::parse($readDates->first());

        // Chuỗi bị đứt nếu ngày đọc gần nhất không phải hôm nay hoặc hôm qua
        if (!$firstReadDate->isSameDay($today) && !$firstReadDate->isSameDay($yesterday)) {
            return 0;
        }

        $streak = 1;
        $currentCheck = $firstReadDate;

        for ($i = 1; $i < $readDates->count(); $i++) {
            $prevDate = Carbon::parse($readDates[$i]);
            if ($currentCheck->copy()->subDay()->isSameDay($prevDate)) {
                $streak++;
                $currentCheck = $prevDate;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Tính toán Cấp bậc Độc giả (Reader Tier / Level).
     *
     * @return array{level: int, name: string, icon: string, next_level_chapters: int, progress_percent: float}
     */
    public function calculateReaderTier(int $chaptersRead): array
    {
        if ($chaptersRead >= 200) {
            return [
                'level'               => 5,
                'name'                => 'Thần Thoại (Mythic)',
                'icon'                => '👑',
                'next_level_chapters' => 200,
                'progress_percent'    => 100.0,
            ];
        }

        if ($chaptersRead >= 100) {
            $progress = round((($chaptersRead - 100) / 100) * 100, 1);
            return [
                'level'               => 4,
                'name'                => 'Đại Tông Sư (Diamond)',
                'icon'                => '💎',
                'next_level_chapters' => 200,
                'progress_percent'    => min(100.0, max(0.0, $progress)),
            ];
        }

        if ($chaptersRead >= 50) {
            $progress = round((($chaptersRead - 50) / 50) * 100, 1);
            return [
                'level'               => 3,
                'name'                => 'Cao Thủ (Gold)',
                'icon'                => '🥇',
                'next_level_chapters' => 100,
                'progress_percent'    => min(100.0, max(0.0, $progress)),
            ];
        }

        if ($chaptersRead >= 10) {
            $progress = round((($chaptersRead - 10) / 40) * 100, 1);
            return [
                'level'               => 2,
                'name'                => 'Mọt Sách (Silver)',
                'icon'                => '🥈',
                'next_level_chapters' => 50,
                'progress_percent'    => min(100.0, max(0.0, $progress)),
            ];
        }

        $progress = round(($chaptersRead / 10) * 100, 1);
        return [
            'level'               => 1,
            'name'                => 'Tân Thủ (Bronze)',
            'icon'                => '🥉',
            'next_level_chapters' => 10,
            'progress_percent'    => min(100.0, max(0.0, $progress)),
        ];
    }

    /**
     * Thống kê tỷ lệ các thể loại yêu thích của người dùng.
     *
     * @return Collection<int, array{genre: string, count: int, percentage: float}>
     */
    public function getFavoriteGenres(User $user, int $limit = 5): Collection
    {
        $comicIds = ReadingHistory::where('user_id', $user->id)
            ->pluck('comic_id')
            ->merge(Library::where('user_id', $user->id)->pluck('comic_id'))
            ->unique();

        if ($comicIds->isEmpty()) {
            return collect();
        }

        $genreCounts = DB::table('comic_genre')
            ->join('genres', 'comic_genre.genre_id', '=', 'genres.id')
            ->whereIn('comic_genre.comic_id', $comicIds)
            ->select('genres.name', DB::raw('COUNT(*) as total'))
            ->groupBy('genres.name')
            ->orderByDesc('total')
            ->take($limit)
            ->get();

        $totalOccurrences = $genreCounts->sum('total');

        return $genreCounts->map(function ($item) use ($totalOccurrences) {
            return [
                'genre'      => $item->name,
                'count'      => (int) $item->total,
                'percentage' => $totalOccurrences > 0 ? round(($item->total / $totalOccurrences) * 100, 1) : 0.0,
            ];
        });
    }

    /**
     * Danh sách huy hiệu thành tích của người dùng.
     *
     * @return array<int, array{id: string, name: string, description: string, icon: string, is_unlocked: bool, unlocked_at: string|null}>
     */
    public function getBadges(User $user): array
    {
        $historyCount  = ReadingHistory::where('user_id', $user->id)->count();
        $libraryCount  = Library::where('user_id', $user->id)->count();
        $ratingsCount  = Rating::where('user_id', $user->id)->count();
        $commentsCount = Comment::where('user_id', $user->id)->count();
        $streakDays    = $this->calculateStreakDays($user);

        return [
            [
                'id'          => 'first_step',
                'name'        => 'Bước Đầu Tiên',
                'description' => 'Đọc chương truyện đầu tiên trên WebComics',
                'icon'        => '🚀',
                'is_unlocked' => $historyCount >= 1,
            ],
            [
                'id'          => 'bookworm',
                'name'        => 'Đại Mọt Sách',
                'description' => 'Đã đọc hơn 50 chương truyện',
                'icon'        => '📚',
                'is_unlocked' => $historyCount >= 50,
            ],
            [
                'id'          => 'critic',
                'name'        => 'Nhà Phê Bình',
                'description' => 'Đã đánh giá và nhận xét từ 5 bộ truyện trở lên',
                'icon'        => '⭐',
                'is_unlocked' => $ratingsCount >= 5,
            ],
            [
                'id'          => 'collector',
                'name'        => 'Nhà Sưu Tầm',
                'description' => 'Thêm từ 10 bộ truyện vào Tủ Sách cá nhân',
                'icon'        => '🏛️',
                'is_unlocked' => $libraryCount >= 10,
            ],
            [
                'id'          => 'commentator',
                'name'        => 'Sôi Nổi',
                'description' => 'Đã gửi từ 10 bình luận giao lưu với cộng đồng',
                'icon'        => '💬',
                'is_unlocked' => $commentsCount >= 10,
            ],
            [
                'id'          => 'streak_master',
                'name'        => 'Kiên Trì Bất Bại',
                'description' => 'Duy trì chuỗi đọc truyện liên tục 7 ngày',
                'icon'        => '🔥',
                'is_unlocked' => $streakDays >= 7,
            ],
        ];
    }

    /**
     * Biểu đồ hoạt động đọc 7 ngày gần nhất.
     *
     * @return array<int, array{date: string, day_name: string, count: int}>
     */
    public function getWeeklyActivity(User $user): array
    {
        $days = [];
        $startDate = Carbon::today()->subDays(6);

        for ($i = 0; $i < 7; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $dateString  = $currentDate->toDateString();

            $count = ReadingHistory::where('user_id', $user->id)
                ->whereDate('last_read_at', $dateString)
                ->count();

            $days[] = [
                'date'     => $dateString,
                'day_name' => $currentDate->format('D'),
                'count'    => $count,
            ];
        }

        return $days;
    }

    /**
     * Xuất dữ liệu đọc truyện cá nhân (JSON format).
     *
     * @return array{
     *     user: array{id: int, name: string, email: string},
     *     exported_at: string,
     *     library: array<int, array{title: string, slug: string, status: string, added_at: string|null}>,
     *     reading_history: array<int, array{comic_title: string, chapter: int|float|null, last_read_at: string|null, scroll_percent: float}>
     * }
     */
    public function exportUserData(User $user): array
    {
        $libraries = Library::with('comic:id,title,slug')
            ->where('user_id', $user->id)
            ->get()
            ->map(fn($item) => [
                'title'    => $item->comic?->title ?? 'N/A',
                'slug'     => $item->comic?->slug ?? '',
                'status'   => $item->status,
                'added_at' => $item->added_at?->toISOString() ?? $item->created_at?->toISOString(),
            ])
            ->toArray();

        $history = ReadingHistory::with(['comic:id,title,slug', 'chapter:id,chapter_number'])
            ->where('user_id', $user->id)
            ->get()
            ->map(fn($item) => [
                'comic_title'    => $item->comic?->title ?? 'N/A',
                'chapter'        => $item->chapter?->chapter_number,
                'last_read_at'   => $item->last_read_at?->toISOString(),
                'scroll_percent' => (float) ($item->scroll_percent ?? 0.0),
            ])
            ->toArray();

        return [
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'exported_at'     => now()->toISOString(),
            'library'         => $libraries,
            'reading_history' => $history,
        ];
    }
}
