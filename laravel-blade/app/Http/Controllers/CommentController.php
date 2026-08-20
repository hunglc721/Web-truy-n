<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Events\CommentCreated;
use App\Models\Comment;
use App\Services\CommentFilterService;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    protected CommentFilterService $filterService;

    public function __construct(CommentFilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * Lưu bình luận mới qua AJAX.
     * Authorization & validation đã được xử lý bởi StoreCommentRequest.
     * Rate limiting: throttle:comments (5 req/phút) áp dụng tại route.
     */
    public function store(StoreCommentRequest $request): JsonResponse
    {
        // Chạy Filter Service: phát hiện spam link & lọc từ cấm
        $processed = $this->filterService->process($request->content);

        $comment = Comment::create([
            'user_id'    => auth()->id(),
            'comic_id'   => $request->comic_id,
            'chapter_id' => $request->chapter_id,
            'parent_id'  => $request->parent_id,
            'content'    => $processed['content'],
            'status'     => $processed['status'],
        ]);

        $comment->load('user');

        // Fire event — LogCommentCreated listener ghi ActivityLog
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
                'user_name' => $comment->user->name ?? 'User',
                'content'   => e($comment->content),
                'status'    => $comment->status,
                'time_ago'  => 'Vừa xong',
            ],
        ]);
    }

    /**
     * Sửa nội dung bình luận qua AJAX.
     * Authorization: CommentPolicy::update() — chủ bình luận trong 15 phút, admin bất kỳ lúc.
     * Route: PATCH /api/comments/{comment}
     */
    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        // Lọc lại nội dung sau khi sửa (chặn thêm spam/từ cấm mới)
        $processed = $this->filterService->process($request->content);

        $comment->update([
            'content' => $processed['content'],
            // Nếu nội dung sau khi sửa chứa spam, chuyển về pending duyệt lại
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

    /**
     * Xóa mềm bình luận qua AJAX.
     * Authorization: CommentPolicy::delete() — chủ bình luận hoặc admin.
     * Route: DELETE /api/comments/{comment}
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete(); // SoftDelete

        return response()->json([
            'status'  => 'success',
            'message' => 'Bình luận đã được xóa.',
        ]);
    }
}
