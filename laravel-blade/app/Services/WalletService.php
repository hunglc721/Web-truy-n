<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\ChapterUnlock;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletService
{
    /**
     * Nạp coin vào ví người dùng và ghi nhận Sổ Cái Bất Biến (Immutable Ledger).
     */
    public function deposit(
        User $user,
        int $amount,
        string $description,
        ?string $refType = null,
        ?int $refId = null
    ): Transaction {
        if ($amount <= 0) {
            throw new RuntimeException('Số coin nạp phải lớn hơn 0.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $refType, $refId) {
            // Khóa dòng ví để chống race-condition tuyệt đối
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'locked_balance' => 0]
            );

            // Re-fetch with row-level lock
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            $balanceBefore = $wallet->balance;
            $balanceAfter  = $balanceBefore + $amount;

            $wallet->balance = $balanceAfter;
            $wallet->save();

            return Transaction::create([
                'user_id'        => $user->id,
                'wallet_id'      => $wallet->id,
                'type'           => 'deposit',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'description'    => $description,
                'reference_type' => $refType,
                'reference_id'   => $refId,
            ]);
        });
    }

    /**
     * Trừ coin từ ví người dùng và ghi nhận Sổ Cái Bất Biến (Immutable Ledger).
     */
    public function spend(
        User $user,
        int $amount,
        string $description,
        ?string $refType = null,
        ?int $refId = null
    ): Transaction {
        if ($amount <= 0) {
            throw new RuntimeException('Số coin thanh toán phải lớn hơn 0.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $refType, $refId) {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'locked_balance' => 0]
            );

            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            if ($wallet->balance < $amount) {
                throw new RuntimeException("Số dư ví không đủ ({$wallet->balance} coin). Cần {$amount} coin để tiếp tục.");
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter  = $balanceBefore - $amount;

            $wallet->balance = $balanceAfter;
            $wallet->save();

            return Transaction::create([
                'user_id'        => $user->id,
                'wallet_id'      => $wallet->id,
                'type'           => 'chapter_unlock',
                'amount'         => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'description'    => $description,
                'reference_type' => $refType,
                'reference_id'   => $refId,
            ]);
        });
    }

    /**
     * Mở khóa chương truyện bằng coin với cơ chế giao dịch khép kín.
     */
    public function unlockChapter(User $user, Chapter $chapter): ChapterUnlock
    {
        // 1. Kiểm tra nếu đã mở khóa trước đó
        $existing = ChapterUnlock::where('user_id', $user->id)
            ->where('chapter_id', $chapter->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $price = $chapter->coin_price > 0 ? $chapter->coin_price : 10;
        $comicTitle = $chapter->comic->title ?? 'Truyện';

        return DB::transaction(function () use ($user, $chapter, $price, $comicTitle) {
            $transaction = $this->spend(
                $user,
                $price,
                "Mở khóa Chapter {$chapter->chapter_number} - {$comicTitle}",
                Chapter::class,
                $chapter->id
            );

            return ChapterUnlock::create([
                'user_id'        => $user->id,
                'chapter_id'     => $chapter->id,
                'transaction_id' => $transaction->id,
                'coins_paid'     => $price,
            ]);
        });
    }
}
