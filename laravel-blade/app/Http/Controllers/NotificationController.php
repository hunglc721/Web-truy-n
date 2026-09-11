<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20);
        return view('user.notifications.index', compact('notifications'));
    }

    public function header(Request $request)
    {
        $accept = (string) $request->header('Accept', '');
        if ($request->boolean('stream') || str_contains($accept, 'text/event-stream')) {
            return $this->stream($request->user());
        }

        return response()->json($this->headerPayload($request->user()));
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

    private function stream(User $user): StreamedResponse
    {
        $testing = app()->environment('testing');
        $maxSeconds = $testing ? 0.0 : 25.0;

        return response()->stream(function () use ($user, $testing, $maxSeconds) {
            $startedAt = microtime(true);
            $lastSignature = null;

            do {
                $payload = $this->headerPayload($user);
                $signature = $this->payloadSignature($payload);

                if ($signature !== $lastSignature) {
                    echo "retry: 1000\n";
                    echo "event: snapshot\n";
                    echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                    $lastSignature = $signature;
                } else {
                    echo ": keep-alive\n\n";
                }

                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
                flush();

                if ($testing || connection_aborted()) {
                    break;
                }

                sleep(2);
            } while ((microtime(true) - $startedAt) < $maxSeconds);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function headerPayload(User $user): array
    {
        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->latest()->limit(8)->get()->map(fn ($notification) => [
                'id' => $notification->id,
                'data' => $notification->data,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->diffForHumans(),
                'open_url' => route('user.notifications.open', $notification->id),
            ])->values()->all(),
            'all_url' => route('user.notifications.index'),
        ];
    }

    private function payloadSignature(array $payload): string
    {
        $state = [
            'unread_count' => $payload['unread_count'],
            'notifications' => array_map(
                fn (array $notification) => [$notification['id'], $notification['read_at']],
                $payload['notifications']
            ),
        ];

        return hash('sha256', json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
