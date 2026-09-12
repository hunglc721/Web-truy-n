<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreChapterRequest;
use App\Http\Requests\Admin\UpdateChapterRequest;
use App\Jobs\ProcessChapterImages;
use App\Jobs\ProcessZipChapterUploadJob;
use App\Models\Comic;
use App\Models\Chapter;
use App\Services\BulkChapterUploadService;
use App\Services\ChapterNotificationService;
use App\Services\ChapterService;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminChapterController extends Controller
{
    public function __construct(
        protected ChapterService $chapterService,
        protected ImageService $imageService,
        protected ChapterNotificationService $notificationService,
        protected BulkChapterUploadService $bulkChapterUploadService,
    ) {}

    public function all(Request $request)
    {
        $query = Chapter::with('comic')->latest('id');

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

    public function index(Comic $comic)
    {
        $chapters = $comic->chapters()->orderBy('chapter_number', 'desc')->paginate(20);
        return view('admin.chapters.index', compact('comic', 'chapters'));
    }

    public function create(Comic $comic)
    {
        $nextChapterNumber = ($comic->chapters()->max('chapter_number') ?? 0) + 1;
        return view('admin.chapters.create', compact('comic', 'nextChapterNumber'));
    }

    public function store(StoreChapterRequest $request, Comic $comic)
    {
        if ($request->filled('bulk_action')) {
            return $this->storeBulkFolder($request, $comic);
        }

        if (!$request->hasContent()) {
            return back()->withInput()->withErrors([
                'images' => 'Bạn phải chọn ít nhất 1 file ảnh hoặc dán danh sách đường dẫn URL ảnh.',
            ]);
        }

        $chapter = $this->chapterService->createWithPages($comic, [
            'chapter_number' => $request->chapter_number,
            'title'          => $request->title,
            'is_free'        => $request->boolean('is_free', true),
        ], []);

        $tmpPaths = [];
        $urlList  = [];

        if ($request->hasFile('zip_file')) {
            $zipFile = $request->file('zip_file');
            $tmpZipName = 'upload_' . $chapter->id . '_' . time() . '.zip';
            $targetDirectory = storage_path('app/tmp/zip_uploads');
            File::ensureDirectoryExists($targetDirectory);

            // PHP đã ghi multipart upload ra file tạm. Move trực tiếp file đó vào staging
            // thay vì storeAs/writeStream thêm một vòng copy toàn bộ ZIP vài chục MB.
            $movedZip = $zipFile->move($targetDirectory, $tmpZipName);
            $zipAbsolutePath = $movedZip->getPathname();

            $chapter->update(['processing_status' => 'pending']);

            ProcessZipChapterUploadJob::dispatch($comic, $chapter, $zipAbsolutePath)
                ->onQueue('chapter-images');

            return redirect()
                ->route('admin.comics.chapters.index', $comic->id)
                ->with('success', "File .ZIP của Chapter {$chapter->chapter_number} đã được tải lên và đang tự động giải nén, sắp xếp thứ tự và tối ưu ảnh.");
        }

        if ($request->hasFile('images')) {
            $tmpFolder = "tmp/comics/{$comic->id}/chapters/{$chapter->id}";
            foreach ($request->file('images') as $idx => $file) {
                $tmpPaths[] = $this->imageService->uploadSingle($file, $tmpFolder, $idx);
            }
        }

        if (!empty(trim($request->input('pages_raw', '')))) {
            $urlList = $this->imageService->parseUrlList($request->pages_raw);
        }

        if (empty($tmpPaths) && !empty($urlList)) {
            $chapter->update([
                'pages'             => $urlList,
                'processing_status' => 'ready',
            ]);

            $this->notificationService->dispatchIfEligible($chapter);

            return redirect()
                ->route('admin.comics.chapters.index', $comic->id)
                ->with('success', "Đăng thành công Chapter {$chapter->chapter_number} với " . count($urlList) . " trang URL!");
        }

        $chapter->update(['processing_status' => 'pending']);

        ProcessChapterImages::dispatch($comic, $chapter, $tmpPaths, $urlList)
            ->onQueue('chapter-images');

        return redirect()
            ->route('admin.comics.chapters.index', $comic->id)
            ->with('success', "Chapter {$chapter->chapter_number} đã được tạo và đang xử lý ảnh (" . count($tmpPaths) . " file). Refresh sau vài giây để xem kết quả.");
    }

    private function storeBulkFolder(StoreChapterRequest $request, Comic $comic): JsonResponse
    {
        $user = $request->user();
        $action = (string) $request->input('bulk_action');

        $result = match ($action) {
            'start' => $this->bulkChapterUploadService->start($comic, $user),
            'chunk' => $this->bulkChapterUploadService->storeChunk(
                $comic,
                $user,
                (string) $request->input('session'),
                (string) $request->input('chapter_key'),
                $request->file('files', []),
                array_values((array) $request->input('page_indexes', [])),
                array_values((array) $request->input('checksums', [])),
            ),
            'finalize' => $this->bulkChapterUploadService->finalizeChapter(
                $comic,
                $user,
                (string) $request->input('session'),
                (string) $request->input('chapter_key'),
                (float) $request->input('chapter_number'),
                $request->filled('title') ? (string) $request->input('title') : null,
                (int) $request->input('page_count'),
            ),
            'complete' => $this->bulkChapterUploadService->complete(
                $comic,
                $user,
                (string) $request->input('session'),
            ),
        };

        return response()->json([
            'status' => 'ok',
            ...$result,
        ]);
    }

    public function edit(Comic $comic, Chapter $chapter)
    {
        return view('admin.chapters.edit', compact('comic', 'chapter'));
    }

    public function update(UpdateChapterRequest $request, Comic $comic, Chapter $chapter)
    {
        $removedPages = $request->input('removed_pages', []);
        if (!empty($removedPages)) {
            $this->imageService->deleteFiles((array) $removedPages);
        }

        $finalPages = array_values((array) $request->input('existing_pages', []));

        if ($request->hasFile('new_images')) {
            $folder   = $this->imageService->chapterFolder($comic->id, $chapter->id);
            $newPaths = $this->imageService->uploadBulk(
                $request->file('new_images'),
                $folder,
                null,
            );
            $finalPages = array_merge($finalPages, $newPaths);
        }

        if (!empty(trim($request->input('add_urls', '')))) {
            $finalPages = array_merge($finalPages, $this->imageService->parseUrlList($request->add_urls));
        }

        $updatedChapter = $this->chapterService->updateWithPages($chapter, [
            'chapter_number' => $request->chapter_number,
            'title'          => $request->title,
            'is_free'        => $request->boolean('is_free', true),
        ], $finalPages);

        app(\App\Services\ReaderImageService::class)->enqueue($updatedChapter->id);
        $this->notificationService->dispatchIfEligible($updatedChapter);

        return redirect()
            ->route('admin.comics.chapters.index', $comic->id)
            ->with('success', "Cập nhật Chapter {$request->chapter_number} thành công!");
    }

    public function destroy(Comic $comic, Chapter $chapter)
    {
        $chapterNumber = $chapter->chapter_number;
        $this->chapterService->delete($comic, $chapter);

        return redirect()
            ->route('admin.comics.chapters.index', $comic->id)
            ->with('success', "Đã xóa Chapter {$chapterNumber} và toàn bộ ảnh thuộc chương!");
    }
}
