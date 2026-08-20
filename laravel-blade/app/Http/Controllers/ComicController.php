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
     * Cache strategy:
     *  - comic_detail (metadata, genres, authors, tags, chapters): TTL 30 phút
     *    → Bị invalidate bởi ComicObserver (saved/deleted)
     *  - related_comics: TTL 60 phút (ít thay đổi hơn)
     *  - views được increment trực tiếp, không cache
     */
    public function show(string $slug)
    {
        // ── Comic Detail Cache ─────────────────────────────────────────────
        // Cache key theo slug — ComicObserver::saved() sẽ forget key này
        $comic = Cache::remember("comic.detail.{$slug}", 1800, function () use ($slug) {
            return Comic::where('slug', $slug)
                ->with([
                    'genres',
                    'authors',
                    'tags',
                    'chapters' => fn($q) => $q->orderByDesc('chapter_number')->take(20),
                ])
                ->withCount('chapters')
                ->firstOrFail();
        });

        // ── View Counter — increment trực tiếp, session-throttle chống spam ─
        $sessionKey = "viewed_comic_{$comic->id}";
        if (!session()->has($sessionKey)) {
            $comic->increment('views');
            session()->put($sessionKey, true);
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

        // ── Recommendations (cá nhân hóa) ────────────────────────────────
        // Chỉ load nếu user đã đăng nhập; cache nằm trong RecommendationService
        $recommendations = auth()->check()
            ? $this->recommendationService->forUser(auth()->user(), 4)
            : collect();

        return view('comics.show', compact('comic', 'relatedComics', 'recommendations'));
    }
}
