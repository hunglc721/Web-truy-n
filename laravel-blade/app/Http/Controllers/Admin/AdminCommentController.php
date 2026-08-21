<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminCommentController extends Controller
{
    /**
     * Danh sách bình luận độc giả với các bộ lọc (chờ duyệt, đã duyệt, đã ẩn, bị báo cáo, đã xóa mềm).
     */
    public function index(Request $request)
    {
        $statusFilter = $request->input('status', 'all');
        $search       = $request->input('search');

        $query = Comment::query();

        // 1. Lọc theo trạng thái
        if ($statusFilter === 'pending') {
            $query->pending();
        } elseif ($statusFilter === 'approved') {
            $query->approved();
        } elseif ($statusFilter === 'hidden') {
            $query->hidden();
        } elseif ($statusFilter === 'reported') {
            $query->reported();
        } elseif ($statusFilter === 'trashed') {
            $query->onlyTrashed();
        }

        // 2. Tìm kiếm theo nội dung, tên người dùng hoặc tên truyện
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('comic', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            });
        }

        // 3. Lọc theo truyện cụ thể
        if ($request->filled('comic_id')) {
            $query->where('comic_id', $request->comic_id);
        }

        $comments = $query->with(['user', 'comic', 'chapter', 'reports', 'parent.user'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Thống kê nhanh
        $stats = [
            'total'    => Comment::count(),
            'pending'  => Comment::pending()->count(),
            'approved' => Comment::approved()->count(),
            'hidden'   => Comment::hidden()->count(),
            'reported' => Comment::reported()->count(),
            'trashed'  => Comment::onlyTrashed()->count(),
        ];

        return view('admin.comments.index', compact('comments', 'stats', 'statusFilter', 'search'));
    }

    /**
     * Duyệt bình luận (Approved) → Hiện ngay lập tức trên trang đọc truyện.
     */
    public function approve(Comment $comment)
    {
        $comment->update(['status' => Comment::STATUS_APPROVED]);

        ActivityLog::record('admin.comment.approved', $comment, [
            'admin_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Đã duyệt bình luận thành công!');
    }

    /**
     * Ẩn bình luận (Hidden) → Không hiển thị ở trang public nhưng vẫn còn trong DB.
     */
    public function hide(Comment $comment)
    {
        $comment->update(['status' => Comment::STATUS_HIDDEN]);

        ActivityLog::record('admin.comment.hidden', $comment, [
            'admin_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Đã ẩn bình luận khỏi trang đọc truyện.');
    }

    /**
     * Xóa mềm bình luận (Soft Delete) → Bản ghi vẫn được lưu trong DB với deleted_at.
     */
    public function destroy(Comment $comment)
    {
        $comment->delete();

        ActivityLog::record('admin.comment.soft_deleted', $comment, [
            'admin_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Đã xóa mềm bình luận (vẫn lưu trữ trong CSDL).');
    }

    /**
     * Khôi phục bình luận đã bị xóa mềm.
     */
    public function restore(int $id)
    {
        $comment = Comment::onlyTrashed()->findOrFail($id);
        $comment->restore();

        ActivityLog::record('admin.comment.restored', $comment, [
            'admin_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Đã khôi phục bình luận thành công.');
    }

    /**
     * Khóa tài khoản nhanh tác giả bình luận vi phạm & ẩn bình luận.
     */
    public function banUser(Comment $comment, Request $request)
    {
        $author = $comment->user;

        if (!$author) {
            return redirect()->back()->with('error', 'Không tìm thấy thông tin tác giả bình luận.');
        }

        if ($author->isAdmin()) {
            return redirect()->back()->with('error', 'Không thể khóa tài khoản của Quản trị viên (Admin).');
        }

        // 1. Khóa tài khoản tác giả
        $author->update(['banned_at' => now()]);

        // 2. Vô hiệu hóa toàn bộ session của user
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $author->id)->delete();
        }

        // 3. Tự động ẩn bình luận vi phạm này
        $comment->update(['status' => Comment::STATUS_HIDDEN]);

        ActivityLog::record('admin.comment.user_banned', $author, [
            'comment_id' => $comment->id,
            'banned_by'  => Auth::id(),
            'reason'     => $request->input('reason', 'Đăng bình luận vi phạm quy định cộng đồng'),
        ]);

        return redirect()->back()->with('success', "Đã khóa tài khoản \"{$author->name}\" và ẩn bình luận vi phạm.");
    }
}
