<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Subscription;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletAndLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_deposit_coins_and_creates_immutable_ledger_record(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);

        $tx = $walletService->deposit($user, 100, 'Nạp tiền qua VNPay', 'vnpay_order', 123);

        $this->assertEquals(0, $tx->balance_before);
        $this->assertEquals(100, $tx->balance_after);
        $this->assertEquals(100, $tx->amount);
        $this->assertNotNull($tx->uuid);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 100,
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id'        => $user->id,
            'amount'         => 100,
            'balance_before' => 0,
            'balance_after'  => 100,
        ]);
    }

    public function test_user_can_spend_coins_and_unlock_chapter(): void
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);
        $walletService->deposit($user, 50, 'Nạp tiền ban đầu');

        $comic = Comic::factory()->create(['title' => 'Võ Luyện Đỉnh Phong']);
        $chapter = Chapter::factory()->create([
            'comic_id'           => $comic->id,
            'chapter_number'     => 100,
            'is_free'            => false,
            'coin_price'         => 15,
            'early_access_until' => now()->addDays(3),
        ]);

        // Trước khi mở khóa: chưa có quyền đọc
        $this->assertFalse($chapter->isUnlockedFor($user));

        // Mở khóa chapter qua API
        $response = $this->actingAs($user)->postJson(route('api.chapters.unlock', $chapter->id));
        $response->assertOk()
            ->assertJson([
                'status'        => 'success',
                'coins_paid'    => 15,
                'balance_after' => 35,
            ]);

        // Sau khi mở khóa: có quyền đọc
        $this->assertTrue($chapter->fresh()->isUnlockedFor($user));

        // Kiểm tra ledger bất biến
        $this->assertDatabaseHas('transactions', [
            'user_id'        => $user->id,
            'amount'         => -15,
            'balance_before' => 50,
            'balance_after'  => 35,
        ]);

        $this->assertDatabaseHas('chapter_unlocks', [
            'user_id'    => $user->id,
            'chapter_id' => $chapter->id,
            'coins_paid' => 15,
        ]);
    }

    public function test_insufficient_coins_throws_error_and_does_not_deduct_balance(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'   => $comic->id,
            'is_free'    => false,
            'coin_price' => 50,
        ]);

        $response = $this->actingAs($user)->postJson(route('api.chapters.unlock', $chapter->id));
        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ]);

        $this->assertEquals(0, $user->fresh()->coin_balance);
        $this->assertDatabaseMissing('chapter_unlocks', [
            'user_id'    => $user->id,
            'chapter_id' => $chapter->id,
        ]);
    }

    public function test_vip_user_has_automatic_early_access_to_chapters(): void
    {
        $vipUser = User::factory()->create();
        Subscription::create([
            'user_id'    => $vipUser->id,
            'plan'       => 'vip_monthly',
            'starts_at'  => now(),
            'expires_at' => now()->addMonth(),
            'status'     => 'active',
        ]);

        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id'           => $comic->id,
            'chapter_number'     => 50,
            'is_free'            => false,
            'coin_price'         => 20,
            'early_access_until' => now()->addDays(7),
        ]);

        $this->assertTrue($vipUser->isVip());
        $this->assertTrue($chapter->isUnlockedFor($vipUser));
    }
}
