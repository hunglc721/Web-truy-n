<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Events\CommentCreated;
use App\Models\Comment;
use App\Services\CommentFilterService;
use Illuminate\Http\Request;

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
        // Rate limiting đã được xử lý bởi throttle:comments middleware trong routes/web.php

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

        // Task 13: Fire event — LogCommentCreated listener ghi ActivityLog
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
}
