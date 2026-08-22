<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutOtherDevicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_logout_other_devices_with_valid_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('my-secret-password'),
        ]);

        $response = $this->actingAs($user)->post(route('user.logoutOtherDevices'), [
            'password' => 'my-secret-password',
        ]);

        $response->assertSessionHas('success');
    }

    public function test_user_cannot_logout_other_devices_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('my-secret-password'),
        ]);

        $response = $this->actingAs($user)->post(route('user.logoutOtherDevices'), [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
