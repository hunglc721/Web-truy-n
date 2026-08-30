<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\StoryPublishingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminStoryRequestController extends Controller
{
    /**
     * Danh sách các đơn yêu cầu đăng truyện với bộ lọc & tìm kiếm.
     */
    public function index(Request $request)
    {
        $statusFilter = $request->input('status', 'all');
        $typeFilter   = $request->input('type', 'all');
        $search       = $request->input('search');

        $query = StoryPublishingRequest::query();

        // 1. Lọc theo trạng thái
        if ($statusFilter === 'pending') {
            $query->pending();
        } elseif ($statusFilter === 'reviewing') {
            $query->reviewing();
        } elseif ($statusFilter === 'approved') {
            $query->approved();
        } elseif ($statusFilter === 'rejected') {
            $query->rejected();
        }

        // 2. Lọc theo loại hình tác phẩm
        if ($typeFilter !== 'all' && !empty($typeFilter)) {
            $query->where('story_type', $typeFilter);
        }

        // 3. Tìm kiếm
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('story_title', 'like', "%{$search}%")
                  ->orWhere('creator_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_or_social', 'like', "%{$search}%")
                  ->orWhere('team_name', 'like', "%{$search}%");
            });
        }

        $storyRequests = $query->with(['user', 'reviewer'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Thống kê nhanh
        $stats = [
            'total'     => StoryPublishingRequest::count(),
            'pending'   => StoryPublishingRequest::pending()->count(),
            'reviewing' => StoryPublishingRequest::reviewing()->count(),
            'approved'  => StoryPublishingRequest::approved()->count(),
            'rejected'  => StoryPublishingRequest::rejected()->count(),
        ];

        return view('admin.story_requests.index', compact('storyRequests', 'stats', 'statusFilter', 'typeFilter', 'search'));
    }

    /**
     * Xem chi tiết thông tin đơn đăng ký đăng truyện & thẩm định.
     */
    public function show(StoryPublishingRequest $storyRequest)
    {
        $storyRequest->load(['user', 'reviewer']);

        return view('admin.story_requests.show', compact('storyRequest'));
    }

    /**
     * Cập nhật trạng thái thẩm định đơn đăng truyện (Phê duyệt, Từ chối, Đang thẩm định).
     */
    public function updateStatus(Request $request, StoryPublishingRequest $storyRequest)
    {
        $validated = $request->validate([
            'status'     => 'required|string|in:pending,reviewing,approved,rejected',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $oldStatus = $storyRequest->status;

        $storyRequest->update([
            'status'      => $validated['status'],
            'admin_note'  => $validated['admin_note'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        ActivityLog::record('admin.story_request.status_updated', $storyRequest, [
            'old_status' => $oldStatus,
            'new_status' => $storyRequest->status,
            'admin_id'   => Auth::id(),
            'admin_note' => $storyRequest->admin_note,
        ]);

        // Gửi thông báo hệ thống đến tác giả (nếu có tài khoản)
        if ($storyRequest->user_id) {
            $statusTitles = [
                'approved'  => '🎉 Đơn đăng truyện "' . $storyRequest->story_title . '" đã được PHÊ DUYỆT!',
                'rejected'  => '⚠️ Thông báo về đơn đăng truyện "' . $storyRequest->story_title . '"',
                'reviewing' => '🔍 Đơn đăng truyện "' . $storyRequest->story_title . '" đang được BQT thẩm định',
                'pending'   => '📋 Cập nhật đơn đăng truyện "' . $storyRequest->story_title . '"',
            ];

            $severityMap = [
                'approved'  => 'success',
                'rejected'  => 'warning',
                'reviewing' => 'info',
                'pending'   => 'info',
            ];

            Announcement::create([
                'created_by'     => Auth::id(),
                'target_user_id' => $storyRequest->user_id,
                'title'          => $statusTitles[$storyRequest->status] ?? 'Thông báo đơn đăng truyện',
                'message'        => $storyRequest->admin_note ?: ('Đơn đăng ký của bạn đã được cập nhật sang trạng thái: ' . $storyRequest->status_label),
                'severity'       => $severityMap[$storyRequest->status] ?? 'info',
                'audience'       => 'user',
                'link_url'       => route('user.publishingRequests'),
                'show_banner'    => false,
                'send_to_inbox'  => true,
                'is_dismissible' => true,
                'is_active'      => true,
            ]);
        }

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái đơn đăng truyện thành công!');
    }

    /**
     * Xóa đơn yêu cầu đăng truyện.
     */
    public function destroy(StoryPublishingRequest $storyRequest)
    {
        // Xóa file đính kèm nếu có
        if ($storyRequest->cover_image_path && Storage::disk('public')->exists($storyRequest->cover_image_path)) {
            Storage::disk('public')->delete($storyRequest->cover_image_path);
        }

        if ($storyRequest->sample_file_path && Storage::disk('public')->exists($storyRequest->sample_file_path)) {
            Storage::disk('public')->delete($storyRequest->sample_file_path);
        }

        $storyRequest->delete();

        ActivityLog::record('admin.story_request.deleted', $storyRequest, [
            'admin_id' => Auth::id(),
        ]);

        return redirect()->route('admin.storyRequests.index')->with('success', 'Đã xóa đơn đăng ký thành công.');
    }
}
