<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Comic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChapterCatalogController extends Controller
{
    public function index(Request $request, Comic $comic): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:50'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $sort = $validated['sort'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 20);

        $query = Chapter::query()
            ->where('comic_id', $comic->id)
            ->published();

        if ($q !== '') {
            $query->where(function ($chapterQuery) use ($q) {
                if (is_numeric($q)) {
                    $chapterQuery->where('chapter_number', (float) $q)
                        ->orWhere('title', 'like', '%' . $q . '%');
                } else {
                    $chapterQuery->where('title', 'like', '%' . $q . '%');
                }
            });
        }

        $query->orderBy('chapter_number', $sort === 'asc' ? 'asc' : 'desc');

        $chapters = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'status' => 'success',
            'data' => collect($chapters->items())->map(fn (Chapter $chapter) => [
                'id' => $chapter->id,
                'chapter_number' => $chapter->chapter_number,
                'title' => $chapter->title,
                'slug' => $chapter->slug,
                'published_at' => $chapter->published_at?->toIso8601String(),
                'time_ago' => $chapter->time_ago,
                'url' => route('chapters.show', [$comic->slug, $chapter->slug]),
            ])->values(),
            'meta' => [
                'current_page' => $chapters->currentPage(),
                'last_page' => $chapters->lastPage(),
                'per_page' => $chapters->perPage(),
                'total' => $chapters->total(),
                'from' => $chapters->firstItem(),
                'to' => $chapters->lastItem(),
            ],
        ]);
    }
}
