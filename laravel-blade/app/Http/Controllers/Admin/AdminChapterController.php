<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreChapterRequest;
use App\Http\Requests\Admin\UpdateChapterRequest;
use App\Jobs\ProcessChapterImages;
use App\Models\Comic;
use App\Models\Chapter;
use App\Services\ChapterService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminChapterController extends Controller
{
    public function __construct(
        protected ChapterService $chapterService,
        protected ImageService   $imageService,
    ) {}

    /**
     * Quản lý toàn bộ danh sách Chapter trên toàn hệ thống.
     */
    public function all(Request $request)
    {
        $query = Chapter::with('comic')
            ->latest('id');

        if ($request->filled('comic_id')) {
            $query->where('comic_id', $request->comic_id);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('chapter_number', 'like', "%{$q}%")
                    ->orWhereHas('comic', fn ($c) => $c->where('title', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('is_free') && $request->is_free !== 'all') {
            $query->where('is_free', $request->boolean('is_free'));
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('processing_status', $request->status);
        }

        $chapters = $query->paginate(20)->withQueryString();
        $comics = Comic::orderBy('title')->get(['id', 'title', 'slug']);

        $stats = [
            'total'   => Chapter::count(),
            'free'    => Chapter::where('is_free', true)->count(),
            'premium' => Chapter::where('is_free', false)->count(),
            'ready'   => Chapter::where('processing_status', 'ready')->count(),
            'pending' => Chapter::where('processing_status', 'pending')->count(),
        ];

        return view('admin.chapters.all', compact('chapters', 'comics', 'stats'));
    }

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
     * Lưu chương mới — QUEUE MODE cho bulk upload ảnh.
     *
     * Flow:
     *  1. Validate bằng StoreChapterRequest
     *  2. Tạo chapter với processing_status='pending'
     *  3. Lưu ảnh vào thư mục tmp/ trước
     *  4. Dispatch ProcessChapterImages job → worker xử lý bất đồng bộ
     *  5. Redirect về admin ngay lập tức (không đợi upload xong)
     *
     * Lợi ích: Admin không bị block khi upload 100+ ảnh nặng.
     * URL list (pages_raw) vẫn xử lý đồng bộ vì không tốn I/O.
     */
    public function store(StoreChapterRequest $request, Comic $comic)
    {
        // Phải có ít nhất 1 ảnh upload hoặc 1 URL
        if (!$request->hasContent()) {
            return back()->withInput()->withErrors([
                'images' => 'Bạn phải chọn ít nhất 1 file ảnh hoặc dán danh sách đường dẫn URL ảnh.',
            ]);
        }

        // ── Tạo chapter với pages = [] trước để lấy ID ──────────────────────
        $chapter = $this->chapterService->createWithPages($comic, [
            'chapter_number' => $request->chapter_number,
            'title'          => $request->title,
            'is_free'        => $request->boolean('is_free', true),
        ], []);

        $tmpPaths = [];
        $urlList  = [];

        // ── Lưu ảnh upload vào thư mục tmp/ trên disk ───────────────────────
        if ($request->hasFile('images')) {
            $tmpFolder = "tmp/comics/{$comic->id}/chapters/{$chapter->id}";
            foreach ($request->file('images') as $idx => $file) {
                $tmpPaths[] = $this->imageService->uploadSingle($file, $tmpFolder, $idx);
            }
        }

        // ── Parse URL list ──────────────────────────────────────────────────
        if (!empty(trim($request->input('pages_raw', '')))) {
            $urlList = $this->imageService->parseUrlList($request->pages_raw);
        }

        // ── Nếu chỉ có URL list → xử lý đồng bộ (nhanh, không cần queue) ──
        if (empty($tmpPaths) && !empty($urlList)) {
            $chapter->update([
                'pages'             => $urlList,
                'processing_status' => 'ready',
            ]);

            return redirect()
                ->route('admin.comics.chapters.index', $comic->id)
                ->with('success', "Đăng thành công Chapter {$chapter->chapter_number} với " . count($urlList) . " trang URL!");
        }

        // ── Dispatch Queue job — worker sẽ move tmp → final, merge URLs ─────
        $chapter->update(['processing_status' => 'pending']);

        ProcessChapterImages::dispatch($comic, $chapter, $tmpPaths, $urlList)
            ->onQueue('chapter-images');

        return redirect()
            ->route('admin.comics.chapters.index', $comic->id)
            ->with('success', "Chapter {$chapter->chapter_number} đã được tạo và đang xử lý ảnh (" . count($tmpPaths) . " file). Refresh sau vài giây để xem kết quả.");
    }

    /**
     * Giao diện chỉnh sửa chương.
     */
    public function edit(Comic $comic, Chapter $chapter)
    {
        return view('admin.chapters.edit', compact('comic', 'chapter'));
    }

    /**
     * Cập nhật chương — ảnh mới cũng đi qua queue nếu có file upload.
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

        // 3. Upload ảnh mới nếu có
        if ($request->hasFile('new_images')) {
            $folder   = $this->imageService->chapterFolder($comic->id, $chapter->id);
            $newPaths = $this->imageService->uploadBulk(
                $request->file('new_images'),
                $folder,
                null,
            );
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
