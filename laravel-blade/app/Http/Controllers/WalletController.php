<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'unauthenticated'], 401);
        }

        $wallet = $user->getOrCreateWallet();
        $transactions = $user->transactions()->limit(10)->get();

        return response()->json([
            'status'       => 'success',
            'balance'      => $wallet->balance,
            'is_vip'       => $user->isVip(),
            'transactions' => $transactions,
        ]);
    }

    public function unlockChapter(Request $request, int $chapterId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'unauthenticated', 'message' => 'Vui lòng đăng nhập để mở khóa chương.'], 401);
        }

        $chapter = Chapter::with('comic')->findOrFail($chapterId);

        try {
            $unlock = $this->walletService->unlockChapter($user, $chapter);

            return response()->json([
                'status'        => 'success',
                'message'       => "Mở khóa Chapter {$chapter->chapter_number} thành công!",
                'coins_paid'    => $unlock->coins_paid,
                'balance_after' => $user->fresh()->coin_balance,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function deposit(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'unauthenticated'], 401);
        }

        $request->validate([
            'amount' => 'required|integer|min:10|max:100000',
        ]);

        $amount = (int) $request->amount;
        $transaction = $this->walletService->deposit(
            $user,
            $amount,
            "Nạp {$amount} coin vào ví",
            'deposit_order',
            null
        );

        return response()->json([
            'status'         => 'success',
            'message'        => "Nạp thành công {$amount} coin!",
            'balance_after'  => $transaction->balance_after,
            'transaction_id' => $transaction->uuid,
        ]);
    }
}
