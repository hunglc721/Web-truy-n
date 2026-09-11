<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminComicCrudViewsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'name'     => 'Admin Test',
            'email'    => 'admintest@example.com',
        ]);

        $this->regularUser = User::factory()->create([
            'is_admin' => false,
            'name'     => 'Regular User',
            'email'    => 'user@example.com',
        ]);
    }

    public function test_admin_can_access_comic_create_page(): void
    {
        $genre = Genre::create(['name' => 'Hành Động', 'slug' => 'hanh-dong']);
        $tag   = Tag::create(['name' => 'Huyền Huyễn', 'slug' => 'huyen-huyen']);

        $response = $this->actingAs($this->admin)->get(route('admin.comics.create'));

        $response->assertOk();
        $response->assertViewIs('admin.comics.create');
        $response->assertSee('Đăng Bộ Truyện Mới');
        $response->assertSee('Hành Động');
        $response->assertSee('Huyền Huyễn');
        $response->assertSee(route('admin.comics.store'));
    }

    public function test_admin_can_store_a_comic_without_cover(): void
    {
        $genre = Genre::create(['name' => 'Tu Tiên', 'slug' => 'tu-tien']);
        $author = Author::factory()->create(['name' => 'Lão Trư']);

        $payload = [
            'title'       => 'Bộ Truyện Mới Nhất',
            'slug'        => 'bo-truyen-moi-nhat',
            'description' => 'Mô tả bộ truyện mới nhất vừa thêm.',
            'status'      => 'ongoing',
            'genre_ids'   => [$genre->id],
            'author_ids'  => [$author->id],
            'is_featured' => 1,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.comics.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.comics.index'));

        $this->assertDatabaseHas('comics', [
            'title' => 'Bộ Truyện Mới Nhất',
            'slug'  => 'bo-truyen-moi-nhat',
        ]);

        $comic = Comic::where('title', 'Bộ Truyện Mới Nhất')->first();
        $this->assertNotNull($comic);
        $this->assertTrue($comic->genres->contains('id', $genre->id));
        $this->assertTrue($comic->authors->contains('id', $author->id));
    }

    public function test_admin_can_store_a_comic_with_cover_image(): void
    {
        Storage::fake('public');

        $genre = Genre::create(['name' => 'Võ Thuật', 'slug' => 'vo-thuat']);
        $file = UploadedFile::fake()->image('cover.jpg', 600, 800);

        $payload = [
            'title'       => 'Truyện Có Ảnh Bìa',
            'status'      => 'ongoing',
            'genre_ids'   => [$genre->id],
            'cover_image' => $file,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.comics.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.comics.index'));

        $comic = Comic::where('title', 'Truyện Có Ảnh Bìa')->first();
        $this->assertNotNull($comic);
        $this->assertNotEmpty($comic->cover_image);
        Storage::disk('public')->assertExists($comic->cover_image);
    }

    public function test_admin_can_access_comic_edit_page(): void
    {
        $genre = Genre::create(['name' => 'Trọng Sinh', 'slug' => 'trong-sinh']);
        $comic = Comic::factory()->create([
            'title' => 'Trọng Sinh Về Thời Đại Mới',
            'slug'  => 'trong-sinh-ve-thoi-dai-moi',
        ]);
        $comic->genres()->attach($genre->id);

        $response = $this->actingAs($this->admin)->get(route('admin.comics.edit', $comic->id));

        $response->assertOk();
        $response->assertViewIs('admin.comics.edit');
        $response->assertSee('Chỉnh Sửa Bộ Truyện');
        $response->assertSee('Trọng Sinh Về Thời Đại Mới');
        $response->assertSee('Trọng Sinh');
        $response->assertSee(route('admin.comics.update', $comic->id));
    }

    public function test_admin_can_update_a_comic(): void
    {
        $genre1 = Genre::create(['name' => 'Kiếm Hiệp', 'slug' => 'kiem-hiep']);
        $genre2 = Genre::create(['name' => 'Khoa Huyễn', 'slug' => 'khoa-huyen']);
        $comic = Comic::factory()->create([
            'title'  => 'Tên Ban Đầu',
            'status' => 'ongoing',
        ]);
        $comic->genres()->attach($genre1->id);

        $payload = [
            'title'       => 'Tên Sau Khi Sửa',
            'slug'        => 'ten-sau-khi-sua',
            'description' => 'Mô tả sau khi cập nhật.',
            'status'      => 'completed',
            'genre_ids'   => [$genre2->id],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.comics.update', $comic->id), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('comics', [
            'id'     => $comic->id,
            'title'  => 'Tên Sau Khi Sửa',
            'status' => 'completed',
        ]);

        $comic->refresh();
        $this->assertTrue($comic->genres->contains('id', $genre2->id));
        $this->assertFalse($comic->genres->contains('id', $genre1->id));
    }

    public function test_regular_user_cannot_access_create_or_edit(): void
    {
        $comic = Comic::factory()->create();

        $responseCreate = $this->actingAs($this->regularUser)->get(route('admin.comics.create'));
        $responseCreate->assertRedirect('/');

        $responseEdit = $this->actingAs($this->regularUser)->get(route('admin.comics.edit', $comic->id));
        $responseEdit->assertRedirect('/');
    }
}
