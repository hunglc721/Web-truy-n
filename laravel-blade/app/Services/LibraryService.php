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

        // Cập nhật last_read_chapter_id trong Library nếu truyện đã có trong tủ sách
        Library::where('user_id', $user->id)
            ->where('comic_id', $comic->id)
            ->update(['last_read_chapter_id' => $chapter->id]);

        $this->recommendationService->invalidateForUser($user->id);

        return $history;
    }

    /**
     * Lấy danh sách truyện trong Tủ sách (phân trang).
     */
    public function getUserLibrary(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return Library::with(['comic.latestChapter', 'lastReadChapter'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'library_page');
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
     * Xóa toàn bộ lịch sử đọc của người dùng.
     */
    public function clearUserHistory(User $user): bool
    {
        ReadingHistory::where('user_id', $user->id)->delete();
        $this->recommendationService->invalidateForUser($user->id);
        return true;
    }

    /**
     * Thống kê hoạt động đọc của người dùng.
     */
    public function getUserReadingStats(User $user): array
    {
        $totalBookmarks = Library::where('user_id', $user->id)->count();
        $totalReadComics = ReadingHistory::where('user_id', $user->id)->count();

        // Thống kê top 3 thể loại yêu thích dựa trên các truyện đã đọc
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
}
