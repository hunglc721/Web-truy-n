<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\ReadingList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReadingListController extends Controller
{
    public function index()
    {
        $lists = ReadingList::where('is_public', true)
            ->with('user')
            ->withCount('comics', 'likes')
            ->orderByDesc('likes_count')
            ->orderByDesc('views_count')
            ->paginate(15);

        return view('lists.index', compact('lists'));
    }

    public function show(string $slug)
    {
        $list = ReadingList::where('slug', $slug)
            ->with(['user', 'comics' => function ($q) {
                $q->with(['genres', 'latestChapter'])
                  ->orderByDesc('views');
            }])
            ->withCount('comics', 'likes')
            ->firstOrFail();

        // Tăng view counter
        $list->increment('views_count');

        $isLiked = auth()->check() ? $list->isLikedBy(auth()->user()) : false;

        return view('lists.show', compact('list', 'isLiked'));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'unauthenticated'], 401);
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'is_public'   => 'nullable|boolean',
            'comic_ids'   => 'nullable|array',
            'comic_ids.*' => 'exists:comics,id',
        ]);

        $list = ReadingList::create([
            'user_id'     => $user->id,
            'title'       => $validated['title'],
            'slug'        => Str::slug($validated['title']) . '-' . Str::random(6),
            'description' => $validated['description'] ?? null,
            'is_public'   => $request->boolean('is_public', true),
        ]);

        if (!empty($validated['comic_ids'])) {
            $syncData = [];
            foreach ($validated['comic_ids'] as $pos => $comicId) {
                $syncData[$comicId] = ['order_position' => $pos];
            }
            $list->comics()->sync($syncData);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Đã tạo danh sách truyện thành công!',
            'list'    => [
                'id'    => $list->id,
                'title' => $list->title,
                'slug'  => $list->slug,
                'url'   => route('lists.show', $list->slug),
            ],
        ], 201);
    }

    public function toggleLike(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'unauthenticated'], 401);
        }

        $list = ReadingList::findOrFail($id);
        $isLiked = $list->isLikedBy($user);

        if ($isLiked) {
            $list->likes()->detach($user->id);
            $list->decrement('likes_count');
            $action = 'unliked';
        } else {
            $list->likes()->attach($user->id);
            $list->increment('likes_count');
            $action = 'liked';
        }

        return response()->json([
            'status'      => 'success',
            'action'      => $action,
            'is_liked'    => $action === 'liked',
            'likes_count' => $list->fresh()->likes_count,
        ]);
    }
}
