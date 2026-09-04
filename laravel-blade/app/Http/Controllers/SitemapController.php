<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Genre;
use App\Models\Tag;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $comics = Comic::query()
            ->select(['slug', 'updated_at'])
            ->whereHas('chapters', fn ($query) => $query->published())
            ->orderByDesc('updated_at')
            ->limit(45000)
            ->get();

        $genres = Genre::query()->select(['slug', 'updated_at'])->orderBy('name')->get();
        $tags = Tag::query()->select(['slug', 'updated_at'])->orderBy('name')->get();

        return response()
            ->view('sitemap.index', compact('comics', 'genres', 'tags'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
