<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Comic;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorAndTeamFollowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_author_profile(): void
    {
        $author = Author::factory()->create(['name' => 'Chugong', 'slug' => 'chugong']);
        $comic = Comic::factory()->create(['title' => 'Solo Leveling']);
        $author->comics()->attach($comic->id, ['role' => 'story']);

        $response = $this->get(route('authors.show', 'chugong'));
        $response->assertOk();
        $response->assertSee('Chugong');
        $response->assertSee('Solo Leveling');
    }

    public function test_user_can_follow_and_unfollow_author(): void
    {
        $user = User::factory()->create();
        $author = Author::factory()->create(['name' => 'Chugong']);

        // Follow
        $response = $this->actingAs($user)->postJson(route('api.authors.follow', $author->id));
        $response->assertOk()
            ->assertJson([
                'status'          => 'success',
                'action'          => 'followed',
                'is_followed'     => true,
                'followers_count' => 1,
            ]);

        $this->assertTrue($author->isFollowedBy($user));
        $this->assertDatabaseHas('author_follows', [
            'user_id'   => $user->id,
            'author_id' => $author->id,
        ]);

        // Unfollow
        $response = $this->actingAs($user)->postJson(route('api.authors.follow', $author->id));
        $response->assertOk()
            ->assertJson([
                'status'          => 'success',
                'action'          => 'unfollowed',
                'is_followed'     => false,
                'followers_count' => 0,
            ]);

        $this->assertFalse($author->isFollowedBy($user));
    }

    public function test_user_can_view_teams_index_and_team_profile(): void
    {
        $team = Team::create([
            'name'        => 'Lạc Thiên Team',
            'slug'        => 'lac-thien-team',
            'description' => 'Nhóm dịch truyện tranh số 1',
        ]);
        $comic = Comic::factory()->create(['title' => 'Trọng Sinh Đi Tu']);
        $team->comics()->attach($comic->id);

        $indexRes = $this->get(route('teams.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('Lạc Thiên Team');

        $showRes = $this->get(route('teams.show', 'lac-thien-team'));
        $showRes->assertOk();
        $showRes->assertSee('Lạc Thiên Team');
        $showRes->assertSee('Trọng Sinh Đi Tu');
    }

    public function test_user_can_follow_and_unfollow_team(): void
    {
        $user = User::factory()->create();
        $team = Team::create([
            'name' => 'A3 Manga',
            'slug' => 'a3-manga',
        ]);

        // Follow
        $response = $this->actingAs($user)->postJson(route('api.teams.follow', $team->id));
        $response->assertOk()
            ->assertJson([
                'status'          => 'success',
                'action'          => 'followed',
                'is_followed'     => true,
                'followers_count' => 1,
            ]);

        $this->assertTrue($team->isFollowedBy($user));
        $this->assertDatabaseHas('team_follows', [
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        // Unfollow
        $response = $this->actingAs($user)->postJson(route('api.teams.follow', $team->id));
        $response->assertOk()
            ->assertJson([
                'status'          => 'success',
                'action'          => 'unfollowed',
                'is_followed'     => false,
                'followers_count' => 0,
            ]);

        $this->assertFalse($team->isFollowedBy($user));
    }
}
