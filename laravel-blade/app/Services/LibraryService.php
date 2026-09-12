<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Library;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LibraryService
{
    public function __construct(
        protected RecommendationService $recommendationService
    ) {}

    /**
     * Thêm hoặc xóa truyện khỏi Tủ sách cá nhân (Toggle Bookmark).
     */
    public function toggle(User $user, Comic $comic): array
    {
        $libraryItem = Library::where('user_id', $user->id)
            ->where('comic_id', $comic->id)
            ->first();

        if ($libraryItem) {
            $libraryItem->delete();
            $isFollowed = false;
            $message = 'Đã bỏ theo dõi bộ truyện "' . $comic->title . '" khỏi tủ sách.';
        } else {
            Library::create([
                'user_id'  => $user->id,
                'comic_id' => $comic->id,
                'status'   => 'reading',
                'added_at' => now(),
            ]);
            $isFollowed = true;
            $message = 'Đã thêm bộ truyện "' . $comic->title . '" vào tủ sách cá nhân!';
        }

        $this->recommendationService->invalidateForUser($user->id);
        $totalFollowers = Library::where('comic_id', $comic->id)->count();

        return [
            'is_followed'     => $isFollowed,
            'message'         => $message,
            'total_followers' => $totalFollowers,
        ];
    }

    /**
     * Ghi nhận lịch sử đọc truyện của người dùng.
     */
    public function recordReading(User $user, Comic $comic, Chapter $chapter): ReadingHistory
    {
        $history = ReadingHistory::updateOrCreate(
            [
                'user_id'  => $user->id,
                'comic_id' => $comic->id,
            ],
            [
                'chapter_id'   => $chapter->id,
                'last_read_at' => now(),
            ]
        );

        $this->syncLastReadChapter($user, $comic, $chapter);
        $this->recommendationService->invalidateForUser($user->id);

        return $history;
    }

    /**
     * Đồng bộ chương đã đọc xa nhất trong Tủ truyện.
     * Không cho con trỏ bị lùi nếu người dùng quay lại đọc một chapter cũ.
     */
    public function syncLastReadChapter(User $user, Comic $comic, Chapter $chapter): void
    {
        $libraryItem = Library::with('lastReadChapter')
            ->where('user_id', $user->id)
            ->where('comic_id', $comic->id)
            ->first();

        if (!$libraryItem) {
            return;
        }

        $lastNumber = $libraryItem->lastReadChapter?->chapter_number;
        if ($lastNumber === null || (float) $chapter->chapter_number >= (float) $lastNumber) {
            $libraryItem->update(['last_read_chapter_id' => $chapter->id]);
            $libraryItem->setRelation('lastReadChapter', $chapter);
        }
    }

    /**
     * Lấy danh sách truyện trong Tủ sách (phân trang) và tính trạng thái chương chưa đọc.
     * Chỉ cần thêm một query batch cho toàn bộ card trên trang, tránh N+1.
     */
    public function getUserLibrary(User $user, int $perPage = 12): LengthAwarePaginator
    {
        $paginator = Library::with(['comic.latestChapter', 'lastReadChapter'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'library_page');

        $this->decorateUnreadState($paginator->getCollection());

        return $paginator;
    }

    /**
     * Lấy lịch sử đọc gần đây của người dùng.
     */
    public function getReadingHistory(User $user, int $limit = 20): Collection
    {
        return ReadingHistory::with(['comic', 'chapter'])
            ->where('user_id', $user->id)
            ->orderBy('last_read_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Xóa toàn bộ lịch sử đọc và reset con trỏ đọc trong Tủ truyện.
     */
    public function clearUserHistory(User $user): bool
    {
        DB::transaction(function () use ($user) {
            ReadingHistory::where('user_id', $user->id)->delete();
            Library::where('user_id', $user->id)->update(['last_read_chapter_id' => null]);
        });

        $this->recommendationService->invalidateForUser($user->id);
        return true;
    }

    /**
     * Thống kê hoạt động đọc của người dùng.
     */
    public function getUserReadingStats(User $user, ?int $totalBookmarks = null): array
    {
        // Reuse the paginator's total when available, including an empty library.
        $totalBookmarks ??= Library::where('user_id', $user->id)->count();
        $totalReadComics = ReadingHistory::where('user_id', $user->id)->count();

        $readComicIds = ReadingHistory::where('user_id', $user->id)->pluck('comic_id');
        $topGenres = DB::table('comic_genre')
            ->join('genres', 'comic_genre.genre_id', '=', 'genres.id')
            ->whereIn('comic_genre.comic_id', $readComicIds)
            ->select('genres.name', DB::raw('count(*) as count'))
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('count')
            ->limit(3)
            ->pluck('name')
            ->toArray();

        return [
            'total_bookmarks'    => $totalBookmarks,
            'total_read_comics'  => $totalReadComics,
            'top_genres'         => $topGenres,
        ];
    }

    /**
     * Gắn unread_chapters_count và nextUnreadChapter cho các Library item đang hiển thị.
     */
    private function decorateUnreadState(Collection $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $comicIds = $items->pluck('comic_id')->filter()->unique()->values();
        $chaptersByComic = Chapter::query()
            ->published()
            ->whereIn('comic_id', $comicIds)
            ->orderBy('comic_id')
            ->orderBy('chapter_number')
            ->get(['id', 'comic_id', 'chapter_number', 'slug', 'title'])
            ->groupBy('comic_id');

        foreach ($items as $item) {
            $publishedChapters = $chaptersByComic->get($item->comic_id, collect());
            $lastNumber = $item->lastReadChapter?->chapter_number;

            $unreadChapters = $lastNumber === null
                ? $publishedChapters
                : $publishedChapters->filter(
                    fn (Chapter $chapter) => (float) $chapter->chapter_number > (float) $lastNumber
                )->values();

            $item->setAttribute('unread_chapters_count', $unreadChapters->count());
            $item->setRelation('nextUnreadChapter', $unreadChapters->first());
        }
    }
}
