<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\Report;
use App\Models\User;

class AdminDashboardController extends Controller
{
    /**
     * Hiển thị bảng điều khiển tổng quan và số liệu thống kê thời gian thực.
     */
    public function index()
    {
        $stats = [
            'total_comics'       => Comic::count(),
            'total_chapters'     => Chapter::count(),
            'total_users'        => User::count(),
            'total_views'        => Comic::sum('views'),
            'pending_comments'   => Comment::where('status', Comment::STATUS_PENDING)->count(),
            'pending_reports'    => Report::where('status', Report::STATUS_PENDING)->count(),
        ];

        // Top 5 truyện có lượt đọc cao nhất
        $topComics = Comic::with(['genres', 'latestChapter'])
            ->orderByDesc('views')
            ->take(5)
            ->get();

        // 5 truyện mới cập nhật gần đây
        $recentComics = Comic::with(['genres', 'latestChapter'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        // 8 hoạt động quản trị gần nhất
        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(8)
            ->get();

        return view('admin.dashboard', compact('stats', 'topComics', 'recentComics', 'recentActivities'));
    }
}
