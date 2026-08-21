<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20);
        return view('user.notifications.index', compact('notifications'));
    }

    public function header(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->latest()->limit(8)->get()->map(fn ($notification) => [
                'id' => $notification->id,
                'data' => $notification->data,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->diffForHumans(),
                'open_url' => route('user.notifications.open', $notification->id),
            ]),
            'all_url' => route('user.notifications.index'),
        ]);
    }

    public function open(Request $request, string $id)
    {
        $notification = $this->ownedNotification($request, $id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('user.notifications.index');
        if (!$this->isSafeUrl($url)) {
            $url = route('user.notifications.index');
        }

        return redirect()->to($url);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success']);
        }

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }

    public function destroy(Request $request, string $id)
    {
        $this->ownedNotification($request, $id)->delete();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success']);
        }

        return back()->with('success', 'Đã xóa thông báo.');
    }

    private function ownedNotification(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->whereKey($id)->firstOrFail();
    }

    private function isSafeUrl(?string $url): bool
    {
        if (!$url) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        return filter_var($url, FILTER_VALIDATE_URL)
            && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }
}
