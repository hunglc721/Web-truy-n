<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest('id');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('action', 'like', "%{$keyword}%")
                    ->orWhere('ip_address', 'like', "%{$keyword}%")
                    ->orWhere('subject_type', 'like', "%{$keyword}%")
                    ->orWhere('subject_id', $keyword)
                    ->orWhereHas('user', function ($uq) use ($keyword) {
                        $uq->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($request->filled('action_group')) {
            $query->where('action', 'like', $request->action_group . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(25)->withQueryString();
        $actionGroups = [
            'admin.comic' => 'Quản lý Truyện',
            'admin.chapter' => 'Quản lý Chapter',
            'admin.genre' => 'Thể loại',
            'admin.tag' => 'Tags',
            'admin.author' => 'Tác giả',
            'admin.comment' => 'Kiểm duyệt Bình luận',
            'admin.report' => 'Xử lý Báo cáo',
            'admin.schedule' => 'Lịch Phát Hành',
            'admin.banner' => 'Banner',
            'admin.user' => 'Thành viên',
            'admin.permissions' => 'Phân quyền',
            'admin.settings' => 'Cài đặt',
            'auth' => 'Đăng nhập / Đăng xuất',
            'comment' => 'Tương tác Bình luận',
            'comic' => 'Tương tác Truyện',
        ];

        $users = User::whereHas('activityLogs')
            ->orWhere('is_admin', true)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view('admin.logs.index', compact('logs', 'actionGroups', 'users'));
    }

    /**
     * Chỉ dọn log cũ theo retention; không hỗ trợ xóa sạch toàn bộ audit trail.
     */
    public function clear(Request $request)
    {
        $validated = $request->validate([
            'days' => 'required|integer|in:30,60,90,180,365',
        ]);

        $days = (int) $validated['days'];
        $deletedCount = ActivityLog::where('created_at', '<', now()->subDays($days))->delete();

        ActivityLog::record('admin.logs.retention_cleanup', null, [
            'admin_id' => Auth::id(),
            'deleted_count' => $deletedCount,
            'retention_days' => $days,
        ]);

        return redirect()->route('admin.logs.index')
            ->with('success', "Đã dọn {$deletedCount} log cũ hơn {$days} ngày; các log mới vẫn được giữ nguyên.");
    }
}
