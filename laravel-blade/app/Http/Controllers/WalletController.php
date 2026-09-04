<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Legacy compatibility controller.
 *
 * WebComics is a free-reading project. These endpoints remain temporarily so old
 * clients receive an explicit response instead of a controller-not-found 500.
 * They must not create balances, deposits, unlocks or paid access.
 */
class WalletController extends Controller
{
    public function balance(): JsonResponse
    {
        return $this->disabled();
    }

    public function deposit(): JsonResponse
    {
        return $this->disabled();
    }

    public function unlockChapter(): JsonResponse
    {
        return $this->disabled();
    }

    private function disabled(): JsonResponse
    {
        return response()->json([
            'status' => 'disabled',
            'message' => 'WebComics đọc miễn phí. Hệ thống coin, ví và mở khóa chapter đã ngừng sử dụng.',
        ], 410);
    }
}
