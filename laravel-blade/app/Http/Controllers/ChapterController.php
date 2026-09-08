<?php

namespace App\Http\Controllers;

use App\Jobs\FlushViewCounters;
use App\Models\Comic;
use App\Models\Chapter;
use App\Models\ReadingHistory;
use App\Models\Comment;
use App\Services\LibraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ChapterController extends Controller
{
    public function __construct(
        protected LibraryService $libraryService
    ) {}

    public function show($comicSlug, $chapterSlug)
    {
        $isAdmin = auth()->check() && auth()->user()->isAdmin();
        $comic = Comic::where('slug', $comicSlug)->firstOrFail();
        $baseQuery = Chapter::where('comic_id', $comic->id);

        if (is_numeric($chapterSlug)) {
            $chapter = $isAdmin
                ? $baseQuery->preview()->where('chapter_number', $chapterSlug)->firstOrFail()
                : $baseQuery->published()->where('chapter_number', $chapterSlug)->firstOrFail();
        } else {
            $chapter = $isAdmin
                ? $baseQuery->preview()->where('slug', $chapterSlug)->firstOrFail()
                : $baseQuery->published()->where('slug', $chapterSlug)->firstOrFail();
        }

        $canonicalChapterSlug = $chapter->slug ?: 'chapter-' . $chapter->chapter_number;
        $canonicalComicSlug = $comic->slug ?: 'comic-' . $comic->id;

        if ($chapterSlug !== $canonicalChapterSlug) {
            return redirect()->route('chapters.show', [
                'comicSlug' => $canonicalComicSlug,
                'chapterSlug' => $canonicalChapterSlug,
            ], 301);
        }

        $navScope = $isAdmin ? 'preview' : 'published';

        $nextChapter = Chapter::where('comic_id', $comic->id)
            ->where('chapter_number', '>', $chapter->chapter_number)
            ->{$navScope}()
            ->orderBy('chapter_number', 'asc')
            ->first();

        $prevChapter = Chapter::where('comic_id', $comic->id)
            ->where('chapter_number', '<', $chapter->chapter_number)
            ->{$navScope}()
            ->orderBy('chapter_number', 'desc')
            ->first();

        $cacheKey = $isAdmin
            ? "comic.{$comic->id}.chapters_list.admin"
            : "comic.{$comic->id}.chapters_list";

        $allChapters = Cache::remember(
            $cacheKey,
            3600,
            fn() => Chapter::where('comic_id', $comic->id)
                ->{$navScope}()
                ->select('id', 'slug', 'chapter_number', 'title', 'published_at')
                ->orderBy('chapter_number', 'desc')
                ->get()
        );

        $comments = Comment::with(['user', 'replies.user'])
            ->where('comic_id', $comic->id)
            ->where('chapter_id', $chapter->id)
            ->whereNull('parent_id')
            ->approved()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if (!$isAdmin) {
            $userOrIp = auth()->id() ?? request()->ip();
            $antiF5Key = "view_chapter:{$chapter->id}:{$userOrIp}";
            if (Cache::add($antiF5Key, true, 900)) {
                FlushViewCounters::recordView($comic->id, $chapter->id);
            }
        }

        $lastScrollPercent = 0;
        if (auth()->check()) {
            $history = ReadingHistory::where('user_id', auth()->id())
                ->where('comic_id', $comic->id)
                ->where('chapter_id', $chapter->id)
                ->first();
            if ($history && $history->scroll_percent > 0) {
                $lastScrollPercent = (float) $history->scroll_percent;
            }
        }

        return view('comics.reader', compact(
            'comic',
            'chapter',
            'nextChapter',
            'prevChapter',
            'allChapters',
            'comments',
            'isAdmin',
            'lastScrollPercent'
        ));
    }

    public function saveHistory(Request $request)
    {
        $request->validate([
            'comic_id' => 'required|exists:comics,id',
            'chapter_id' => [
                'required',
                Rule::exists('chapters', 'id')
                    ->where('comic_id', $request->comic_id)
                    ->whereNotNull('published_at')
                    ->where(fn ($query) => $query->where('published_at', '<=', now())),
            ],
            'scroll_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $scrollPercent = round(min(max((float) $request->input('scroll_percent', 0), 0), 100), 2);

        ReadingHistory::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'comic_id' => $request->comic_id,
            ],
            [
                'chapter_id' => $request->chapter_id,
                'scroll_percent' => $scrollPercent,
                'last_read_at' => now(),
            ]
        );

        if (auth()->check()) {
            $comic = Comic::select('id', 'title')->findOrFail($request->comic_id);
            $chapter = Chapter::select('id', 'comic_id', 'chapter_number')->findOrFail($request->chapter_id);
            $this->libraryService->syncLastReadChapter(auth()->user(), $comic, $chapter);
            \App\Jobs\InvalidateUserRecommendation::dispatch(auth()->id());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Lịch sử đọc đã được cập nhật!',
        ]);
    }
}
