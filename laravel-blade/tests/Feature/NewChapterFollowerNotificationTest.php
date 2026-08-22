<?php

namespace Tests\Feature;

use App\Jobs\SendChapterFollowerNotifications;
use App\Models\Author;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Library;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NewChapterFollowerNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_chapter_follower_notifications_job_notifies_comic_author_and_team_followers(): void
    {
        $user1 = User::factory()->create(); // Có comic trong Library
        $user2 = User::factory()->create(); // Follow Author của comic
        $user3 = User::factory()->create(); // Follow Team của comic
        $user4 = User::factory()->create(); // Không follow gì

        $author = Author::factory()->create();
        $team = Team::create(['name' => 'Dragon Team', 'slug' => 'dragon-team']);
        $comic = Comic::factory()->create();

        $comic->authors()->attach($author->id, ['role' => 'both']);
        $comic->teams()->attach($team->id);

        Library::create(['user_id' => $user1->id, 'comic_id' => $comic->id]);
        $user2->followedAuthors()->attach($author->id);
        $user3->followedTeams()->attach($team->id);

        $chapter = Chapter::factory()->create([
            'comic_id'          => $comic->id,
            'chapter_number'    => 10,
            'processing_status' => 'ready',
            'published_at'      => now()->subMinute(),
        ]);

        // Chạy Job thông báo
        $job = new SendChapterFollowerNotifications($chapter);
        $job->handle();

        // user1, user2, user3 phải có thông báo trong database
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user1->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user2->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user3->id,
        ]);

        // user4 không nhận thông báo
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $user4->id,
        ]);
    }
}
