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
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    protected CommentFilterService $filterService;

    public function __construct(CommentFilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * Lấy danh sách bình luận (phân biệt cấp truyện vs cấp chapter).
     * Query Parameters:
     *   - comic_id: int (bắt buộc)
     *   - chapter_id: int (tuỳ chọn) - nếu có thì lấy comment của chapter đó, nếu không có thì lấy comment cấp truyện (chapter_id IS NULL)
     *   - parent_id: null (chỉ lấy top-level comments, replies được eager load)
     */
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
            // Luồng 1: Bình luận thuộc về chương cụ thể
            $query->where('chapter_id', $request->chapter_id);
        } else {
            // Luồng 2: Bình luận cấp truyện (không gắn với chương nào)
            $query->whereNull('chapter_id');
        }

        $comments = $query->paginate(20);

        return response()->json([
            'status'   => 'success',
            'comments' => $comments,
        ]);
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
            'chapter_id' => $request->chapter_id ?: null,
            'parent_id'  => $request->parent_id ?: null,
            'content'    => $processed['content'],
            'status'     => $processed['status'],
        ]);

        $comment->load(['user', 'replies.user']);

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
                'parent_id' => $comment->parent_id,
                'chapter_id'=> $comment->chapter_id,
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

        // Ghi log trước khi xóa (admin moderation audit trail)
        ActivityLog::record('comment.deleted', $comment, [
            'comic_id'     => $comment->comic_id,
            'chapter_id'   => $comment->chapter_id,
            'deleted_by'   => auth()->id(),
            'is_own'       => $comment->user_id === auth()->id(),
        ]);

        $comment->delete(); // SoftDelete

        return response()->json([
            'status'  => 'success',
            'message' => 'Bình luận đã được xóa.',
        ]);
    }
}
