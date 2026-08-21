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
    protected function invalidateForComic(int $comicId): void
    {
        // Dropdown danh sách chương trong reader (reader cache)
        Cache::forget("comic.{$comicId}.chapters_list");

        // Dropdown danh sách chương trong reader (admin preview cache)
        Cache::forget("comic.{$comicId}.chapters_list.admin");

        // Comic detail page cache (reader + admin tách riêng)
        // Xóa cả 2 vì có thể chapters count / list đã thay đổi
        Cache::forget("comic.detail.{$comicId}");
        Cache::forget("comic.detail.{$comicId}.admin");

        // Home page: latest updates phụ thuộc vào chapter mới nhất
        Cache::forget('home.latest');
        Cache::forget('home.trending');
    }

    public function created(Chapter $chapter): void
    {
        $this->invalidateForComic($chapter->comic_id);
    }

    public function updated(Chapter $chapter): void
    {
        $this->invalidateForComic($chapter->comic_id);
    }

    public function deleted(Chapter $chapter): void
    {
        $this->invalidateForComic($chapter->comic_id);
    }

    public function restored(Chapter $chapter): void
    {
        $this->invalidateForComic($chapter->comic_id);
    }
}
