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
     * TOGGLE TỦ SÁCH — Thêm/Xóa truyện khỏi Library của user.
     *
     * POST /api/comics/{comicId}/toggle-library
     * → JSON: { status, in_library, message }
     */
    public function toggleLibrary(int $comicId): JsonResponse
    {
        $user  = Auth::user();
        $comic = Comic::findOrFail($comicId);

        $existing = Library::where('user_id', $user->id)
                           ->where('comic_id', $comicId)
                           ->first();

        if ($existing) {
            $existing->delete();
            ActivityLog::record('comic.unfollowed', $comic, ['comic_id' => $comicId]);
            $this->recommendationService->invalidateForUser($user->id);

            return response()->json([
                'status'     => 'success',
                'in_library' => false,
                'is_followed'=> false,
                'message'    => 'Đã bỏ theo dõi "' . $comic->title . '"',
            ]);
        }

        Library::create([
            'user_id'  => $user->id,
            'comic_id' => $comicId,
            'status'   => 'reading',
            'added_at' => now(),
        ]);
        ActivityLog::record('comic.followed', $comic, ['comic_id' => $comicId]);
        $this->recommendationService->invalidateForUser($user->id);

        return response()->json([
            'status'     => 'success',
            'in_library' => true,
            'is_followed'=> true,
            'message'    => 'Đã thêm "' . $comic->title . '" vào Tủ Sách!',
        ]);
    }

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
            $likeCount = $comic->likes()->count();
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
        $likeCount = $comic->likes()->count();
        ActivityLog::record('comic.liked', $comic, ['comic_id' => $comicId]);

        return response()->json([
            'status'     => 'success',
            'is_liked'   => true,
            'like_count' => $likeCount,
            'message'    => 'Đã thích "' . $comic->title . '"! ❤️',
        ]);
    }
}
