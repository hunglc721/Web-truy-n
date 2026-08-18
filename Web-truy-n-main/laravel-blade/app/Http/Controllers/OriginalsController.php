<?php
// app/Http/Controllers/OriginalsController.php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Genre;

class OriginalsController extends Controller
{
    public function index()
    {
        /*
        |──────────────────────────────────────────────────────────
        | SPOTLIGHT — truyện is_featured=true, is_original=true
        | Ưu tiên Editor's Choice (is_featured), nếu không có thì
        | lấy truyện original có avg_rating cao nhất
        |──────────────────────────────────────────────────────────
        */
        $spotlight = Comic::where('is_original', true)
            ->with(['genres', 'authors', 'tags', 'latestChapter'])
            ->orderByDesc('is_featured')   // is_featured=1 lên đầu
            ->orderByDesc('avg_rating')
            ->first();

        /*
        |──────────────────────────────────────────────────────────
        | EDITOR'S PICKS — tất cả originals, sắp xếp views desc
        |──────────────────────────────────────────────────────────
        */
        $originals = Comic::originals()   // scope: where('is_original', true)
            ->with([
                'genres',
                'tags',
                'latestChapter',
            ])
            ->withCount('ratings')        // thêm ratings_count
            ->orderByDesc('views')
            ->get();

        /*
        |──────────────────────────────────────────────────────────
        | GENRE TABS — chỉ lấy thể loại có trong originals
        |──────────────────────────────────────────────────────────
        */
        $genres = Genre::whereHas('comics', fn($q) => $q->where('is_original', true))
            ->orderBy('name')
            ->get();

        return view('originals', compact('spotlight', 'originals', 'genres'));
    }
}
