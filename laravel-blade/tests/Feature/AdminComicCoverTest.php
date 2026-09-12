<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Tag;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminComicCoverTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'name'     => 'Admin Test',
            'email'    => 'admin-cover@example.com',
        ]);

        $this->genre = Genre::create([
            'name' => 'Hành Động',
            'slug' => 'hanh-dong',
        ]);
    }

    /**
     * 1. Accessor: Local path tạo đúng URL /storage/comics/covers/...
     */
    public function test_cover_url_accessor_handles_local_path(): void
    {
        $comic = Comic::factory()->create([
            'cover_image' => 'comics/covers/eleceed_test.webp',
        ]);

        $this->assertEquals('/storage/comics/covers/eleceed_test.webp', $comic->cover_url);
    }

    /**
     * 2. Accessor: Path đã có /storage/ hoặc storage/ không bị double prefix
     */
    public function test_cover_url_accessor_handles_storage_prefix(): void
    {
        $comicWithSlash = Comic::factory()->create([
            'cover_image' => '/storage/comics/covers/my_cover.jpg',
        ]);
        $this->assertEquals('/storage/comics/covers/my_cover.jpg', $comicWithSlash->cover_url);

        $comicWithoutSlash = Comic::factory()->create([
            'cover_image' => 'storage/comics/covers/my_cover.jpg',
        ]);
        $this->assertEquals('/storage/comics/covers/my_cover.jpg', $comicWithoutSlash->cover_url);
    }

    /**
     * 3. Accessor: External URL và protocol URL giữ nguyên
     */
    public function test_cover_url_accessor_handles_external_urls(): void
    {
        $httpsComic = Comic::factory()->create([
            'cover_image' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401',
        ]);
        $this->assertEquals('https://images.unsplash.com/photo-1534447677768-be436bb09401', $httpsComic->cover_url);

        $httpComic = Comic::factory()->create([
            'cover_image' => 'http://cdn.example.com/comics/cover.png',
        ]);
        $this->assertEquals('http://cdn.example.com/comics/cover.png', $httpComic->cover_url);

        $protocolRelative = Comic::factory()->create([
            'cover_image' => '//cdn.example.com/comics/cover.png',
        ]);
        $this->assertEquals('//cdn.example.com/comics/cover.png', $protocolRelative->cover_url);
    }

    /**
     * 4. Accessor: Null hoặc chuỗi rỗng trả về null
     */
    public function test_cover_url_accessor_handles_null_or_empty(): void
    {
        $nullComic = new Comic(['cover_image' => null]);
        $this->assertNull($nullComic->cover_url);

        $emptyComic = new Comic(['cover_image' => '']);
        $this->assertNull($emptyComic->cover_url);

        $whitespaceComic = new Comic(['cover_image' => '   ']);
        $this->assertNull($whitespaceComic->cover_url);
    }

    /**
     * 5. Admin update comic với cover mới: DB lưu path mới, file tồn tại trên public disk,
     * cover cũ bị xóa an toàn khỏi disk.
     */
    public function test_admin_updates_comic_with_new_cover_and_cleans_up_old_cover(): void
    {
        Storage::fake('public');

        $oldPath = 'comics/covers/old_cover_123.jpg';
        Storage::disk('public')->put($oldPath, 'old fake image content');
        Storage::disk('public')->assertExists($oldPath);

        $comic = Comic::factory()->create([
            'title'       => 'Truyện Cần Đổi Bìa',
            'cover_image' => $oldPath,
            'status'      => 'ongoing',
        ]);
        $comic->genres()->attach($this->genre->id);

        $newImage = UploadedFile::fake()->image('new_cover.png', 500, 700);

        $response = $this->actingAs($this->admin)->put(route('admin.comics.update', $comic->id), [
            'title'       => 'Truyện Cần Đổi Bìa',
            'status'      => 'ongoing',
            'genre_ids'   => [$this->genre->id],
            'cover_image' => $newImage,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.comics.index'));

        $comic->refresh();

        // 1. DB lưu path mới
        $this->assertNotEquals($oldPath, $comic->cover_image);
        $this->assertStringStartsWith('comics/covers/', $comic->cover_image);
        $this->assertStringEndsWith('.png', $comic->cover_image);

        // 2. File mới tồn tại trên public disk
        Storage::disk('public')->assertExists($comic->cover_image);

        // 3. File cũ đã bị xóa khỏi public disk
        Storage::disk('public')->assertMissing($oldPath);

        // 4. Accessor trả về đúng URL /storage/comics/covers/...
        $this->assertEquals('/storage/' . $comic->cover_image, $comic->cover_url);
    }

    /**
     * 6. Cập nhật không có cover mới thì giữ nguyên cover cũ, không bị null/rỗng
     */
    public function test_update_without_new_cover_preserves_existing_cover(): void
    {
        Storage::fake('public');

        $existingPath = 'comics/covers/keep_me.webp';
        Storage::disk('public')->put($existingPath, 'existing image');

        $comic = Comic::factory()->create([
            'title'       => 'Truyện Giữ Bìa',
            'cover_image' => $existingPath,
            'status'      => 'ongoing',
        ]);
        $comic->genres()->attach($this->genre->id);

        $response = $this->actingAs($this->admin)->put(route('admin.comics.update', $comic->id), [
            'title'       => 'Truyện Giữ Bìa Đổi Tên',
            'status'      => 'completed',
            'genre_ids'   => [$this->genre->id],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.comics.index'));

        $comic->refresh();
        $this->assertEquals('Truyện Giữ Bìa Đổi Tên', $comic->title);
        $this->assertEquals($existingPath, $comic->cover_image);
        Storage::disk('public')->assertExists($existingPath);
    }

    /**
     * 7. Cover cũ là URL ngoài thì không cố xóa file disk
     */
    public function test_update_does_not_break_when_old_cover_is_external_url(): void
    {
        Storage::fake('public');

        $externalUrl = 'https://images.unsplash.com/photo-1534447677768-be436bb09401';
        $comic = Comic::factory()->create([
            'title'       => 'Truyện Bìa External',
            'cover_image' => $externalUrl,
            'status'      => 'ongoing',
        ]);
        $comic->genres()->attach($this->genre->id);

        $newImage = UploadedFile::fake()->image('local_cover.webp', 400, 600);

        $response = $this->actingAs($this->admin)->put(route('admin.comics.update', $comic->id), [
            'title'       => 'Truyện Bìa External',
            'status'      => 'ongoing',
            'genre_ids'   => [$this->genre->id],
            'cover_image' => $newImage,
        ]);

        $response->assertSessionHasNoErrors();

        $comic->refresh();
        $this->assertNotEquals($externalUrl, $comic->cover_image);
        $this->assertStringStartsWith('comics/covers/', $comic->cover_image);
        Storage::disk('public')->assertExists($comic->cover_image);
    }

    /**
     * 8. Không xóa cover cũ nếu truyện khác đang dùng chung (shared path)
     */
    public function test_does_not_delete_old_cover_if_shared_by_another_comic(): void
    {
        Storage::fake('public');

        $sharedPath = 'comics/covers/shared_cover.jpg';
        Storage::disk('public')->put($sharedPath, 'shared image data');

        $comicA = Comic::factory()->create([
            'title'       => 'Comic A',
            'cover_image' => $sharedPath,
            'status'      => 'ongoing',
        ]);
        $comicA->genres()->attach($this->genre->id);

        $comicB = Comic::factory()->create([
            'title'       => 'Comic B',
            'cover_image' => $sharedPath,
            'status'      => 'ongoing',
        ]);

        $newImage = UploadedFile::fake()->image('comic_a_new.jpg', 300, 400);

        $response = $this->actingAs($this->admin)->put(route('admin.comics.update', $comicA->id), [
            'title'       => 'Comic A',
            'status'      => 'ongoing',
            'genre_ids'   => [$this->genre->id],
            'cover_image' => $newImage,
        ]);

        $response->assertSessionHasNoErrors();

        // Comic A có ảnh mới
        $comicA->refresh();
        $this->assertNotEquals($sharedPath, $comicA->cover_image);

        // File shared vẫn còn tồn tại vì Comic B vẫn tham chiếu
        Storage::disk('public')->assertExists($sharedPath);
    }

    /**
     * 9. Nếu DB update thất bại: file mới bị dọn dẹp, cover cũ vẫn nguyên vẹn
     */
    public function test_cleans_up_new_cover_and_preserves_old_cover_if_update_fails(): void
    {
        Storage::fake('public');

        $oldPath = 'comics/covers/preserve_old.jpg';
        Storage::disk('public')->put($oldPath, 'old data');

        $comic = Comic::factory()->create([
            'title'       => 'Comic Fail Test',
            'cover_image' => $oldPath,
            'status'      => 'ongoing',
        ]);
        $comic->genres()->attach($this->genre->id);

        // Lắng nghe model event saving để ném ngoại lệ mô phỏng DB failure
        Comic::saving(function ($c) {
            if ($c->title === 'TRIGGER_FAILURE') {
                throw new \RuntimeException('Simulated Database Error');
            }
        });

        $newImage = UploadedFile::fake()->image('new_temp.jpg', 300, 400);

        try {
            $this->actingAs($this->admin)->put(route('admin.comics.update', $comic->id), [
                'title'       => 'TRIGGER_FAILURE',
                'status'      => 'ongoing',
                'genre_ids'   => [$this->genre->id],
                'cover_image' => $newImage,
            ]);
        } catch (\RuntimeException $e) {
            // Expected simulated exception
        }

        // 1. Cover cũ vẫn nguyên vẹn trong DB
        $comic->refresh();
        $this->assertEquals($oldPath, $comic->cover_image);

        // 2. File cũ vẫn tồn tại trên disk
        Storage::disk('public')->assertExists($oldPath);

        // 3. Không có file mồ côi mới được để lại trên disk (ngoại trừ file cũ)
        $files = Storage::disk('public')->files('comics/covers');
        $this->assertCount(1, $files);
        $this->assertEquals($oldPath, $files[0]);
    }

    /**
     * 10. Validation: Reject file không phải ảnh
     */
    public function test_validation_rejects_non_image_file(): void
    {
        Storage::fake('public');

        $comic = Comic::factory()->create([
            'title'       => 'Comic Valid Test',
            'cover_image' => 'comics/covers/safe.jpg',
        ]);
        $comic->genres()->attach($this->genre->id);

        $fakeFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->put(route('admin.comics.update', $comic->id), [
            'title'       => 'Comic Valid Test',
            'status'      => 'ongoing',
            'genre_ids'   => [$this->genre->id],
            'cover_image' => $fakeFile,
        ]);

        $response->assertSessionHasErrors(['cover_image']);
    }

    /**
     * 11. Validation: Reject file vượt quá giới hạn 2MB (2048 KB)
     */
    public function test_validation_rejects_file_exceeding_2mb(): void
    {
        Storage::fake('public');

        $comic = Comic::factory()->create([
            'title'       => 'Comic Max Size Test',
            'cover_image' => 'comics/covers/safe.jpg',
        ]);
        $comic->genres()->attach($this->genre->id);

        // Tạo file 3MB (3072 KB)
        $oversizedImage = UploadedFile::fake()->image('huge.jpg')->size(3072);

        $response = $this->actingAs($this->admin)->put(route('admin.comics.update', $comic->id), [
            'title'       => 'Comic Max Size Test',
            'status'      => 'ongoing',
            'genre_ids'   => [$this->genre->id],
            'cover_image' => $oversizedImage,
        ]);

        $response->assertSessionHasErrors(['cover_image']);
    }

    /**
     * 12. View rendering: Trang Edit và Show hiển thị đúng cover_url
     */
    public function test_views_render_valid_cover_url(): void
    {
        $comic = Comic::factory()->create([
            'title'       => 'Truyện Render Test',
            'slug'        => 'truyen-render-test',
            'cover_image' => 'comics/covers/rendered.webp',
            'status'      => 'ongoing',
        ]);
        $comic->genres()->attach($this->genre->id);

        // Trang Admin Edit
        $editResponse = $this->actingAs($this->admin)->get(route('admin.comics.edit', $comic->id));
        $editResponse->assertOk();
        $editResponse->assertSee('/storage/comics/covers/rendered.webp');
        $editResponse->assertDontSee('src="comics/covers/rendered.webp"');

        // Trang Public Show
        $showResponse = $this->get(route('comics.show', $comic->slug));
        $showResponse->assertOk();
        $showResponse->assertSee('/storage/comics/covers/rendered.webp');
        $showResponse->assertDontSee('src="comics/covers/rendered.webp"');
    }
}
