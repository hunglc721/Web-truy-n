<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletAndLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_balance_endpoint_is_disabled(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('api.wallet.balance'))
            ->assertStatus(410)
            ->assertJson([
                'status' => 'disabled',
            ]);
    }

    public function test_wallet_deposit_endpoint_is_disabled(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.wallet.deposit'), ['amount' => 100])
            ->assertStatus(410)
            ->assertJson([
                'status' => 'disabled',
            ]);
    }

    public function test_chapter_unlock_endpoint_is_disabled(): void
    {
        $user = User::factory()->create();
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->postJson(route('api.chapters.unlock', $chapter->id))
            ->assertStatus(410)
            ->assertJson([
                'status' => 'disabled',
            ]);
    }

    public function test_published_chapter_is_readable_without_wallet_or_vip(): void
    {
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'slug' => 'free-chapter',
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('chapters.show', [$comic->slug, $chapter->slug]))
            ->assertOk();
    }
}
