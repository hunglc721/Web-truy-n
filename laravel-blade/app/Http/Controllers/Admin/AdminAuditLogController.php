<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuditLogController extends Controller
{
    /**
     * Danh sách nhật ký hoạt động hệ thống kèm bộ lọc và tìm kiếm.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with(['user'])->latest('id');

        // Tìm kiếm theo từ khóa (tên user, action, ip, subject)
        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('action', 'like', "%{$keyword}%")
                    ->orWhere('ip_address', 'like', "%{$keyword}%")
                    ->orWhere('subject_type', 'like', "%{$keyword}%")
                    ->orWhereHas('user', function ($uq) use ($keyword) {
                        $uq->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
            });
        }

        // Lọc theo nhóm Action
        if ($request->filled('action_group')) {
            $group = $request->action_group;
            $query->where('action', 'like', "{$group}%");
        }

        // Lọc theo User cụ thể
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Lọc theo ngày
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(25)->withQueryString();

        // Danh sách các nhóm hành động để filter
        $actionGroups = [
            'admin.comic'    => 'Quản lý Truyện & Chapter',
            'admin.comment'  => 'Kiểm duyệt Bình luận',
            'admin.report'   => 'Xử lý Báo cáo',
            'admin.schedule' => 'Lịch Phát Sóng',
            'admin.banner'   => 'Banner Quảng cáo',
            'admin.user'     => 'Quản lý Thành viên',
            'auth'           => 'Đăng nhập / Đăng xuất',
            'comment'        => 'Tương tác Bình luận',
            'comic'          => 'Tương tác Yêu thích',
        ];

        $users = User::where('is_admin', true)
            ->orWhereHas('activityLogs')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view('admin.logs.index', compact('logs', 'actionGroups', 'users'));
    }

    /**
     * Dọn dẹp nhật ký hoạt động cũ.
     */
    public function clear(Request $request)
    {
        $days = (int) $request->input('days', 30);

        if ($days > 0) {
            $deletedCount = ActivityLog::where('created_at', '<', now()->subDays($days))->delete();
            $msg = "Đã dọn dẹp {$deletedCount} bản ghi nhật ký cũ hơn {$days} ngày.";
        } else {
            $deletedCount = ActivityLog::query()->delete();
            $msg = "Đã xóa toàn bộ {$deletedCount} bản ghi nhật ký hoạt động.";
        }

        ActivityLog::record('admin.logs.cleared', null, [
            'admin_id'      => Auth::id(),
            'deleted_count' => $deletedCount,
            'days'          => $days,
        ]);

        return redirect()->route('admin.logs.index')->with('success', $msg);
    }
}
