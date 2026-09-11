<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminComicFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'is_admin' => true,
            'name' => 'Admin Boss',
        ]);
    }

    public function test_admin_can_view_comic_filter_bar(): void
    {
        $genre = Genre::create(['name' => 'Hành Động', 'slug' => 'hanh-dong']);
        $author = Author::factory()->create(['name' => 'Nguyễn Nhật Ánh']);

        $response = $this->actingAs($this->admin)->get(route('admin.comics.index'));
        $response->assertOk();

        // Kiểm tra sự xuất hiện của các trường lọc
        $response->assertSee('Tên hoặc slug truyện...');
        $response->assertSee('— Tất cả thể loại —');
        $response->assertSee('Hành Động');
        $response->assertSee('— Tất cả tác giả —');
        $response->assertSee('Nguyễn Nhật Ánh');
        $response->assertSee('Đang ra');
        $response->assertSee('Hoàn thành');
        $response->assertSee('Lượt xem cao');
        $response->assertSee('Nhiều chapter');
    }

    public function test_filter_by_search_keyword(): void
    {
        $comic1 = Comic::factory()->create(['title' => 'Đại Quản Gia Ma Hoàng', 'slug' => 'dai-quan-gia-ma-hoang']);
        $comic2 = Comic::factory()->create(['title' => 'Trọng Sinh Đô Thị Tu Tiên', 'slug' => 'trong-sinh-do-thi-tu-tien']);

        $response = $this->actingAs($this->admin)->get(route('admin.comics.index', ['q' => 'Ma Hoàng']));
        $response->assertOk();

        $filteredComics = $response->viewData('comics');
        $this->assertTrue($filteredComics->contains('id', $comic1->id));
        $this->assertFalse($filteredComics->contains('id', $comic2->id));
    }

    public function test_filter_by_genre(): void
    {
        $genreAction = Genre::create(['name' => 'Hành Động', 'slug' => 'hanh-dong']);
        $genreRomance = Genre::create(['name' => 'Lãng Mạn', 'slug' => 'lang-man']);

        $comicAction = Comic::factory()->create(['title' => 'Truyện Đấm Bốc']);
        $comicAction->genres()->attach($genreAction->id);

        $comicRomance = Comic::factory()->create(['title' => 'Truyện Tình Yêu']);
        $comicRomance->genres()->attach($genreRomance->id);

        $response = $this->actingAs($this->admin)->get(route('admin.comics.index', ['genre_id' => $genreAction->id]));
        $response->assertOk();

        $filteredComics = $response->viewData('comics');
        $this->assertTrue($filteredComics->contains('id', $comicAction->id));
        $this->assertFalse($filteredComics->contains('id', $comicRomance->id));
    }

    public function test_filter_by_author(): void
    {
        $author1 = Author::factory()->create(['name' => 'Tác Giả A']);
        $author2 = Author::factory()->create(['name' => 'Tác Giả B']);

        $comic1 = Comic::factory()->create(['title' => 'Tác Phẩm Của A']);
        $comic1->authors()->attach($author1->id);

        $comic2 = Comic::factory()->create(['title' => 'Tác Phẩm Của B']);
        $comic2->authors()->attach($author2->id);

        $response = $this->actingAs($this->admin)->get(route('admin.comics.index', ['author_id' => $author1->id]));
        $response->assertOk();

        $filteredComics = $response->viewData('comics');
        $this->assertTrue($filteredComics->contains('id', $comic1->id));
        $this->assertFalse($filteredComics->contains('id', $comic2->id));
    }

    public function test_filter_by_status(): void
    {
        $comicOngoing = Comic::factory()->create(['title' => 'Truyện Đang Ra', 'status' => 'ongoing']);
        $comicCompleted = Comic::factory()->create(['title' => 'Truyện Đã Xong', 'status' => 'completed']);

        $response = $this->actingAs($this->admin)->get(route('admin.comics.index', ['status' => 'completed']));
        $response->assertOk();

        $filteredComics = $response->viewData('comics');
        $this->assertTrue($filteredComics->contains('id', $comicCompleted->id));
        $this->assertFalse($filteredComics->contains('id', $comicOngoing->id));
    }

    public function test_sort_by_views(): void
    {
        $lowComic = Comic::factory()->create(['title' => 'Truyện Ít View', 'views' => 10]);
        $highComic = Comic::factory()->create(['title' => 'Truyện Siêu View', 'views' => 999999]);

        $response = $this->actingAs($this->admin)->get(route('admin.comics.index', ['sort' => 'views']));
        $response->assertOk();

        $filteredComics = $response->viewData('comics');
        $this->assertEquals($highComic->id, $filteredComics->first()->id);
    }

    public function test_empty_filter_shows_friendly_message_and_reset_button(): void
    {
        Comic::factory()->create(['title' => 'Truyện Bình Thường']);

        $response = $this->actingAs($this->admin)->get(route('admin.comics.index', ['q' => 'Từ Khóa Không Tồn Tại 12345']));
        $response->assertOk();
        $response->assertSee('Không tìm thấy bộ truyện nào phù hợp.');
        $response->assertSee('Xóa bộ lọc');
    }

    public function test_searchable_dropdowns_rendered_for_genre_and_author(): void
    {
        $genre = Genre::create(['name' => 'Võ Thuật', 'slug' => 'vo-thuat']);
        $author = Author::factory()->create(['name' => 'Kim Dung']);

        $response = $this->actingAs($this->admin)->get(route('admin.comics.index'));
        $response->assertOk();

        // Kiểm tra component dropdown tìm kiếm thể loại
        $response->assertSee('genre-searchable-dropdown');
        $response->assertSee('Tìm thể loại...');
        $response->assertSee('Võ Thuật');

        // Kiểm tra component dropdown tìm kiếm tác giả
        $response->assertSee('author-searchable-dropdown');
        $response->assertSee('Tìm tác giả...');
        $response->assertSee('Kim Dung');
    }
}
