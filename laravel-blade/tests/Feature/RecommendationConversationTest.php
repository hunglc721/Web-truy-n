<?php

namespace Tests\Feature;

use App\Data\RecommendationPreference;
use App\Models\RecommendationConversation;
use App\Models\User;
use App\Services\RecommendationConversationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RecommendationConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_creation_and_token_persistence(): void
    {
        $service = app(RecommendationConversationService::class);
        $guest = $service->getOrCreate();
        $other = $service->getOrCreate();
        $this->assertNull($guest->user_id);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $guest->session_token);
        $this->assertNotSame($guest->session_token, $other->session_token);
        $this->assertArrayNotHasKey('session_token', $guest->toArray());
        $service->updatePreference($guest->id, RecommendationPreference::fromArray(['genres' => ['Fantasy']]), sessionToken: $guest->session_token);
        $freshService = new RecommendationConversationService;
        $this->assertSame($guest->id, $freshService->getOrCreate(sessionToken: $guest->session_token)->id);
        $this->assertSame(['Fantasy'], $freshService->getPreference($guest->id, sessionToken: $guest->session_token)->toArray()['genres']);
    }

    public function test_member_state_persists_and_merges_without_erasing_previous_signals(): void
    {
        $user = User::factory()->create();
        $service = app(RecommendationConversationService::class);
        $conversation = $service->getOrCreate($user);
        $this->assertSame($user->id, $conversation->user_id);
        $this->assertNull($conversation->session_token);
        $service->updatePreference($conversation->id, RecommendationPreference::fromArray([
            'genres' => ['Fantasy'], 'status' => 'completed', 'relationships' => ['Harem'],
        ]), $user);
        $merged = $service->updatePreference($conversation->id, RecommendationPreference::fromArray([
            'genres' => [], 'themes' => ['System'], 'status' => null, 'exclude' => ['Harem'],
        ]), $user);
        $this->assertSame(['Fantasy'], $merged->toArray()['genres']);
        $this->assertSame(['System'], $merged->toArray()['themes']);
        $this->assertSame('completed', $merged->toArray()['status']);
        $this->assertSame([], $merged->toArray()['relationships']);
        $this->assertSame(['Harem'], $merged->toArray()['exclude']);
        $freshService = new RecommendationConversationService;
        $this->assertSame($conversation->id, $freshService->getOrCreate($user->fresh())->id);
        $this->assertSame($merged->toArray(), $freshService->getPreference($conversation->id, $user->fresh())->toArray());
        $this->assertDatabaseCount('recommendation_conversations', 1);
    }

    public function test_cross_owner_reads_and_updates_fail_without_modifying_state(): void
    {
        $service = app(RecommendationConversationService::class);
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $member = $service->getOrCreate($owner);
        $guest = $service->getOrCreate();
        $otherGuest = $service->getOrCreate();
        foreach ([[$member->id, $intruder, null], [$member->id, null, $guest->session_token],
            [$guest->id, $owner, null], [$guest->id, null, $otherGuest->session_token],
            [$guest->id, null, str_repeat('a', 64)], [$guest->id, null, null]] as [$id, $user, $token]) {
            foreach (['read', 'update'] as $operation) {
                try {
                    if ($operation === 'read') {
                        $service->getPreference($id, $user, $token);
                    } else {
                        $service->updatePreference($id, RecommendationPreference::fromArray(['themes' => ['Revenge']]), $user, $token);
                    }
                    $this->fail('Cross-owner access must fail.');
                } catch (ModelNotFoundException) {
                    $this->assertSame([], RecommendationConversation::findOrFail($id)->preferences_json['themes']);
                }
            }
        }
    }

    public function test_unknown_guest_token_does_not_create_a_client_chosen_conversation(): void
    {
        $this->expectException(ModelNotFoundException::class);
        app(RecommendationConversationService::class)->getOrCreate(sessionToken: str_repeat('x', 64));
    }

    public function test_guest_token_case_variant_is_rejected(): void
    {
        $guest = RecommendationConversation::create([
            'session_token' => str_repeat('a', 64),
            'preferences_json' => RecommendationPreference::fromArray([])->toArray(),
        ]);
        $this->expectException(ModelNotFoundException::class);
        app(RecommendationConversationService::class)->getPreference($guest->id, sessionToken: strtoupper($guest->session_token));
    }

    public function test_update_uses_current_database_state_and_persists_only_preference_fields(): void
    {
        $user = User::factory()->create();
        $first = new RecommendationConversationService;
        $second = new RecommendationConversationService;
        $conversation = $first->getOrCreate($user);
        $stale = $second->getOrCreate($user);
        $first->updatePreference($conversation->id, RecommendationPreference::fromArray(['genres' => ['Fantasy']]), $user);
        $second->updatePreference($stale->id, RecommendationPreference::fromArray([
            'themes' => ['System'], 'prompt' => 'must not persist', 'api_key' => 'must not persist', 'response' => 'raw',
        ]), $user);
        $saved = $conversation->fresh()->preferences_json;
        $this->assertSame(['Fantasy'], $saved['genres']);
        $this->assertSame(['System'], $saved['themes']);
        $this->assertSame(array_keys(RecommendationPreference::fromArray([])->toArray()), array_keys($saved));
    }

    public function test_ambiguous_identity_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(RecommendationConversationService::class)->getOrCreate(User::factory()->create(), str_repeat('a', 64));
    }

    public function test_model_rejects_ownerless_conversations(): void
    {
        $this->expectException(InvalidArgumentException::class);
        RecommendationConversation::create(['preferences_json' => []]);
    }
}
