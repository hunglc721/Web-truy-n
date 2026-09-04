<?php

namespace App\Http\Controllers;

use App\Models\Tag;

class TagController extends Controller
{
    public function show(string $slug)
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $comics = $tag->comics()
            ->with(['genres', 'latestChapter', 'tags'])
            ->whereHas('chapters', fn ($query) => $query->published())
            ->orderByDesc('views')
            ->orderByDesc('avg_rating')
            ->paginate(18)
            ->withQueryString();

        return view('tags.show', compact('tag', 'comics'));
    }
}
