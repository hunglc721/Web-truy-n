<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Comic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChapterService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Tạo Chapter mới trong DB và gán danh sách trang ảnh.
     * Bọc trong transaction — nếu upload lỗi thì rollback toàn bộ.
     *
     * @param  Comic   $comic
     * @param  array   $data    chapter_number, title, is_free
     * @param  array   $pages   Mảng đường dẫn ảnh đã xử lý
     * @return Chapter
     */
    public function createWithPages(Comic $comic, array $data, array $pages): Chapter
    {
        return DB::transaction(function () use ($comic, $data, $pages) {
            $chapterNumber = $data['chapter_number'];

            $chapter = Chapter::create([
                'comic_id'       => $comic->id,
                'chapter_number' => $chapterNumber,
                'title'          => $data['title'] ?: 'Chapter ' . $chapterNumber,
                'slug'           => $this->generateSlug($comic->id, $chapterNumber),
                'pages'          => $pages,
                'views'          => 0,
                'published_at'   => now(),
                'is_free'        => $data['is_free'] ?? true,
            ]);

            return $chapter;
        });
    }

    /**
     * Cập nhật Chapter (thông tin + danh sách trang ảnh cuối cùng).
     *
     * @param  Chapter $chapter
     * @param  array   $data       chapter_number, title, is_free
     * @param  array   $finalPages Mảng đường dẫn ảnh đã merge (existing + new)
     * @return Chapter
     */
    public function updateWithPages(Chapter $chapter, array $data, array $finalPages): Chapter
    {
        return DB::transaction(function () use ($chapter, $data, $finalPages) {
            $chapterNumber = $data['chapter_number'];

            $chapter->update([
                'chapter_number' => $chapterNumber,
                'title'          => $data['title'] ?: 'Chapter ' . $chapterNumber,
                'slug'           => $this->generateSlug($chapter->comic_id, $chapterNumber, $chapter->id),
                'pages'          => array_values($finalPages),
                'is_free'        => $data['is_free'] ?? true,
            ]);

            return $chapter;
        });
    }

    /**
     * Xóa Chapter: xóa thư mục ảnh trên disk rồi forceDelete bản ghi.
     *
     * @param  Comic   $comic
     * @param  Chapter $chapter
     */
    public function delete(Comic $comic, Chapter $chapter): void
    {
        $folder = $this->imageService->chapterFolder($comic->id, $chapter->id);
        $this->imageService->deleteFolder($folder);
        $chapter->forceDelete();
    }

    /**
     * Tạo slug chuẩn cho chapter, đảm bảo unique trong phạm vi comic.
     * Nếu trùng, thêm suffix -v2, -v3, ...
     *
     * @param  int        $comicId
     * @param  int|float  $chapterNumber
     * @param  int|null   $excludeId     ID chapter hiện tại khi update (để bỏ qua chính nó)
     * @return string
     */
    public function generateSlug(int $comicId, int|float $chapterNumber, ?int $excludeId = null): string
    {
        $base = 'chapter-' . Str::slug((string) $chapterNumber);
        $slug = $base;
        $suffix = 1;

        while ($this->slugExists($comicId, $slug, $excludeId)) {
            $suffix++;
            $slug = $base . '-v' . $suffix;
        }

        return $slug;
    }

    /**
     * Kiểm tra slug đã tồn tại trong phạm vi comic chưa.
     */
    protected function slugExists(int $comicId, string $slug, ?int $excludeId): bool
    {
        return Chapter::withTrashed()
            ->where('comic_id', $comicId)
            ->where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
