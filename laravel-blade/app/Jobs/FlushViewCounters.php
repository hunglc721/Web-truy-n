<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FlushViewCounters implements ShouldQueue
{
    use Queueable;

    public const BUFFER_CHAPTER_PREFIX = 'views:buffer:chapter:';
    public const BUFFER_COMIC_PREFIX   = 'views:buffer:comic:';
    public const ACTIVE_CHAPTERS_KEY   = 'views:active_chapter_ids';
    public const ACTIVE_COMICS_KEY     = 'views:active_comic_ids';

    /**
     * Ghi nhận 1 lượt xem chapter & comic vào cache buffer.
     *
     * @param int $comicId
     * @param int $chapterId
     */
    public static function recordView(int $comicId, int $chapterId): void
    {
        // 1. Tăng counter của chapter & comic trong cache buffer
        Cache::increment(self::BUFFER_CHAPTER_PREFIX . $chapterId);
        Cache::increment(self::BUFFER_COMIC_PREFIX . $comicId);

        // 2. Lưu ID vào danh sách cần flush
        self::registerActiveId(self::ACTIVE_CHAPTERS_KEY, $chapterId);
        self::registerActiveId(self::ACTIVE_COMICS_KEY, $comicId);
    }

    /**
     * Ghi nhận 1 lượt xem comic trực tiếp vào cache buffer (ví dụ từ trang chi tiết).
     *
     * @param int $comicId
     */
    public static function recordComicView(int $comicId): void
    {
        Cache::increment(self::BUFFER_COMIC_PREFIX . $comicId);
        self::registerActiveId(self::ACTIVE_COMICS_KEY, $comicId);
    }

    /**
     * Helper đăng ký ID cần flush vào danh sách active.
     */
    protected static function registerActiveId(string $cacheKey, int $id): void
    {
        $ids = Cache::get($cacheKey, []);
        if (!in_array($id, $ids, true)) {
            $ids[] = $id;
            Cache::put($cacheKey, $ids, 86400); // 24h safety TTL
        }
    }

    /**
     * Thực thi flush toàn bộ counter từ cache buffer xuống CSDL bằng 1 câu update gộp cho mỗi bảng.
     */
    public function handle(): void
    {
        $this->flushChapters();
        $this->flushComics();
    }

    /**
     * Flush chapter views xuống bảng chapters bằng 1 câu SQL CASE WHEN duy nhất.
     */
    public function flushChapters(): int
    {
        $chapterIds = Cache::pull(self::ACTIVE_CHAPTERS_KEY, []);
        if (empty($chapterIds)) {
            return 0;
        }

        $updates = [];
        foreach ($chapterIds as $id) {
            $count = (int) Cache::pull(self::BUFFER_CHAPTER_PREFIX . $id, 0);
            if ($count > 0) {
                $updates[$id] = $count;
            }
        }

        if (empty($updates)) {
            return 0;
        }

        $this->batchIncrement('chapters', $updates);

        return count($updates);
    }

    /**
     * Flush comic views xuống bảng comics bằng 1 câu SQL CASE WHEN duy nhất.
     */
    public function flushComics(): int
    {
        $comicIds = Cache::pull(self::ACTIVE_COMICS_KEY, []);
        if (empty($comicIds)) {
            return 0;
        }

        $updates = [];
        foreach ($comicIds as $id) {
            $count = (int) Cache::pull(self::BUFFER_COMIC_PREFIX . $id, 0);
            if ($count > 0) {
                $updates[$id] = $count;
            }
        }

        if (empty($updates)) {
            return 0;
        }

        $this->batchIncrement('comics', $updates);

        return count($updates);
    }

    /**
     * Thực hiện câu SQL UPDATE gộp (CASE WHEN) duy nhất cho toàn bộ bảng.
     * Ví dụ:
     * UPDATE chapters SET views = CASE WHEN id = ? THEN views + ? WHEN id = ? THEN views + ? ELSE views END WHERE id IN (?, ?)
     *
     * @param string $table 'chapters' | 'comics'
     * @param array<int, int> $updates [id => increment_amount]
     */
    protected function batchIncrement(string $table, array $updates): void
    {
        $ids = array_keys($updates);
        $casesViews = [];
        $casesViewsCount = [];
        $params = [];

        foreach ($updates as $id => $count) {
            $casesViews[] = "WHEN id = ? THEN views + ?";
            $params[] = (int) $id;
            $params[] = (int) $count;
        }

        $caseSqlViews = implode(' ', $casesViews);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if ($table === 'comics') {
            // Comics có cả cột views và views_count
            foreach ($updates as $id => $count) {
                $casesViewsCount[] = "WHEN id = ? THEN views_count + ?";
                $params[] = (int) $id;
                $params[] = (int) $count;
            }
            $caseSqlViewsCount = implode(' ', $casesViewsCount);
            $sql = "UPDATE {$table} SET views = CASE {$caseSqlViews} ELSE views END, views_count = CASE {$caseSqlViewsCount} ELSE views_count END WHERE id IN ({$placeholders})";
        } else {
            $sql = "UPDATE {$table} SET views = CASE {$caseSqlViews} ELSE views END WHERE id IN ({$placeholders})";
        }

        $params = array_merge($params, array_map('intval', $ids));

        DB::update($sql, $params);
    }
}
