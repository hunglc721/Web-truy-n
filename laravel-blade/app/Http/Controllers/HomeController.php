<?php
// app/Http/Controllers/HomeController.php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Comic;
use App\Models\Genre;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $banners = Cache::remember('home.banners', 300, function () {
            return Banner::active()->get();
        });

        $trendingComics = Cache::remember('home.trending', 1800, function () {
            return Comic::trending()
                ->whereHas('chapters', fn ($query) => $query->published())
                ->with(['genres', 'latestChapter', 'tags'])
                ->take(9)
                ->get();
        });

        $genres = Cache::remember('home.genres', 3600, function () {
            return Genre::orderBy('name')->get();
        });

        $latestUpdates = Cache::remember('home.latest', 300, function () {
            return Comic::withMax(
                    ['chapters' => fn ($query) => $query->published()],
                    'published_at'
                )
                ->with(['genres', 'latestChapter', 'tags'])
                ->whereHas('chapters', fn ($query) => $query->published())
                ->orderByDesc('chapters_max_published_at')
                ->take(15)
                ->get();
        });

        // Daily Picks: ổn định trong cùng một ngày, tự đổi vào ngày tiếp theo.
        $dailyPicks = Cache::remember('home.daily_picks.' . now()->format('Y-m-d'), now()->endOfDay(), function () {
            $pool = Comic::query()
                ->whereHas('chapters', fn ($query) => $query->published())
                ->with(['genres', 'latestChapter', 'tags'])
                ->orderByDesc('is_featured')
                ->orderByDesc('avg_rating')
                ->orderByDesc('views')
                ->take(30)
                ->get();

            return $this->rotateDaily($pool, 12);
        });

        // New Arrivals khác Recent Updates: đây là series mới được thêm, không phải series vừa có chapter mới.
        $newArrivals = Cache::remember('home.new_arrivals', 600, function () {
            return Comic::query()
                ->whereHas('chapters', fn ($query) => $query->published())
                ->with(['genres', 'latestChapter', 'tags'])
                ->latest('created_at')
                ->take(12)
                ->get();
        });

        // Các thể loại có dữ liệu nhất, mỗi thể loại lấy 6 bộ hot để homepage không thành một bức tường 30 section rỗng.
        $hottestByGenre = Cache::remember('home.hottest_by_genre', 1800, function () {
            $topGenres = Genre::query()
                ->withCount([
                    'comics' => fn ($query) => $query->whereHas('chapters', fn ($chapterQuery) => $chapterQuery->published()),
                ])
                ->having('comics_count', '>', 0)
                ->orderByDesc('comics_count')
                ->orderBy('name')
                ->take(4)
                ->get();

            return $topGenres->map(function (Genre $genre) {
                $comics = $genre->comics()
                    ->whereHas('chapters', fn ($query) => $query->published())
                    ->with(['genres', 'latestChapter', 'tags'])
                    ->orderByDesc('views')
                    ->orderByDesc('avg_rating')
                    ->take(6)
                    ->get();

                return [
                    'genre' => $genre,
                    'comics' => $comics,
                ];
            });
        });

        $recentReadings = auth()->check()
            ? \App\Models\ReadingHistory::with(['comic.genres', 'chapter'])
                ->where('user_id', auth()->id())
                ->whereHas('comic')
                ->whereHas('chapter')
                ->latest('last_read_at')
                ->take(4)
                ->get()
            : collect();

        return view('home', compact(
            'banners',
            'trendingComics',
            'genres',
            'latestUpdates',
            'dailyPicks',
            'newArrivals',
            'hottestByGenre',
            'recentReadings'
        ));
    }

    private function rotateDaily(Collection $pool, int $limit): Collection
    {
        if ($pool->isEmpty() || $pool->count() <= $limit) {
            return $pool->take($limit)->values();
        }

        $offset = now()->dayOfYear % $pool->count();

        return $pool->slice($offset)
            ->concat($pool->slice(0, $offset))
            ->take($limit)
            ->values();
    }
}
