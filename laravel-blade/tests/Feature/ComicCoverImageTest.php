<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComicCoverImageTest extends TestCase
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
            'email'    => 'admin_cover_test@example.com',
        ]);

        $this->genre = Genre::create([
            'name' => 'Hành Động',
            'slug' => 'hanh-dong',
        ]);
    }

    /**
     * Test 1: Admin upload cover mới:
     * - request thành công
     * - DB lưu relative path
     * - Storage public có file
     * - cover_url trả /storage/...
     * - trang admin render URL đúng.
     */
    public function test_admin_upload_cover_success_stores_relative_path_and_renders_storage_url(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('my_cover.jpg', 600, 800);

        $response = $this->actingAs($this->admin)->post(route('admin.comics.store'), [
            'title'       => 'Truyện Test Bìa Mới',
            'status'      => 'ongoing',
            'genre_ids'   => [$this->genre->id],
            'cover_image' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.comics.index'));

        $comic = Comic::where('title', 'Truyện Test Bìa Mới')->firstOrFail();

        // 1. DB lưu relative path bắt đầu bằng comics/covers/
        $this->assertStringStartsWith('comics/covers/', $comic->cover_image);

        // 2. Storage public có file
        Storage::disk('public')->assertExists($comic->cover_image);

        // 3. cover_url trả URL chứa /storage/...
        $this->assertNotNull($comic->cover_url);
        $this->assertStringContainsString('/storage/' . $comic->cover_image, $comic->cover_url);

        // 4. Trang danh sách admin render URL đúng
        $indexResponse = $this->actingAs($this->admin)->get(route('admin.comics.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee($comic->cover_url, false);

        // 5. Trang edit admin render URL đúng
        $editResponse = $this->actingAs($this->admin)->get(route('admin.comics.edit', $comic->id));
        $editResponse->assertOk();
        $editResponse->assertSee($comic->cover_url, false);
    }

    /**
     * Test 2: External cover:
     * DB: https://example.com/cover.jpg
     * => $comic->cover_url vẫn đúng URL external.
     */
    public function test_external_cover_url_is_preserved(): void
    {
        $comicHttps = Comic::factory()->create([
            'cover_image' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600',
        ]);
        $this->assertEquals(
            'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600',
            $comicHttps->cover_url
        );

        $comicHttp = Comic::factory()->create([
            'cover_image' => 'http://example.com/static/cover.png',
        ]);
        $this->assertEquals('http://example.com/static/cover.png', $comicHttp->cover_url);
    }

    /**
     * Test 3: Existing /storage/...:
     * => không thành /storage/storage/...
     */
    public function test_existing_storage_path_does_not_duplicate_storage_prefix(): void
    {
        $comicWithSlash = Comic::factory()->create([
            'cover_image' => '/storage/comics/covers/existing_cover.jpg',
        ]);
        $this->assertEquals('/storage/comics/covers/existing_cover.jpg', $comicWithSlash->cover_url);
        $this->assertStringNotContainsString('/storage/storage/', $comicWithSlash->cover_url);

        $comicWithoutLeadingSlash = Comic::factory()->create([
            'cover_image' => 'storage/comics/covers/existing_cover.jpg',
        ]);
        $this->assertEquals('/storage/comics/covers/existing_cover.jpg', $comicWithoutLeadingSlash->cover_url);
        $this->assertStringNotContainsString('/storage/storage/', $comicWithoutLeadingSlash->cover_url);
    }

    /**
     * Test 4: Empty / null:
     * => không tạo broken URL.
     */
    public function test_empty_or_null_cover_returns_null(): void
    {
        $comicEmpty = Comic::factory()->create(['cover_image' => '']);
        $this->assertNull($comicEmpty->cover_url);

        $comicNull = Comic::factory()->create(['cover_image' => '   ']);
        $this->assertNull($comicNull->cover_url);
    }

    /**
     * Test 5: Update cover:
     * - file mới tồn tại
     * - DB trỏ file mới
     * - file local cũ được xóa sau update thành công.
     */
    public function test_update_cover_stores_new_file_and_deletes_old_local_cover(): void
    {
        Storage::fake('public');

        // Tạo ảnh cũ trên public disk
        $oldPath = 'comics/covers/old_file_123456.jpg';
        Storage::disk('public')->put($oldPath, 'fake-old-image-bytes');

        $comic = Comic::factory()->create([
            'title'       => 'Truyện Sắp Đổi Bìa',
            'cover_image' => $oldPath,
        ]);
        $comic->genres()->attach($this->genre->id);

        $newFile = UploadedFile::fake()->image('new_cover_updated.png', 800, 1200);

        $response = $this->actingAs($this->admin)->put(route('admin.comics.update', $comic->id), [
            'title'       => 'Truyện Đã Đổi Bìa',
            'status'      => 'ongoing',
            'cover_image' => $newFile,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.comics.index'));

        $comic->refresh();

        // File mới được lưu và khác file cũ
        $this->assertNotEquals($oldPath, $comic->cover_image);
        Storage::disk('public')->assertExists($comic->cover_image);

        // File local cũ đã được xóa
        Storage::disk('public')->assertMissing($oldPath);
    }

    /**
     * Test 6: Không upload ảnh mới:
     * - cover cũ không đổi
     * - file cũ không bị xóa.
     */
    public function test_update_without_new_cover_keeps_existing_cover_and_file(): void
    {
        Storage::fake('public');

        $oldPath = 'comics/covers/keep_me.jpg';
        Storage::disk('public')->put($oldPath, 'fake-image-bytes');

        $comic = Comic::factory()->create([
            'title'       => 'Truyện Giữ Bìa',
            'cover_image' => $oldPath,
        ]);
        $comic->genres()->attach($this->genre->id);

        $response = $this->actingAs($this->admin)->put(route('admin.comics.update', $comic->id), [
            'title'  => 'Truyện Giữ Bìa (Đã Sửa Title)',
            'status' => 'completed',
        ]);

        $response->assertSessionHasNoErrors();
        $comic->refresh();

        $this->assertEquals($oldPath, $comic->cover_image);
        Storage::disk('public')->assertExists($oldPath);
    }

    /**
     * Test 7: External old cover:
     * - update bằng local cover mới
     * - tuyệt đối không cố Storage::delete() URL external.
     */
    public function test_updating_external_cover_does_not_crash_or_delete_external_url(): void
    {
        Storage::fake('public');

        $externalUrl = 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600';
        $comic = Comic::factory()->create([
            'title'       => 'Truyện Dùng Bìa External',
            'cover_image' => $externalUrl,
        ]);
        $comic->genres()->attach($this->genre->id);

        $newFile = UploadedFile::fake()->image('local_replacement.jpg', 600, 800);

        $response = $this->actingAs($this->admin)->put(route('admin.comics.update', $comic->id), [
            'cover_image' => $newFile,
        ]);

        $response->assertSessionHasNoErrors();
        $comic->refresh();

        $this->assertNotEquals($externalUrl, $comic->cover_image);
        $this->assertStringStartsWith('comics/covers/', $comic->cover_image);
        Storage::disk('public')->assertExists($comic->cover_image);
    }

    /**
     * Test 8: Invalid fake image:
     * - reject validation.
     */
    public function test_fake_image_file_is_rejected_by_validation(): void
    {
        Storage::fake('public');

        // Tạo file văn bản giả danh đuôi .jpg
        $fakeFile = UploadedFile::fake()->create('hacker.jpg', 15, 'text/plain');

        $response = $this->actingAs($this->admin)->post(route('admin.comics.store'), [
            'title'       => 'Truyện File Giả',
            'status'      => 'ongoing',
            'genre_ids'   => [$this->genre->id],
            'cover_image' => $fakeFile,
        ]);

        $response->assertSessionHasErrors('cover_image');
        $this->assertDatabaseMissing('comics', ['title' => 'Truyện File Giả']);
    }

    /**
     * Test 9: AVIF hợp lệ:
     * - được chấp nhận và lưu trữ đúng định dạng.
     */
    public function test_valid_avif_image_is_accepted_and_uploaded(): void
    {
        Storage::fake('public');

        // Tạo header ftyp hợp lệ chuẩn ISOBMFF của file AVIF
        $avifBytes = pack('N', 28) . 'ftyp' . 'avif' . pack('N', 0) . 'avif' . 'mif1' . str_repeat("\x00", 100);
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_avif_');
        file_put_contents($tmpPath, $avifBytes);

        $avifFile = new UploadedFile($tmpPath, 'cover.avif', 'image/avif', null, true);

        $response = $this->actingAs($this->admin)->post(route('admin.comics.store'), [
            'title'       => 'Truyện Bìa AVIF Chuẩn',
            'status'      => 'ongoing',
            'genre_ids'   => [$this->genre->id],
            'cover_image' => $avifFile,
        ]);

        @unlink($tmpPath);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.comics.index'));

        $comic = Comic::where('title', 'Truyện Bìa AVIF Chuẩn')->firstOrFail();
        $this->assertStringEndsWith('.avif', $comic->cover_image);
        Storage::disk('public')->assertExists($comic->cover_image);
        $this->assertStringContainsString('/storage/' . $comic->cover_image, $comic->cover_url);
    }

    /**
     * Test 10: Nếu update DB fail sau khi upload ảnh mới:
     * - xóa file mới vừa upload để tránh orphan file
     * - giữ cover cũ.
     */
    public function test_update_failure_cleans_up_newly_uploaded_file_avoiding_orphan(): void
    {
        Storage::fake('public');

        $oldPath = 'comics/covers/original_cover.jpg';
        Storage::disk('public')->put($oldPath, 'original-bytes');

        $comic = Comic::factory()->create([
            'title'       => 'Truyện Ban Đầu',
            'cover_image' => $oldPath,
        ]);

        $newFile = UploadedFile::fake()->image('will_fail.png', 500, 500);

        // Giả lập lỗi DB khi saving comic bằng cách lắng nghe event saving và ném exception
        Comic::saving(function ($model) {
            if ($model->title === 'Tên Gây Lỗi') {
                throw new \RuntimeException('Mô phỏng lỗi DB transaction');
            }
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($this->admin)->put(route('admin.comics.update', $comic->id), [
                'title'       => 'Tên Gây Lỗi',
                'cover_image' => $newFile,
            ]);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Mô phỏng lỗi DB transaction', $e->getMessage());
        }

        $comic->refresh();

        // Cover cũ vẫn được bảo tồn
        $this->assertEquals($oldPath, $comic->cover_image);
        Storage::disk('public')->assertExists($oldPath);

        // Không có orphan file mới nào trong comics/covers ngoại trừ $oldPath
        $allFiles = Storage::disk('public')->allFiles('comics/covers');
        $this->assertEquals([$oldPath], $allFiles);
    }
}

