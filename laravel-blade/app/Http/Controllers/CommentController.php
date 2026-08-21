<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Events\CommentCreated;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Services\CommentFilterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    protected CommentFilterService $filterService;

    public function __construct(CommentFilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'comic_id'   => 'required|exists:comics,id',
            'chapter_id' => 'nullable|exists:chapters,id',
        ]);

        $query = Comment::with(['user', 'replies.user'])
            ->where('comic_id', $request->comic_id)
            ->whereNull('parent_id')
            ->approved()
            ->orderBy('created_at', 'desc');

        if ($request->filled('chapter_id')) {
            $query->where('chapter_id', $request->chapter_id);
        } else {
            $query->whereNull('chapter_id');
        }

        $comments = $query->paginate(20);

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
        ]);

        $comment->load(['user', 'replies.user']);
        CommentCreated::dispatch($comment);

        $isSpam = $processed['status'] === Comment::STATUS_SPAM;

        return response()->json([
            'status'  => 'success',
            'is_spam' => $isSpam,
            'message' => $isSpam
                ? 'Bình luận của bạn chứa liên kết/thông tin nghi vấn và đã được đưa vào danh sách chờ duyệt.'
                : 'Đã đăng bình luận thành công!',
            'comment' => [
                'id'        => $comment->id,
                // Reader inserts this response into an HTML fragment, so both
                // user-controlled fields must be escaped before returning.
                'user_name' => e($comment->user->name ?? 'User'),
                'content'   => e($comment->content),
                'status'    => $comment->status,
                'time_ago'  => 'Vừa xong',
                'parent_id' => $comment->parent_id,
                'chapter_id'=> $comment->chapter_id,
            ],
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
            'comic_id'     => $comment->comic_id,
            'chapter_id'   => $comment->chapter_id,
            'deleted_by'   => auth()->id(),
            'is_own'       => $comment->user_id === auth()->id(),
        ]);

        $comment->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Bình luận đã được xóa.',
        ]);
    }
}
