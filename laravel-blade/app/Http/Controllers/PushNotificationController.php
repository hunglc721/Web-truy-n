<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
            'public_key' => 'nullable|string',
            'auth_token' => 'nullable|string',
        ]);

        $userId = auth()->id();

        PushSubscription::updateOrCreate(
            ['endpoint' => $request->endpoint, 'user_id' => $userId],
            [
                'public_key'       => $request->public_key,
                'auth_token'       => $request->auth_token,
                'content_encoding' => $request->content_encoding ?? 'aesgcm',
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Đã bật thông báo đẩy trình duyệt thành công!',
        ]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        // Chỉ xóa subscription thuộc về user hiện tại, tránh xóa nhầm subscription của người khác.
        PushSubscription::where('endpoint', $request->endpoint)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Đã tắt thông báo đẩy.',
        ]);
    }
}
