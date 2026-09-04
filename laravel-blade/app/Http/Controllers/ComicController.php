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
     *  - comic_detail (metadata, genres, authors, tags, teams, chapters): TTL 30 phút
     *  - related_comics: TTL 60 phút
     *  - views được increment qua buffer riêng
     */
    public function show(string $slug)
    {
        $isAdmin = auth()->check() && auth()->user()->isAdmin();

        $cacheKey = $isAdmin
            ? "comic.detail.{$slug}.admin"
            : "comic.detail.{$slug}";

        $comic = Cache::remember($cacheKey, 1800, function () use ($slug, $isAdmin) {
            return Comic::where('slug', $slug)
                ->with([
                    'genres',
                    'authors',
                    'tags',
                    'teams',
                    'chapters' => fn ($q) => $q
                        ->when(!$isAdmin, fn ($q) => $q->published())
                        ->orderByDesc('chapter_number')
                        ->take(20),
                ])
                ->withCount([
                    'chapters' => fn ($q) => $q->when(!$isAdmin, fn ($q) => $q->published()),
                ])
                ->firstOrFail();
        });

        if (!$isAdmin) {
            $userOrIp = auth()->id() ?? request()->ip();
            $antiF5Key = "view_comic:{$comic->id}:{$userOrIp}";
            if (Cache::add($antiF5Key, true, 900)) {
                \App\Jobs\FlushViewCounters::recordComicView($comic->id);
            }
        }

        $firstGenreSlug = $comic->genres->first()?->slug ?? '';
        $relatedComics = Cache::remember(
            "comic.related.{$comic->id}",
            3600,
            function () use ($comic, $firstGenreSlug) {
                if ($firstGenreSlug === '') {
                    return collect();
                }

                return Comic::with(['genres', 'latestChapter'])
                    ->byGenre($firstGenreSlug)
                    ->whereHas('chapters', fn ($query) => $query->published())
                    ->where('id', '!=', $comic->id)
                    ->orderByDesc('avg_rating')
                    ->take(6)
                    ->get();
            }
        );

        $recommendations = auth()->check()
            ? $this->recommendationService->forUser(auth()->user(), 4)
            : collect();

        $likeCount = $comic->likes_count;
        $isLiked = false;
        $isSaved = false;
        $lastHistory = null;
        $lastChapter = null;

        if (auth()->check()) {
            $user = auth()->user();
            $isLiked = $comic->hasLikedBy($user->id);
            $isSaved = $user->hasInLibrary($comic->id);
            $lastHistory = $user->readingHistoryForComic($comic->id);
            $lastChapter = $lastHistory?->chapter;
        }

        $firstChapter = $comic->chapters->last();
        $latestChapter = $comic->chapters->first();

        return view('comics.show', compact(
            'comic', 'relatedComics', 'recommendations', 'isAdmin',
            'likeCount', 'isLiked', 'isSaved', 'lastHistory', 'lastChapter',
            'firstChapter', 'latestChapter'
        ));
    }
}
