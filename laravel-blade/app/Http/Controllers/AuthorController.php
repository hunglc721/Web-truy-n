<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function show(string $slug)
    {
        $author = Author::where('slug', $slug)
            ->with(['comics' => function ($q) {
                $q->with(['genres', 'latestChapter'])
                  ->orderByDesc('views');
            }])
            ->withCount('followers')
            ->firstOrFail();

        $isFollowed = auth()->check() ? $author->isFollowedBy(auth()->user()) : false;

        return view('authors.show', compact('author', 'isFollowed'));
    }

    public function follow(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'unauthenticated', 'message' => 'Vui lòng đăng nhập.'], 401);
        }

        $author = Author::findOrFail($id);
        $isFollowing = $user->followedAuthors()->where('authors.id', $author->id)->exists();

        if ($isFollowing) {
            $user->followedAuthors()->detach($author->id);
            $action = 'unfollowed';
            $message = 'Đã hủy theo dõi tác giả.';
        } else {
            $user->followedAuthors()->attach($author->id);
            $action = 'followed';
            $message = 'Đã theo dõi tác giả thành công! Bạn sẽ nhận được thông báo khi có truyện hoặc chương mới.';
        }

        $followersCount = $author->followers()->count();

        return response()->json([
            'status'          => 'success',
            'action'          => $action,
            'is_followed'     => $action === 'followed',
            'followers_count' => $followersCount,
            'message'         => $message,
        ]);
    }
}
