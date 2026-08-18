<?php
// app/Http/Controllers/ComicController.php

namespace App\Http\Controllers;

use App\Models\Comic;
use Illuminate\Http\Request;

class ComicController extends Controller
{
    /**
     * Trang chi tiết truyện — /comics/{slug}
     * Ví dụ: /comics/solo-leveling
     */
    public function show(string $slug)
    {
        // firstOrFail() → tự động trả 404 nếu không tìm thấy slug
        $comic = Comic::where('slug', $slug)
            ->with([
                'genres',
                'authors',
                'tags',
                'chapters' => fn($q) => $q->orderByDesc('chapter_number')->take(20),
                'ratings',
            ])
            ->withCount('chapters')
            ->firstOrFail();

        // Tăng lượt xem (không cần refresh để tránh N+1)
        $comic->increment('views');

        // Truyện liên quan: cùng thể loại, khác slug
        $relatedComics = Comic::with(['genres', 'latestChapter'])
            ->byGenre($comic->genres->first()?->slug ?? '')
            ->where('id', '!=', $comic->id)
            ->orderByDesc('avg_rating')
            ->take(6)
            ->get();

        return view('comics.show', compact('comic', 'relatedComics'));
    }
}
