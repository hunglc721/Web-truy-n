<?php
// app/Http/Controllers/ComicActionController.php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\ComicLike;
use App\Models\Library;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComicActionController extends Controller
{
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
            // Đã theo dõi → Bỏ theo dõi
            $existing->delete();
            return response()->json([
                'status'     => 'success',
                'in_library' => false,
                'is_followed'=> false, // backward-compat với code cũ
                'message'    => 'Đã bỏ theo dõi "' . $comic->title . '"',
            ]);
        }

        // Chưa theo dõi → Thêm vào Library
        Library::create([
            'user_id'  => $user->id,
            'comic_id' => $comicId,
            'status'   => 'reading',
            'added_at' => now(),
        ]);

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
            // Đã like → Unlike
            $existing->delete();
            $likeCount = $comic->likes()->count();

            return response()->json([
                'status'     => 'success',
                'is_liked'   => false,
                'like_count' => $likeCount,
                'message'    => 'Đã bỏ thích truyện này.',
            ]);
        }

        // Chưa like → Like
        ComicLike::create([
            'user_id'  => $user->id,
            'comic_id' => $comicId,
            'liked_at' => now(),
        ]);

        $likeCount = $comic->likes()->count();

        return response()->json([
            'status'     => 'success',
            'is_liked'   => true,
            'like_count' => $likeCount,
            'message'    => 'Đã thích "' . $comic->title . '"! ❤️',
        ]);
    }
}
