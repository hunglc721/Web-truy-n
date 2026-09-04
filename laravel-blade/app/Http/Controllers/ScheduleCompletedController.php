<?php

namespace App\Http\Controllers;

use App\Models\Comic;

class ScheduleCompletedController extends Controller
{
    public function index()
    {
        $comics = Comic::query()
            ->where('status', 'completed')
            ->whereHas('chapters', fn ($query) => $query->published())
            ->with(['genres', 'latestChapter', 'authors', 'tags'])
            ->withCount(['chapters' => fn ($query) => $query->published()])
            ->orderByDesc('avg_rating')
            ->orderByDesc('views')
            ->paginate(24);

        return view('schedule-completed', compact('comics'));
    }
}
