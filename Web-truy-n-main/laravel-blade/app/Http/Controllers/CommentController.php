<?php

namespace App\Http\Controllers;

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
     * Lưu bình luận mới qua AJAX request kèm tự động duyệt & lọc từ nhạy cảm
     */
    public function store(Request $request)
    {
        $request->validate([
            'comic_id'   => 'required|exists:comics,id',
            'chapter_id' => 'nullable|exists:chapters,id',
            'parent_id'  => 'nullable|exists:comments,id',
            'content'    => 'required|string|max:1000',
        ]);

        // 1. Chạy Filter Service kiểm tra Spam Link & Lọc từ cấm
        $processed = $this->filterService->process($request->content);

        // 2. Lưu bình luận vào Database với trạng thái tương ứng
        $comment = Comment::create([
            'user_id'    => auth()->id(),
            'comic_id'   => $request->comic_id,
            'chapter_id' => $request->chapter_id,
            'parent_id'  => $request->parent_id,
            'content'    => $processed['content'],
            'status'     => $processed['status'], // 'approved' hoặc 'spam'
        ]);

        $comment->load('user');

        $isSpam = $processed['status'] === 'spam';

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
            ]
        ]);
    }
}
