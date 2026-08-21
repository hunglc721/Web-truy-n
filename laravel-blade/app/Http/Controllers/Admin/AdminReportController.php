<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminReportController extends Controller
{
    /**
     * Danh sách báo cáo sự cố độc giả với bộ lọc trạng thái và loại lỗi.
     */
    public function index(Request $request)
    {
        $statusFilter = $request->input('status', 'all');
        $typeFilter   = $request->input('type', 'all');
        $search       = $request->input('search');

        $query = Report::query();

        // 1. Lọc theo trạng thái
        if ($statusFilter === 'pending') {
            $query->pending();
        } elseif ($statusFilter === 'processing') {
            $query->processing();
        } elseif ($statusFilter === 'resolved') {
            $query->resolved();
        } elseif ($statusFilter === 'dismissed') {
            $query->dismissed();
        }

        // 2. Lọc theo loại lỗi
        if ($typeFilter !== 'all' && !empty($typeFilter)) {
            $query->where('type', $typeFilter);
        }

        // 3. Tìm kiếm theo tên truyện, chapter, mô tả hoặc người báo
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('comic', fn ($c) => $c->where('title', 'like', "%{$search}%"))
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $reports = $query->with(['comic', 'chapter', 'user', 'comment'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Thống kê nhanh
        $stats = [
            'total'      => Report::count(),
            'pending'    => Report::pending()->count(),
            'processing' => Report::processing()->count(),
            'resolved'   => Report::resolved()->count(),
            'dismissed'  => Report::dismissed()->count(),
        ];

        return view('admin.reports.index', compact('reports', 'stats', 'statusFilter', 'typeFilter', 'search'));
    }

    /**
     * Cập nhật trạng thái xử lý báo cáo sự cố (Pending → Processing → Resolved / Dismissed).
     */
    public function updateStatus(Report $report, Request $request)
    {
        $validated = $request->validate([
            'status'     => 'required|string|in:pending,processing,resolved,dismissed',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $report->status;
        $report->update($validated);

        ActivityLog::record('admin.report.status_updated', $report, [
            'old_status' => $oldStatus,
            'new_status' => $report->status,
            'admin_id'   => Auth::id(),
            'admin_note' => $report->admin_note,
        ]);

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái báo cáo sự cố thành công!');
    }

    /**
     * Xóa báo cáo sự cố.
     */
    public function destroy(Report $report)
    {
        $report->delete();

        ActivityLog::record('admin.report.deleted', $report, [
            'admin_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Đã xóa bản ghi báo cáo thành công.');
    }
}
