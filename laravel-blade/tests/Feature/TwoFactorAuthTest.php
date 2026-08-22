<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_enable_2fa_with_valid_totp_code(): void
    {
        $user = User::factory()->create();
        $twoFactorService = app(TwoFactorService::class);
        $secret = $twoFactorService->generateSecretKey();

        // Giả lập session pending secret
        $this->actingAs($user)->withSession(['2fa_pending_secret' => $secret]);

        $validCode = $twoFactorService->calculateCode($secret, (int) floor(time() / 30));

        $response = $this->actingAs($user)->post(route('2fa.enable'), [
            'code' => $validCode,
        ]);

        $response->assertRedirect(route('2fa.show'));
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
        $this->assertNotNull($user->fresh()->two_factor_recovery_codes);
    }

    public function test_user_can_verify_2fa_challenge_using_recovery_code(): void
    {
        $user = User::factory()->create();
        $twoFactorService = app(TwoFactorService::class);
        $secret = $twoFactorService->generateSecretKey();
        $recoveryCodes = $twoFactorService->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret'         => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at'   => now(),
        ])->save();

        $usedCode = $recoveryCodes[0];

        $response = $this->actingAs($user)->post(route('2fa.challenge.verify'), [
            'recovery_code' => $usedCode,
        ]);

        $response->assertRedirect(route('user.library'));
        $this->assertTrue(session('2fa_passed'));

        // Mã đã dùng phải bị xóa khỏi danh sách
        $remainingCodes = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);
        $this->assertNotContains($usedCode, $remainingCodes);
    }
}
