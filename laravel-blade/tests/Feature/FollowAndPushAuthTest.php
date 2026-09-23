<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\PushSubscription;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowAndPushAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_follow_author(): void
    {
        $author = Author::factory()->create();

        $this->postJson(route('api.authors.follow', $author->id))
            ->assertUnauthorized();

        $this->assertDatabaseMissing('author_follows', ['author_id' => $author->id]);
    }

    public function test_guest_cannot_follow_team(): void
    {
        $team = Team::create(['name' => 'A3 Manga', 'slug' => 'a3-manga']);

        $this->postJson(route('api.teams.follow', $team->id))
            ->assertUnauthorized();

        $this->assertDatabaseMissing('team_follows', ['team_id' => $team->id]);
    }

    public function test_guest_cannot_subscribe_to_push(): void
    {
        $this->postJson(route('api.push.subscribe'), [
            'endpoint' => 'https://example.com/endpoint/guest',
        ])->assertUnauthorized();

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => 'https://example.com/endpoint/guest',
        ]);
    }

    public function test_guest_cannot_unsubscribe_from_push(): void
    {
        $this->postJson(route('api.push.unsubscribe'), [
            'endpoint' => 'https://example.com/endpoint/guest',
        ])->assertUnauthorized();
    }

    public function test_user_can_subscribe_and_unsubscribe_own_subscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.push.subscribe'), [
            'endpoint' => 'https://example.com/endpoint/own',
        ])->assertOk()->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id'  => $user->id,
            'endpoint' => 'https://example.com/endpoint/own',
        ]);

        $this->actingAs($user)->postJson(route('api.push.unsubscribe'), [
            'endpoint' => 'https://example.com/endpoint/own',
        ])->assertOk()->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => 'https://example.com/endpoint/own',
        ]);
    }

    public function test_user_cannot_unsubscribe_other_users_subscription(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        PushSubscription::create([
            'user_id'  => $owner->id,
            'endpoint' => 'https://example.com/endpoint/owner',
        ]);

        // Kẻ tấn công cố xóa subscription của người khác.
        $this->actingAs($attacker)->postJson(route('api.push.unsubscribe'), [
            'endpoint' => 'https://example.com/endpoint/owner',
        ])->assertOk()->assertJson(['status' => 'success']);

        // Subscription của chủ sở hữu vẫn còn nguyên.
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id'  => $owner->id,
            'endpoint' => 'https://example.com/endpoint/owner',
        ]);
    }
}
