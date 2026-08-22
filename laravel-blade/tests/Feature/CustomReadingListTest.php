<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\ReadingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomReadingListTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_custom_reading_list(): void
    {
        $user = User::factory()->create();
        $comic1 = Comic::factory()->create(['title' => 'Comic 1']);
        $comic2 = Comic::factory()->create(['title' => 'Comic 2']);

        $response = $this->actingAs($user)->postJson(route('api.lists.store'), [
            'title'       => 'Top Truyện Tu Tiên Đỉnh Cao',
            'description' => 'Tuyển tập tu tiên hay nhất mọi thời đại.',
            'is_public'   => true,
            'comic_ids'   => [$comic1->id, $comic2->id],
        ]);

        $response->assertCreated()
            ->assertJson([
                'status'  => 'success',
                'message' => 'Đã tạo danh sách truyện thành công!',
            ]);

        $this->assertDatabaseHas('reading_lists', [
            'user_id' => $user->id,
            'title'   => 'Top Truyện Tu Tiên Đỉnh Cao',
        ]);

        $list = ReadingList::where('user_id', $user->id)->first();
        $this->assertCount(2, $list->comics);
    }

    public function test_user_can_browse_and_view_reading_list(): void
    {
        $user = User::factory()->create(['name' => 'Reviewer X']);
        $list = ReadingList::create([
            'user_id'     => $user->id,
            'title'       => 'Manga Hành Động Cực Cuốn',
            'slug'        => 'manga-hanh-dong-cuc-cuon',
            'description' => 'Không thể bỏ lỡ.',
            'is_public'   => true,
        ]);

        $comic = Comic::factory()->create(['title' => 'One Punch Man']);
        $list->comics()->attach($comic->id);

        $indexRes = $this->get(route('lists.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('Manga Hành Động Cực Cuốn');

        $showRes = $this->get(route('lists.show', 'manga-hanh-dong-cuc-cuon'));
        $showRes->assertOk();
        $showRes->assertSee('Manga Hành Động Cực Cuốn');
        $showRes->assertSee('One Punch Man');
    }

    public function test_user_can_like_and_unlike_reading_list(): void
    {
        $author = User::factory()->create();
        $liker = User::factory()->create();

        $list = ReadingList::create([
            'user_id'   => $author->id,
            'title'     => 'Top Isekai Hay',
            'is_public' => true,
        ]);

        // Like
        $response = $this->actingAs($liker)->postJson(route('api.lists.toggleLike', $list->id));
        $response->assertOk()
            ->assertJson([
                'status'      => 'success',
                'action'      => 'liked',
                'is_liked'    => true,
                'likes_count' => 1,
            ]);

        $this->assertTrue($list->isLikedBy($liker));

        // Unlike
        $response = $this->actingAs($liker)->postJson(route('api.lists.toggleLike', $list->id));
        $response->assertOk()
            ->assertJson([
                'status'      => 'success',
                'action'      => 'unliked',
                'is_liked'    => false,
                'likes_count' => 0,
            ]);

        $this->assertFalse($list->isLikedBy($liker));
    }
}
