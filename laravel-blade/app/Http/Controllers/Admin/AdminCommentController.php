<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminCommentController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->input('status', 'all');
        $search = $request->input('search');

        $query = Comment::query();

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

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('comic', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('comic_id')) {
            $query->where('comic_id', $request->integer('comic_id'));
        }

        $comments = $query->with(['user', 'comic', 'chapter', 'reports', 'parent.user'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

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

    public function approve(Comment $comment)
    {
        $comment->update(['status' => Comment::STATUS_APPROVED]);
        ActivityLog::record('admin.comment.approved', $comment, ['admin_id' => Auth::id()]);

        return back()->with('success', 'Đã duyệt bình luận thành công!');
    }

    public function hide(Comment $comment)
    {
        $comment->update(['status' => Comment::STATUS_HIDDEN]);
        ActivityLog::record('admin.comment.hidden', $comment, ['admin_id' => Auth::id()]);

        return back()->with('success', 'Đã ẩn bình luận khỏi trang đọc truyện.');
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();
        ActivityLog::record('admin.comment.soft_deleted', $comment, ['admin_id' => Auth::id()]);

        return back()->with('success', 'Đã xóa mềm bình luận.');
    }

    public function restore(int $id)
    {
        $comment = Comment::onlyTrashed()->findOrFail($id);
        $comment->restore();
        ActivityLog::record('admin.comment.restored', $comment, ['admin_id' => Auth::id()]);

        return back()->with('success', 'Đã khôi phục bình luận thành công.');
    }

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,hide,delete',
            'ids'    => 'required|array|min:1|max:100',
            'ids.*'  => 'integer|distinct|exists:comments,id',
        ]);

        $comments = Comment::whereIn('id', $validated['ids'])->get();

        DB::transaction(function () use ($comments, $validated) {
            foreach ($comments as $comment) {
                if ($validated['action'] === 'approve') {
                    $comment->update(['status' => Comment::STATUS_APPROVED]);
                } elseif ($validated['action'] === 'hide') {
                    $comment->update(['status' => Comment::STATUS_HIDDEN]);
                } else {
                    $comment->delete();
                }
            }
        });

        ActivityLog::record('admin.comment.bulk_' . $validated['action'], Auth::user(), [
            'admin_id' => Auth::id(),
            'comment_ids' => $comments->pluck('id')->all(),
            'count' => $comments->count(),
        ]);

        return back()->with('success', 'Đã xử lý ' . $comments->count() . ' bình luận được chọn.');
    }

    public function banUser(Comment $comment, Request $request)
    {
        $author = $comment->user;

        if (!$author) {
            return back()->with('error', 'Không tìm thấy thông tin tác giả bình luận.');
        }

        if ($author->isAdmin()) {
            return back()->with('error', 'Không thể khóa tài khoản của Quản trị viên.');
        }

        $author->update(['banned_at' => now()]);

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $author->id)->delete();
        }

        $comment->update(['status' => Comment::STATUS_HIDDEN]);

        ActivityLog::record('admin.comment.user_banned', $author, [
            'comment_id' => $comment->id,
            'banned_by'  => Auth::id(),
            'reason'     => $request->input('reason', 'Đăng bình luận vi phạm quy định cộng đồng'),
        ]);

        return back()->with('success', "Đã khóa tài khoản \"{$author->name}\" và ẩn bình luận vi phạm.");
    }
}
