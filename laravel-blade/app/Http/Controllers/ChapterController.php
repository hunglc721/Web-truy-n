<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\ReadingHistory;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ChapterController extends Controller
{
    /**
     * Hiển thị nội dung đọc từng chương (Chế độ SEO Slug)
     * URL: /truyen/{comicSlug}/{chapterSlug}
     */
    public function show($comicSlug, $chapterSlug)
    {
        // 1. Tìm bộ truyện theo Slug
        $comic = Comic::where('slug', $comicSlug)->firstOrFail();

        // 2. Tìm chương theo slug | chapter_number | id (chỉ trong phạm vi comic này)
        $chapter = Chapter::where('comic_id', $comic->id)
            ->where(function($q) use ($chapterSlug) {
                $q->where('slug', $chapterSlug)
                  ->orWhere('chapter_number', $chapterSlug)
                  ->orWhere('id', $chapterSlug);
            })->firstOrFail();

        // 3. SEO Canonical: nếu URL không dùng slug chuẩn → redirect 301
        //    Tránh duplicate content: /truyen/solo-leveling/1 và /truyen/solo-leveling/chuong-1
        if ($chapterSlug !== $chapter->slug) {
            return redirect()->route('chapters.show', [
                'comicSlug'   => $comic->slug,
                'chapterSlug' => $chapter->slug,
            ], 301);
        }

        // 4. Lấy Chapter Trước và Chapter Sau
        $nextChapter = Chapter::where('comic_id', $comic->id)
            ->where('chapter_number', '>', $chapter->chapter_number)
            ->orderBy('chapter_number', 'asc')
            ->first();

        $prevChapter = Chapter::where('comic_id', $comic->id)
            ->where('chapter_number', '<', $chapter->chapter_number)
            ->orderBy('chapter_number', 'desc')
            ->first();

        // 5. Lấy danh sách chương cho dropdown — cache theo comic_id 1 giờ
        //    ChapterObserver sẽ xóa cache khi có chapter mới/sửa/xóa
        $allChapters = Cache::remember(
            "comic.{$comic->id}.chapters_list",
            3600,
            fn() => Chapter::where('comic_id', $comic->id)
                ->select('id', 'slug', 'chapter_number', 'title')
                ->orderBy('chapter_number', 'desc')
                ->get()
        );

        // 6. Lấy bình luận — phân trang để tránh treo trang khi truyện hot
        $comments = Comment::with('user')
            ->where('comic_id', $comic->id)
            ->where(function($q) use ($chapter) {
                $q->where('chapter_id', $chapter->id)->orWhereNull('chapter_id');
            })
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // 7. Throttle view counter qua session — chặn spam F5
        //    Mỗi session chỉ tính 1 lượt xem / chapter
        $sessionKey = "viewed_chapter_{$chapter->id}";
        if (!session()->has($sessionKey)) {
            $comic->increment('views');
            $chapter->increment('views');
            session()->put($sessionKey, true);
        }

        return view('comics.reader', compact(
            'comic',
            'chapter',
            'nextChapter',
            'prevChapter',
            'allChapters',
            'comments'
        ));
    }

    /**
     * Tự động ghi nhận Lịch sử đọc qua AJAX request khi cuộn trang.
     * Validate chapter phải thuộc đúng comic được truyền vào.
     */
    public function saveHistory(Request $request)
    {
        $request->validate([
            'comic_id'   => 'required|exists:comics,id',
            // Đảm bảo chapter_id thuộc đúng comic_id — chặn cặp lệch nhau
            'chapter_id' => [
                'required',
                Rule::exists('chapters', 'id')->where('comic_id', $request->comic_id),
            ],
        ]);

        ReadingHistory::updateOrCreate(
            [
                'user_id'  => auth()->id(),
                'comic_id' => $request->comic_id,
            ],
            [
                'chapter_id'   => $request->chapter_id,
                'last_read_at' => now(),
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Lịch sử đọc đã được cập nhật!',
        ]);
    }
}
