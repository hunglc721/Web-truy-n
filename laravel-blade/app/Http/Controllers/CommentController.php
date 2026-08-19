<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Services\CommentFilterService;
use Illuminate\Support\Facades\RateLimiter;

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
     */
    public function store(StoreCommentRequest $request)
    {
        // Rate limiting: 5 bình luận / phút / user
        $rateLimitKey = 'comment:' . auth()->id();
        if (RateLimiter::tooManyAttempts($rateLimitKey, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'status'  => 'error',
                'message' => "Bạn đăng bình luận quá nhanh. Vui lòng thử lại sau {$seconds} giây.",
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, decay: 60);

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
}
