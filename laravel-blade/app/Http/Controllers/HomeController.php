<?php
// app/Http/Controllers/HomeController.php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Genre;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        /*
        |──────────────────────────────────────────────────────────
        | TRENDING — cache 30 phút; bị invalidate bởi ChapterObserver
        | khi có chapter mới được đăng (via artisan schedule hoặc admin)
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
        |──────────────────────────────────────────────────────────
        */
        $latestUpdates = Cache::remember('home.latest', 300, function () {
            return Comic::withMax('chapters', 'published_at')
                ->with(['genres', 'latestChapter', 'tags'])
                ->whereHas('chapters')
                ->orderByDesc('chapters_max_published_at')
                ->take(15)
                ->get();
        });

        return view('home', compact('trendingComics', 'genres', 'latestUpdates'));
    }
}
