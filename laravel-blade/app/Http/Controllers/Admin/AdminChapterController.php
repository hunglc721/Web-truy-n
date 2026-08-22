<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreChapterRequest;
use App\Http\Requests\Admin\UpdateChapterRequest;
use App\Jobs\ProcessChapterImages;
use App\Models\Comic;
use App\Models\Chapter;
use App\Services\ChapterNotificationService;
use App\Services\ChapterService;
use App\Services\ImageService;
use Illuminate\Http\Request;

class AdminChapterController extends Controller
{
    public function __construct(
        protected ChapterService $chapterService,
        protected ImageService $imageService,
        protected ChapterNotificationService $notificationService,
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
            $storedZipPath = $zipFile->storeAs('tmp/zip_uploads', $tmpZipName);
            $zipAbsolutePath = storage_path('app/' . $storedZipPath);

            $chapter->update(['processing_status' => 'pending']);

            \App\Jobs\ProcessZipChapterUploadJob::dispatch($comic, $chapter, $zipAbsolutePath)
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
