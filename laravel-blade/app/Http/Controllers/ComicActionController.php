<?php
// app/Http/Controllers/ComicActionController.php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\ComicLike;
use App\Models\Library;
use App\Models\ActivityLog;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComicActionController extends Controller
{
    public function __construct(
        protected RecommendationService $recommendationService
    ) {}



    /**
     * TOGGLE LIKE — Thích/Bỏ thích một bộ truyện.
     *
     * POST /api/comics/{comicId}/toggle-like
     * → JSON: { status, is_liked, like_count }
     */
    public function toggleLike(int $comicId): JsonResponse
    {
        $user  = Auth::user();
        $comic = Comic::findOrFail($comicId);

        $existing = ComicLike::where('user_id', $user->id)
                             ->where('comic_id', $comicId)
                             ->first();

        if ($existing) {
            $existing->delete();
            $comic->refresh();
            $likeCount = (int) $comic->likes_count;
            ActivityLog::record('comic.unliked', $comic, ['comic_id' => $comicId]);
            return response()->json([
                'status'     => 'success',
                'is_liked'   => false,
                'like_count' => $likeCount,
                'message'    => 'Đã bỏ thích truyện này.',
            ]);
        }

        ComicLike::create([
            'user_id'  => $user->id,
            'comic_id' => $comicId,
            'liked_at' => now(),
        ]);
        $comic->refresh();
        $likeCount = (int) $comic->likes_count;
        ActivityLog::record('comic.liked', $comic, ['comic_id' => $comicId]);

        return response()->json([
            'status'     => 'success',
            'is_liked'   => true,
            'like_count' => $likeCount,
            'message'    => 'Đã thích "' . $comic->title . '"! ❤️',
        ]);
    }
}
