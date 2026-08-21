<?php
// app/Http/Controllers/ComicController.php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Cache;

class ComicController extends Controller
{
    public function __construct(
        protected RecommendationService $recommendationService
    ) {}

    /**
     * Trang chi tiết truyện — /truyen/{slug}
     *
     * Publish-gate (BE-01):
     *   - Guest/Member: eager-load chapters chỉ lấy chương đã phát hành
     *   - Admin: thấy tất cả chương kể cả lên lịch tương lai (preview)
     *
     * Cache strategy:
     *  - comic_detail (metadata, genres, authors, tags, chapters): TTL 30 phút
     *    → Bị invalidate bởi ComicObserver (saved/deleted)
     *  - related_comics: TTL 60 phút (ít thay đổi hơn)
     *  - views được increment trực tiếp, không cache
     */
    public function show(string $slug)
    {
        $isAdmin = auth()->check() && auth()->user()->isAdmin();

        // ── Comic Detail Cache ─────────────────────────────────────────────
        // Cache key tách riêng giữa admin và reader để tải đúng danh sách chương
        $cacheKey = $isAdmin
            ? "comic.detail.{$slug}.admin"
            : "comic.detail.{$slug}";

        $comic = Cache::remember($cacheKey, 1800, function () use ($slug, $isAdmin) {
            return Comic::where('slug', $slug)
                ->with([
                    'genres',
                    'authors',
                    'tags',
                    // Admin: xem cả chương chưa phát hành (preview)
                    // Guest/Member: chỉ lấy chương published_at <= now()
                    'chapters' => fn($q) => $q
                        ->when(!$isAdmin, fn($q) => $q->published())
                        ->orderByDesc('chapter_number')
                        ->take(20),
                ])
                ->withCount([
                    // withCount cũng chỉ đếm chương đã phát hành với reader
                    'chapters' => fn($q) => $q->when(!$isAdmin, fn($q) => $q->published()),
                ])
                ->firstOrFail();
        });

        // ── View Counter — Đếm qua Cache buffer, chống F5 bằng TTL 30 phút ──
        if (!$isAdmin) {
            $userOrIp  = auth()->id() ?? request()->ip();
            $antiF5Key = "view_comic:{$comic->id}:{$userOrIp}";
            if (Cache::add($antiF5Key, true, 1800)) {
                \App\Jobs\FlushViewCounters::recordComicView($comic->id);
            }
        }

        // ── Related Comics Cache ───────────────────────────────────────────
        $firstGenreSlug = $comic->genres->first()?->slug ?? '';
        $relatedComics = Cache::remember(
            "comic.related.{$comic->id}",
            3600,
            function () use ($comic, $firstGenreSlug) {
                return Comic::with(['genres', 'latestChapter'])
                    ->byGenre($firstGenreSlug)
                    ->where('id', '!=', $comic->id)
                    ->orderByDesc('avg_rating')
                    ->take(6)
                    ->get();
            }
        );

        // ── Recommendations (đã cá nhân hóa) ────────────────────────────────
        // Chỉ load nếu user đã đăng nhập; cache nằm trong RecommendationService
        $recommendations = auth()->check()
            ? $this->recommendationService->forUser(auth()->user(), 4)
            : collect();

        return view('comics.show', compact('comic', 'relatedComics', 'recommendations', 'isAdmin'));
    }
}
