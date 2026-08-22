<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'email'    => 'testrate@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        RateLimiter::clear('testrate@example.com|127.0.0.1');

        // 5 lần thử đăng nhập sai
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('login'), [
                'email'    => 'testrate@example.com',
                'password' => 'wrong-password',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // Lần thứ 6 phải bị chặn bởi Rate Limiter
        $response = $this->post(route('login'), [
            'email'    => 'testrate@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('quá nhiều lần', session('errors')->first('email'));
    }
}
