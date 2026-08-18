<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\Chapter;
use App\Models\Genre;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminComicController extends Controller
{
    /**
     * Danh sách tất cả bộ truyện (Admin Dashboard)
     */
    public function index()
    {
        $comics = Comic::withCount('chapters')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('admin.comics.index', compact('comics'));
    }

    /**
     * Giao diện đăng bộ truyện mới
     */
    public function create()
    {
        $genres = Genre::all();
        $authors = Author::all();
        return view('admin.comics.create', compact('genres', 'authors'));
    }

    /**
     * Lưu bộ truyện mới vào CSDL
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'cover_image' => 'required|url',
            'description' => 'required|string',
            'status'      => 'required|in:ONGOING,COMPLETED',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['is_original'] = $request->has('is_original');
        $validated['is_featured'] = $request->has('is_featured');

        $comic = Comic::create($validated);

        if ($request->has('genres')) {
            $comic->genres()->sync($request->input('genres'));
        }

        return redirect()->route('admin.comics.index')
            ->with('success', 'Đăng bộ truyện mới thành công!');
    }

    /**
     * Giao diện chỉnh sửa bộ truyện
     */
    public function edit($id)
    {
        $comic = Comic::with(['genres', 'authors'])->findOrFail($id);
        $genres = Genre::all();
        $authors = Author::all();
        return view('admin.comics.edit', compact('comic', 'genres', 'authors'));
    }

    /**
     * Cập nhật thông tin bộ truyện
     */
    public function update(Request $request, $id)
    {
        $comic = Comic::findOrFail($id);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'cover_image' => 'required|url',
            'description' => 'required|string',
            'status'      => 'required|in:ONGOING,COMPLETED',
        ]);

        $validated['is_original'] = $request->has('is_original');
        $validated['is_featured'] = $request->has('is_featured');

        $comic->update($validated);

        if ($request->has('genres')) {
            $comic->genres()->sync($request->input('genres'));
        }

        return redirect()->route('admin.comics.index')
            ->with('success', 'Cập nhật bộ truyện thành công!');
    }

    /**
     * Xóa bộ truyện
     */
    public function destroy($id)
    {
        $comic = Comic::findOrFail($id);
        $comic->delete();

        return redirect()->route('admin.comics.index')
            ->with('success', 'Đã xóa bộ truyện!');
    }

    /**
     * Giao diện thêm Chapter mới cho bộ truyện
     */
    public function createChapter($comicId)
    {
        $comic = Comic::findOrFail($comicId);
        $nextChapterNumber = ($comic->chapters()->max('chapter_number') ?? 0) + 1;

        return view('admin.chapters.create', compact('comic', 'nextChapterNumber'));
    }

    /**
     * Lưu Chapter mới với danh sách đường dẫn ảnh
     */
    public function storeChapter(Request $request, $comicId)
    {
        $comic = Comic::findOrFail($comicId);

        $request->validate([
            'chapter_number' => 'required|numeric',
            'title'          => 'nullable|string|max:255',
            'pages_raw'      => 'required|string', // Nhập danh sách đường dẫn ảnh (mỗi dòng 1 link URL)
        ]);

        // Tách danh sách đường dẫn ảnh theo dòng mới
        $pages = array_values(array_filter(
            array_map('trim', explode("\n", $request->input('pages_raw'))),
            fn($url) => !empty($url)
        ));

        Chapter::create([
            'comic_id'       => $comic->id,
            'chapter_number' => $request->input('chapter_number'),
            'title'          => $request->input('title') ?: 'Chapter ' . $request->input('chapter_number'),
            'slug'           => 'chapter-' . $request->input('chapter_number'),
            'pages'          => $pages,
            'published_at'   => now(),
            'is_free'        => true,
        ]);

        return redirect()->route('admin.comics.index')
            ->with('success', 'Thêm Chapter ' . $request->input('chapter_number') . ' thành công!');
    }
}
