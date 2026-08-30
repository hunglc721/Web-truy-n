<?php

namespace App\Observers;

use App\Models\Comic;
use Illuminate\Support\Facades\Cache;

/**
 * ComicObserver — Xóa cache liên quan khi Comic được thêm/sửa/xóa.
 *
 * Đăng ký trong AppServiceProvider::boot():
 *   Comic::observe(ComicObserver::class);
 *
 * Các cache keys bị ảnh hưởng:
 *  - comic.detail.{slug}      → trang chi tiết truyện
 *  - comic.related.{id}       → truyện liên quan
 *  - home.trending            → trending list trên trang chủ
 *  - home.latest              → latest updates trang chủ
 *  - all_genres               → không cần xóa (genre không đổi khi comic đổi)
 *  - recommendations.guest    → trending-based guest recommendations
 */
class ComicObserver
{
    /**
     * Các cache keys cần xóa khi comic này thay đổi.
     */
    protected function invalidateForComic(Comic $comic): void
    {
        // Cache trang chi tiết theo slug
        Cache::forget("comic.detail.{$comic->slug}");

        // Cache truyện liên quan của comic này
        Cache::forget("comic.related.{$comic->id}");

        // Home page caches
        Cache::forget('home.trending');
        Cache::forget('home.latest');

        // Guest recommendation (trending-based)
        \App\Services\RecommendationService::invalidateGuestCache();
    }

    /**
     * Khi tạo truyện mới — xóa home caches vì latest list thay đổi.
     */
    public function created(Comic $comic): void
    {
        Cache::forget('home.latest');
        Cache::forget('home.trending');
        \App\Services\RecommendationService::invalidateGuestCache();
    }

    /**
     * Khi sửa truyện — xóa tất cả cache liên quan.
     * Bao gồm: đổi title, slug, cover, status, genres, trending_rank...
     */
    public function updated(Comic $comic): void
    {
        $this->invalidateForComic($comic);

        // Nếu slug thay đổi, xóa cache slug cũ
        if ($comic->wasChanged('slug') && $comic->getOriginal('slug')) {
            Cache::forget("comic.detail.{$comic->getOriginal('slug')}");
        }
    }

    /**
     * Khi xóa mềm truyện — xóa cache ngay để tránh hiển thị truyện đã xóa.
     */
    public function deleted(Comic $comic): void
    {
        $this->invalidateForComic($comic);
    }

    /**
     * Khi khôi phục truyện đã xóa.
     */
    public function restored(Comic $comic): void
    {
        $this->invalidateForComic($comic);
    }
}
