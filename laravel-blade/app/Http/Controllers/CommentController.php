<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Services\CommentFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    protected CommentFilterService $filterService;

    public function __construct(CommentFilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * Lưu bình luận mới qua AJAX request kèm tự động duyệt & lọc từ nhạy cảm.
     *
     * Fix #3 – Kiểm tra CommentPolicy::create() (chặn user bị ban).
     * Fix #2 – chapter_id scoped theo comic_id (chặn cross-comic chapter).
     * Fix #4 – parent_id phải cùng comic_id & chapter_id (chặn cross-comic reply).
     * Rate limit – 5 bình luận / phút / user.
     */
    public function store(Request $request)
    {
        // Fix #3: Authorization – CommentPolicy::create() từ chối user bị ban
        $this->authorize('create', Comment::class);

        // Rate limiting: tối đa 5 comment/phút mỗi user
        $rateLimitKey = 'comment:' . auth()->id();
        if (RateLimiter::tooManyAttempts($rateLimitKey, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'status'  => 'error',
                'message' => "Bạn đăng bình luận quá nhanh. Vui lòng thử lại sau {$seconds} giây.",
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, decay: 60);

        // Fix #2: chapter_id scoped theo comic_id – chặn cặp lệch nhau
        $comicId   = $request->input('comic_id');
        $chapterId = $request->input('chapter_id');
        $parentId  = $request->input('parent_id');

        $request->validate([
            'comic_id'   => 'required|exists:comics,id',
            // chapter_id chỉ hợp lệ nếu thuộc đúng comic_id đang gửi
            'chapter_id' => [
                'nullable',
                Rule::exists('chapters', 'id')->where('comic_id', $comicId),
            ],
            // Fix #4: parent comment phải thuộc cùng comic + chapter (nếu có)
            'parent_id'  => [
                'nullable',
                function ($attribute, $value, $fail) use ($comicId, $chapterId) {
                    if (is_null($value)) return;

                    $parent = Comment::find($value);

                    if (!$parent) {
                        $fail('Bình luận cha không tồn tại.');
                        return;
                    }

                    // Phải cùng comic
                    if ((int) $parent->comic_id !== (int) $comicId) {
                        $fail('Bình luận cha không thuộc truyện này.');
                        return;
                    }

                    // Nếu reply trong chapter → parent cũng phải cùng chapter
                    if (!is_null($chapterId) && (int) $parent->chapter_id !== (int) $chapterId) {
                        $fail('Bình luận cha không thuộc chương này.');
                    }
                },
            ],
            'content'    => 'required|string|max:1000',
        ]);

        // 1. Chạy Filter Service kiểm tra Spam Link & Lọc từ cấm
        $processed = $this->filterService->process($request->content);

        // 2. Lưu bình luận với status từ FilterService (fix #1: status đã có trong $fillable)
        $comment = Comment::create([
            'user_id'    => auth()->id(),
            'comic_id'   => $comicId,
            'chapter_id' => $chapterId,
            'parent_id'  => $parentId,
            'content'    => $processed['content'],
            'status'     => $processed['status'], // 'approved' hoặc 'spam'
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
