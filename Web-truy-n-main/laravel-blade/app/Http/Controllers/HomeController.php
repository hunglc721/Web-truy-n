<?php
// app/Http/Controllers/HomeController.php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Genre;

class HomeController extends Controller
{
    public function index()
    {
        /*
        |──────────────────────────────────────────────────────────
        | TRENDING — lấy 9 truyện có trending_rank, kèm eager load
        | genres (để hiển thị "Action · Fantasy") và latestChapter
        |──────────────────────────────────────────────────────────
        */
        $trendingComics = Comic::trending()         // scope: whereNotNull('trending_rank')->orderBy('trending_rank')
            ->with([
                'genres',           // ["Action", "Fantasy"]
                'latestChapter',    // chương số lớn nhất
                'tags',             // ["HOT", "ORIGINAL"]
            ])
            ->take(9)
            ->get();

        /*
        |──────────────────────────────────────────────────────────
        | GENRE TABS — lấy tất cả thể loại để render tab filter
        |──────────────────────────────────────────────────────────
        */
        $genres = Genre::orderBy('name')->get();

        /*
        |──────────────────────────────────────────────────────────
        | LATEST UPDATES — 15 truyện có chapter mới nhất
        | Dùng withMax để lấy max(published_at) của chapters
        | rồi orderByDesc để sắp xếp
        |──────────────────────────────────────────────────────────
        */
        $latestUpdates = Comic::withMax('chapters', 'published_at')  // thêm cột: chapters_max_published_at
            ->with([
                'genres',
                'latestChapter',
                'tags',
            ])
            ->whereHas('chapters')           // chỉ lấy truyện đã có chương
            ->orderByDesc('chapters_max_published_at')
            ->take(15)
            ->get();

        return view('home', compact('trendingComics', 'genres', 'latestUpdates'));
    }
}
