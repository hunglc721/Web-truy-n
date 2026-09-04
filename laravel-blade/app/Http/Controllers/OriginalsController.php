<?php
// app/Http/Controllers/OriginalsController.php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OriginalsController extends Controller
{
    public function index(Request $request)
    {
        $selectedGenre = trim((string) $request->input('genre', ''));

        $spotlight = Comic::where('is_original', true)
            ->whereHas('chapters', fn ($query) => $query->published())
            ->with(['genres', 'authors', 'tags', 'latestChapter'])
            ->orderByDesc('is_featured')
            ->orderByDesc('avg_rating')
            ->first();

        $originalsQuery = Comic::originals()
            ->whereHas('chapters', fn ($query) => $query->published())
            ->with(['genres', 'tags', 'latestChapter'])
            ->withCount('ratings');

        if ($selectedGenre !== '') {
            $originalsQuery->whereHas('genres', fn ($query) => $query->where('slug', $selectedGenre));
        }

        $originals = $originalsQuery
            ->orderByDesc('views')
            ->orderByDesc('avg_rating')
            ->get();

        $genres = Cache::remember('originals.genres', 1800, function () {
            return Genre::whereHas('comics', fn ($query) => $query
                    ->where('is_original', true)
                    ->whereHas('chapters', fn ($chapterQuery) => $chapterQuery->published()))
                ->orderBy('name')
                ->get();
        });

        $latestTrends = Cache::remember('originals.latest_trends', 900, function () {
            return Comic::originals()
                ->whereHas('chapters', fn ($query) => $query->published())
                ->with(['genres', 'latestChapter', 'tags'])
                ->orderByDesc('views')
                ->orderByDesc('avg_rating')
                ->take(6)
                ->get();
        });

        $recentOriginalUpdates = Cache::remember('originals.recent_updates', 300, function () {
            return Comic::originals()
                ->whereHas('chapters', fn ($query) => $query->published())
                ->with(['genres', 'latestChapter', 'tags'])
                ->withMax(['chapters' => fn ($query) => $query->published()], 'published_at')
                ->orderByDesc('chapters_max_published_at')
                ->take(6)
                ->get();
        });

        return view('originals', compact(
            'spotlight',
            'originals',
            'genres',
            'selectedGenre',
            'latestTrends',
            'recentOriginalUpdates'
        ));
    }
}
