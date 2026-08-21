<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAdminBroadcastNotifications;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Announcement::with(['creator', 'targetUser'])->latest();

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('audience')) {
            $query->where('audience', $request->audience);
        }

        $announcements = $query->paginate(20)->withQueryString();
        $roles = Role::whereIn('slug', ['member', 'admin', 'moderator', 'editor', 'viewer'])->orderBy('name')->get();
        $users = User::orderBy('name')->limit(500)->get(['id', 'name', 'email']);

        $stats = [
            'total' => Announcement::count(),
            'active' => Announcement::where('is_active', true)->count(),
            'emergency' => Announcement::where('severity', 'emergency')->where('is_active', true)->count(),
            'scheduled' => Announcement::where('is_active', true)->where('starts_at', '>', now())->count(),
        ];

        return view('admin.notifications.index', compact('announcements', 'roles', 'users', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:160',
            'message' => 'required|string|max:5000',
            'severity' => 'required|in:info,success,warning,emergency',
            'audience' => 'required|in:all,guests,authenticated,role,user',
            'role_slug' => 'nullable|required_if:audience,role|exists:roles,slug',
            'target_user_id' => 'nullable|required_if:audience,user|exists:users,id',
            'link_url' => 'nullable|string|max:500',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
        ]);

        $link = $this->validateLink($validated['link_url'] ?? null);
        $audience = $validated['audience'];
        $severity = $validated['severity'];
        $startsAt = !empty($validated['starts_at']) ? Carbon::parse($validated['starts_at']) : now();
        $endsAt = !empty($validated['ends_at']) ? Carbon::parse($validated['ends_at']) : null;

        if ($endsAt && $endsAt->lte($startsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
            ]);
        }

        $announcement = Announcement::create([
            'created_by' => $request->user()->id,
            'target_user_id' => $audience === 'user' ? ($validated['target_user_id'] ?? null) : null,
            'title' => trim($validated['title']),
            'message' => trim($validated['message']),
            'severity' => $severity,
            'audience' => $audience,
            'role_slug' => $audience === 'role' ? ($validated['role_slug'] ?? null) : null,
            'link_url' => $link,
            'show_banner' => $severity === 'emergency' ? true : $request->boolean('show_banner'),
            'send_to_inbox' => $audience === 'guests' ? false : $request->boolean('send_to_inbox'),
            'is_dismissible' => $request->boolean('is_dismissible'),
            'is_active' => true,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        if ($announcement->send_to_inbox) {
            SendAdminBroadcastNotifications::dispatch($announcement)->onQueue('notifications');
        }

        ActivityLog::record('admin.notification.created', $announcement, [
            'severity' => $announcement->severity,
            'audience' => $announcement->audience,
            'show_banner' => $announcement->show_banner,
            'send_to_inbox' => $announcement->send_to_inbox,
        ]);

        return back()->with('success', $severity === 'emergency'
            ? 'Đã phát thông báo khẩn cấp.'
            : 'Đã tạo thông báo thành công.');
    }

    public function toggle(Announcement $announcement)
    {
        $announcement->update(['is_active' => !$announcement->is_active]);

        ActivityLog::record('admin.notification.toggled', $announcement, [
            'is_active' => $announcement->is_active,
        ]);

        return back()->with('success', $announcement->is_active ? 'Đã bật thông báo.' : 'Đã tắt thông báo.');
    }

    public function destroy(Announcement $announcement)
    {
        ActivityLog::record('admin.notification.deleted', $announcement, [
            'title' => $announcement->title,
            'severity' => $announcement->severity,
        ]);

        $announcement->delete();
        return back()->with('success', 'Đã xóa thông báo khỏi danh sách phát.');
    }

    private function validateLink(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return $url;
        }

        throw ValidationException::withMessages([
            'link_url' => 'Link phải là đường dẫn nội bộ bắt đầu bằng / hoặc URL http/https hợp lệ.',
        ]);
    }
}
