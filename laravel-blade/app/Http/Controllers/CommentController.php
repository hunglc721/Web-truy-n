<?php

namespace App\Http\Controllers;

use App\Events\CommentCreated;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\CommentLike;
use App\Services\CommentFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    public function __construct(
        protected CommentFilterService $filterService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'comic_id'   => 'required|exists:comics,id',
            'chapter_id' => 'nullable|exists:chapters,id',
            'sort'       => 'nullable|in:newest,top',
        ]);

        $query = Comment::with([
                'user:id,name',
                'replies' => fn ($q) => $q->approved()->with('user:id,name')->orderBy('created_at'),
            ])
            ->where('comic_id', $request->integer('comic_id'))
            ->whereNull('parent_id')
            ->approved();

        if ($request->filled('chapter_id')) {
            $query->where('chapter_id', $request->integer('chapter_id'));
        } else {
            $query->whereNull('chapter_id');
        }

        if ($request->input('sort') === 'top') {
            $query->orderByDesc('likes_count')->orderByDesc('created_at');
        } else {
            $query->orderByDesc('created_at');
        }

        $comments = $query->paginate(20);
        $userId = auth()->id();

        if ($userId) {
            $commentIds = $comments->getCollection()
                ->flatMap(fn (Comment $comment) => collect([$comment->id])->merge($comment->replies->pluck('id')))
                ->unique()
                ->values();

            $likedIds = CommentLike::where('user_id', $userId)
                ->whereIn('comment_id', $commentIds)
                ->pluck('comment_id')
                ->flip();

            $comments->getCollection()->each(function (Comment $comment) use ($likedIds) {
                $comment->setAttribute('liked_by_me', $likedIds->has($comment->id));
                $comment->replies->each(
                    fn (Comment $reply) => $reply->setAttribute('liked_by_me', $likedIds->has($reply->id))
                );
            });
        }

        return response()->json([
            'status'   => 'success',
            'comments' => $comments,
        ]);
    }

    public function store(StoreCommentRequest $request): JsonResponse
    {
        $processed = $this->filterService->process($request->content);

        $comment = Comment::create([
            'user_id'    => auth()->id(),
            'comic_id'   => $request->comic_id,
            'chapter_id' => $request->chapter_id ?: null,
            'parent_id'  => $request->parent_id ?: null,
            'content'    => $processed['content'],
            'status'     => $processed['status'],
            'likes_count'=> 0,
        ]);

        $comment->load('user:id,name');
        CommentCreated::dispatch($comment);

        $isSpam = $processed['status'] === Comment::STATUS_SPAM;

        return response()->json([
            'status'       => 'success',
            'is_spam'      => $isSpam,
            'has_bad_word' => (bool) ($processed['has_bad_word'] ?? false),
            'message'      => $isSpam
                ? 'Bình luận chứa liên kết/thông tin nghi vấn và đã được đưa vào danh sách chờ duyệt.'
                : (($processed['has_bad_word'] ?? false)
                    ? 'Bình luận đã được tự động làm sạch từ nhạy cảm và đăng thành công.'
                    : 'Đã đăng bình luận thành công!'),
            'comment' => [
                'id'           => $comment->id,
                'user_id'      => $comment->user_id,
                'user_name'    => e($comment->user->name ?? 'User'),
                'content'      => e($comment->content),
                'status'       => $comment->status,
                'time_ago'     => 'Vừa xong',
                'parent_id'    => $comment->parent_id,
                'chapter_id'   => $comment->chapter_id,
                'likes_count'  => 0,
                'liked_by_me'  => false,
            ],
        ]);
    }

    public function toggleLike(Comment $comment): JsonResponse
    {
        if ($comment->status !== Comment::STATUS_APPROVED) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Chỉ có thể thích bình luận đã được duyệt.',
            ], 422);
        }

        $userId = auth()->id();

        $result = DB::transaction(function () use ($comment, $userId) {
            $existing = CommentLike::where('comment_id', $comment->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->delete();
                $isLiked = false;
            } else {
                CommentLike::create([
                    'comment_id' => $comment->id,
                    'user_id'    => $userId,
                ]);
                $isLiked = true;
            }

            $likesCount = CommentLike::where('comment_id', $comment->id)->count();
            $comment->update(['likes_count' => $likesCount]);

            return [$isLiked, $likesCount];
        });

        return response()->json([
            'status'      => 'success',
            'is_liked'    => $result[0],
            'likes_count' => $result[1],
        ]);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $processed = $this->filterService->process($request->content);

        $comment->update([
            'content' => $processed['content'],
            'status'  => $processed['status'] === Comment::STATUS_SPAM
                ? Comment::STATUS_PENDING
                : $comment->status,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Bình luận đã được cập nhật.',
            'comment' => [
                'id'      => $comment->id,
                'content' => e($comment->content),
            ],
        ]);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        ActivityLog::record('comment.deleted', $comment, [
            'comic_id'   => $comment->comic_id,
            'chapter_id' => $comment->chapter_id,
            'deleted_by' => auth()->id(),
            'is_own'     => $comment->user_id === auth()->id(),
        ]);

        $comment->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Bình luận đã được xóa.',
        ]);
    }
}
