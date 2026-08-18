<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\ReadingHistory;
use App\Models\Comment;
use Illuminate\Http\Request;

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

        // 2. Tìm chương theo Slug thuộc truyện
        $chapter = Chapter::where('comic_id', $comic->id)
            ->where(function($q) use ($chapterSlug) {
                $q->where('slug', $chapterSlug)
                  ->orWhere('chapter_number', $chapterSlug)
                  ->orWhere('id', $chapterSlug);
            })->firstOrFail();

        // 3. Lấy Chapter Trước và Chapter Sau
        $nextChapter = Chapter::where('comic_id', $comic->id)
            ->where('chapter_number', '>', $chapter->chapter_number)
            ->orderBy('chapter_number', 'asc')
            ->first();

        $prevChapter = Chapter::where('comic_id', $comic->id)
            ->where('chapter_number', '<', $chapter->chapter_number)
            ->orderBy('chapter_number', 'desc')
            ->first();

        // 4. Lấy tất cả các chương để chọn nhanh
        $allChapters = Chapter::where('comic_id', $comic->id)
            ->orderBy('chapter_number', 'desc')
            ->get();

        // 5. Lấy danh sách bình luận của chương & truyện
        $comments = Comment::with('user')
            ->where('comic_id', $comic->id)
            ->where(function($q) use ($chapter) {
                $q->where('chapter_id', $chapter->id)->orWhereNull('chapter_id');
            })
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();

        // 6. Tự động tăng lượt xem
        $comic->increment('views');
        $chapter->increment('views');

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
     * Tự động ghi nhận Lịch sử đọc qua AJAX request khi cuộn trang
     */
    public function saveHistory(Request $request)
    {
        $request->validate([
            'comic_id'   => 'required|exists:comics,id',
            'chapter_id' => 'required|exists:chapters,id',
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
            'message' => 'Lịch sử đọc đã được cập nhật!'
        ]);
    }
}
