<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreChapterRequest;
use App\Http\Requests\Admin\UpdateChapterRequest;
use App\Models\Comic;
use App\Models\Chapter;
use App\Services\ChapterService;
use App\Services\ImageService;

class AdminChapterController extends Controller
{
    public function __construct(
        protected ChapterService $chapterService,
        protected ImageService   $imageService,
    ) {}

    /**
     * Danh sách tất cả các chapter của một bộ truyện.
     */
    public function index(Comic $comic)
    {
        $chapters = $comic->chapters()
            ->orderBy('chapter_number', 'desc')
            ->paginate(20);

        return view('admin.chapters.index', compact('comic', 'chapters'));
    }

    /**
     * Giao diện đăng tải chương mới.
     */
    public function create(Comic $comic)
    {
        $nextChapterNumber = ($comic->chapters()->max('chapter_number') ?? 0) + 1;
        return view('admin.chapters.create', compact('comic', 'nextChapterNumber'));
    }

    /**
     * Lưu chương mới với Bulk Upload ảnh.
     * Validation đã được xử lý bởi StoreChapterRequest.
     */
    public function store(StoreChapterRequest $request, Comic $comic)
    {
        // Phải có ít nhất 1 ảnh upload hoặc 1 URL
        if (!$request->hasContent()) {
            return back()->withInput()->withErrors([
                'images' => 'Bạn phải chọn ít nhất 1 file ảnh hoặc dán danh sách đường dẫn URL ảnh.',
            ]);
        }

        $folder = $this->imageService->chapterFolder($comic->id, 0); // temp folder — sẽ đổi sau khi có ID
        $pages  = [];

        // Xử lý file upload (cần chapter ID nên tạo chapter trước, sau đó cập nhật pages)
        // Tạo chapter với pages = [] trước để lấy ID
        $chapter = $this->chapterService->createWithPages($comic, [
            'chapter_number' => $request->chapter_number,
            'title'          => $request->title,
            'is_free'        => $request->boolean('is_free', true),
        ], []);

        // Upload ảnh vào thư mục theo chapter ID thực
        $folder = $this->imageService->chapterFolder($comic->id, $chapter->id);

        if ($request->hasFile('images')) {
            $pages = $this->imageService->uploadBulk(
                $request->file('images'),
                $folder,
                $request->input('image_order')
            );
        }

        // Merge URL nếu có
        if (!empty(trim($request->input('pages_raw', '')))) {
            $pages = array_merge($pages, $this->imageService->parseUrlList($request->pages_raw));
        }

        // Cập nhật pages vào chapter vừa tạo
        $chapter->update(['pages' => $pages]);

        return redirect()
            ->route('admin.comics.chapters.index', $comic->id)
            ->with('success', "Đăng thành công Chapter {$chapter->chapter_number} với " . count($pages) . " trang ảnh!");
    }

    /**
     * Giao diện chỉnh sửa chương.
     */
    public function edit(Comic $comic, Chapter $chapter)
    {
        return view('admin.chapters.edit', compact('comic', 'chapter'));
    }

    /**
     * Cập nhật chương (ảnh cũ, ảnh mới, thứ tự trang).
     * Validation đã được xử lý bởi UpdateChapterRequest.
     */
    public function update(UpdateChapterRequest $request, Comic $comic, Chapter $chapter)
    {
        // 1. Xóa các file ảnh bị đánh dấu xóa
        $removedPages = $request->input('removed_pages', []);
        if (!empty($removedPages)) {
            $this->imageService->deleteFiles((array) $removedPages);
        }

        // 2. Bắt đầu với danh sách ảnh còn giữ lại
        $finalPages = array_values((array) $request->input('existing_pages', []));

        // 3. Upload ảnh mới
        if ($request->hasFile('new_images')) {
            $folder    = $this->imageService->chapterFolder($comic->id, $chapter->id);
            $newPaths  = $this->imageService->uploadBulk(
                $request->file('new_images'),
                $folder,
                null,
            );
            // Offset page number ảnh mới theo số ảnh hiện tại
            $finalPages = array_merge($finalPages, $newPaths);
        }

        // 4. Thêm URL từ textarea (nếu có)
        if (!empty(trim($request->input('add_urls', '')))) {
            $finalPages = array_merge($finalPages, $this->imageService->parseUrlList($request->add_urls));
        }

        $this->chapterService->updateWithPages($chapter, [
            'chapter_number' => $request->chapter_number,
            'title'          => $request->title,
            'is_free'        => $request->boolean('is_free', true),
        ], $finalPages);

        return redirect()
            ->route('admin.comics.chapters.index', $comic->id)
            ->with('success', "Cập nhật Chapter {$request->chapter_number} thành công!");
    }

    /**
     * Xóa chương và toàn bộ thư mục ảnh.
     */
    public function destroy(Comic $comic, Chapter $chapter)
    {
        $chapterNumber = $chapter->chapter_number;
        $this->chapterService->delete($comic, $chapter);

        return redirect()
            ->route('admin.comics.chapters.index', $comic->id)
            ->with('success', "Đã xóa Chapter {$chapterNumber} và toàn bộ ảnh thuộc chương!");
    }
}
