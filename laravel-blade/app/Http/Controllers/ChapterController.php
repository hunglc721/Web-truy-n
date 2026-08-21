<?php

namespace App\Http\Controllers;

use App\Jobs\FlushViewCounters;
use App\Models\Comic;
use App\Models\Chapter;
use App\Models\ReadingHistory;
use App\Models\Comment;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ChapterController extends Controller
{
    public function __construct(
        protected RecommendationService $recommendationService
    ) {}

    /**
     * Hiển thị nội dung đọc từng chương (Chế độ SEO Slug)
     * URL: /truyen/{comicSlug}/{chapterSlug}
     *
     * Publish-gate (DoD BE-01):
     *  - Guest / Member: chương có published_at > now() → 404
     *  - Admin: luôn xem được (preview) dù chưa tới giờ phát hành
     */
    public function show($comicSlug, $chapterSlug)
    {
        $isAdmin = auth()->check() && auth()->user()->isAdmin();

        // 1. Tìm bộ truyện theo Slug
        $comic = Comic::where('slug', $comicSlug)->firstOrFail();

        // 2. Lấy chương — phân nánh dựa trên dạng $chapterSlug để luôn dùng index:
        //    - numeric  (ví dụ: "1", "200") → tìm theo chapter_number (indexed)
        //    - còn lại (ví dụ: "chuong-1")  → tìm theo slug         (indexed)
        //    Tránh dùng orWhere(slug|chapter_number|id) khiến MySQL chọn ALL thay vì index.
        $baseQuery = Chapter::where('comic_id', $comic->id);

        if (is_numeric($chapterSlug)) {
            // URL dạng số: /truyen/solo-leveling/1 → tìm theo chapter_number
            $chapter = $isAdmin
                ? $baseQuery->preview() ->where('chapter_number', $chapterSlug)->firstOrFail()
                : $baseQuery->published()->where('chapter_number', $chapterSlug)->firstOrFail();
        } else {
            // URL dạng slug: /truyen/solo-leveling/chuong-1 → tìm theo slug
            $chapter = $isAdmin
                ? $baseQuery->preview() ->where('slug', $chapterSlug)->firstOrFail()
                : $baseQuery->published()->where('slug', $chapterSlug)->firstOrFail();
        }

        // 3. SEO Canonical: nếu URL không dùng slug chuẩn → redirect 301
        //    Tránh duplicate content: /truyen/solo-leveling/1 và /truyen/solo-leveling/chuong-1
        if ($chapterSlug !== $chapter->slug) {
            return redirect()->route('chapters.show', [
                'comicSlug'   => $comic->slug,
                'chapterSlug' => $chapter->slug,
            ], 301);
        }

        // 4. Lấy Chapter Trước và Chapter Sau
        //    - Admin: dùng preview() để nav sang chương tương lai nếu cần
        //    - Guest/Member: chỉ nav trong phạm vi đã phát hành
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

        // 5. Lấy danh sách chương cho dropdown — cache theo comic_id + role
        //    - Admin cache key berbeda agar tidak tercampur dengan reader cache
        //    - ChapterObserver sẽ xóa cả 2 cache khi có chapter mới/sửa/xóa
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

        // 6. Lấy bình luận của riêng chapter này (tách biệt hoàn toàn với bình luận cấp truyện)
        //    Eager-load ['user', 'replies.user'] để triệt tiêu N+1 query khi render
        $comments = Comment::with(['user', 'replies.user'])
            ->where('comic_id', $comic->id)
            ->where('chapter_id', $chapter->id)
            ->whereNull('parent_id')
            ->approved()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // 7. Đếm view qua Cache buffer — chống spam F5 bằng TTL 30 phút
        //    Key: view:{chapter_id}:{user_id|ip} — Không lưu vào session (tránh phình session)
        //    Admin preview không tính vào view count (tránh skew số liệu)
        if (!$isAdmin) {
            $userOrIp  = auth()->id() ?? request()->ip();
            $antiF5Key = "view:{$chapter->id}:{$userOrIp}";

            // Cache::add() trả về true nếu key CHƯA từng tồn tại (atomic chống F5)
            if (Cache::add($antiF5Key, true, 1800)) {
                FlushViewCounters::recordView($comic->id, $chapter->id);
            }
        }

        // 8. Lấy vị trí đọc dở gần nhất (scroll_percent) của user để khôi phục vị trí đọc
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

    /**
     * Tự động ghi nhận Lịch sử đọc qua AJAX request khi cuộn trang.
     * Validate chapter phải thuộc đúng comic được truyền vào.
     * Chỉ ghi lịch sử cho chương đã phát hành (published) — không ghi preview admin.
     */
    public function saveHistory(Request $request)
    {
        $request->validate([
            'comic_id'       => 'required|exists:comics,id',
            // Đảm bảo chapter_id thuộc đúng comic_id VÀ đã được phát hành
            'chapter_id'     => [
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
                'user_id'  => auth()->id(),
                'comic_id' => $request->comic_id,
            ],
            [
                'chapter_id'     => $request->chapter_id,
                'scroll_percent' => $scrollPercent,
                'last_read_at'   => now(),
            ]
        );

        // Đẩy việc invalidate recommendation cache vào queue kèm debounce 60s
        // Giúp các request đọc liên tục không phá vỡ cache hit rate của trang chủ & tủ sách
        if (auth()->check()) {
            \App\Jobs\InvalidateUserRecommendation::dispatch(auth()->id());
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Lịch sử đọc đã được cập nhật!',
        ]);
    }
}
