<?php

namespace App\Observers;

use App\Models\Chapter;
use Illuminate\Support\Facades\Cache;

/**
 * Xóa cache liên quan khi Chapter được thêm / sửa / xóa.
 *
 * Đăng ký trong AppServiceProvider::boot():
 *   Chapter::observe(ChapterObserver::class);
 */
class ChapterObserver
{
    /**
     * Các cache keys cần xóa khi bất kỳ chapter của comic này thay đổi.
     */
    protected function invalidateForChapter(Chapter $chapter): void
    {
        $comicId = $chapter->comic_id;
        $comicSlug = $chapter->comic->slug ?? null;

        // Dropdown danh sách chương trong reader (reader cache)
        Cache::forget("comic.{$comicId}.chapters_list");

        // Dropdown danh sách chương trong reader (admin preview cache)
        Cache::forget("comic.{$comicId}.chapters_list.admin");

        // Comic detail page cache (reader + admin tách riêng)
        // Xóa cả 2 vì có thể chapters count / list đã thay đổi
        // FIX: Xóa theo slug vì ComicController lưu cache theo slug
        if ($comicSlug) {
            Cache::forget("comic.detail.{$comicSlug}");
            Cache::forget("comic.detail.{$comicSlug}.admin");
        }

        // Home page: latest updates phụ thuộc vào chapter mới nhất
        Cache::forget('home.latest');
        Cache::forget('home.trending');

        // Schedule cache phụ thuộc vào chapter mới nhất
        Cache::forget('schedule.day_counts');
        for ($i = 0; $i <= 6; $i++) {
            Cache::forget("schedule.day.{$i}");
        }
    }

    public function created(Chapter $chapter): void
    {
        $this->invalidateForChapter($chapter);
    }

    public function updated(Chapter $chapter): void
    {
        $this->invalidateForChapter($chapter);
    }

    public function deleted(Chapter $chapter): void
    {
        $this->invalidateForChapter($chapter);
    }

    public function restored(Chapter $chapter): void
    {
        $this->invalidateForChapter($chapter);
    }
}
