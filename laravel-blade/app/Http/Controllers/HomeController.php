<?php
// app/Http/Controllers/HomeController.php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Comic;
use App\Models\Genre;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        /*
        |──────────────────────────────────────────────────────────
        | HERO BANNERS (BE-12) — cache 5 phút
        | Tự động lọc chỉ lấy banner đang bật (is_active = true)
        | và trong khoảng thời gian hiệu lực (start_at..end_at).
        |──────────────────────────────────────────────────────────
        */
        $banners = Cache::remember('home.banners', 300, function () {
            return Banner::active()->get();
        });

        /*
        |──────────────────────────────────────────────────────────
        | TRENDING — cache 30 phút; bị invalidate bởi ChapterObserver
        | khi có chapter mới được đăng (via artisan schedule hoặc admin)
        | latestChapter relationship đã áp scopePublished() — chỉ hiện
        | chapter đã tới giờ phát hành.
        |──────────────────────────────────────────────────────────
        */
        $trendingComics = Cache::remember('home.trending', 1800, function () {
            return Comic::trending()
                ->with(['genres', 'latestChapter', 'tags'])
                ->take(9)
                ->get();
        });

        /*
        |──────────────────────────────────────────────────────────
        | GENRE TABS — cache 60 phút; genres ít thay đổi
        |──────────────────────────────────────────────────────────
        */
        $genres = Cache::remember('home.genres', 3600, function () {
            return Genre::orderBy('name')->get();
        });

        /*
        |──────────────────────────────────────────────────────────
        | LATEST UPDATES — cache 5 phút; hay thay đổi nên TTL ngắn
        |
        | Publish-gate (BE-01):
        |   - withMax('chapters', 'published_at') được scope chỉ
        |     tính các chương đã phát hành (published_at <= now()).
        |   - whereHas('chapters') tương tự: chỉ lấy comic có ít
        |     nhất 1 chương đã phát hành.
        |   → Chương scheduled tương lai KHÔNG làm comic xuất hiện
        |     hoặc leo top trong danh sách Latest Updates.
        |──────────────────────────────────────────────────────────
        */
        $latestUpdates = Cache::remember('home.latest', 300, function () {
            return Comic::withMax(
                    // Chỉ tính max(published_at) của chương đã phát hành
                    ['chapters' => fn($q) => $q->published()],
                    'published_at'
                )
                ->with(['genres', 'latestChapter', 'tags'])
                ->whereHas('chapters', fn($q) => $q->published())  // ít nhất 1 chương published
                ->orderByDesc('chapters_max_published_at')
                ->take(15)
                ->get();
        });

        return view('home', compact('banners', 'trendingComics', 'genres', 'latestUpdates'));
    }
}
