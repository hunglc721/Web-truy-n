<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreComicRequest;
use App\Http\Requests\Admin\UpdateComicRequest;
use App\Models\ActivityLog;
use App\Models\Author;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Tag;
use App\Services\ImageService;

class AdminComicController extends Controller
{
    public function __construct(
        protected ImageService $imageService
    ) {}

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
        $genres  = Genre::orderBy('name')->get();
        $authors = Author::orderBy('name')->get();
        $tags    = Tag::orderBy('name')->get();

        return view('admin.comics.create', compact('genres', 'authors', 'tags'));
    }

    /**
     * Lưu bộ truyện mới vào CSDL.
     * Validation & authorization đã được xử lý bởi StoreComicRequest.
     */
    public function store(StoreComicRequest $request)
    {
        $data = $request->safe()->except(['genre_ids', 'tag_ids', 'author_ids', 'cover_image']);

        // Xử lý upload ảnh bìa (nếu có)
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->imageService->uploadCover($request->file('cover_image'));
        }

        $comic = Comic::create($data);

        // Sync quan hệ nhiều-nhiều
        $comic->genres()->sync($request->input('genre_ids', []));
        $comic->tags()->sync($request->input('tag_ids', []));

        if (!empty($request->input('author_ids'))) {
            $comic->authors()->sync($request->input('author_ids'));
        }

        // Ghi activity log
        ActivityLog::record('admin.comic.created', $comic, [
            'title'      => $comic->title,
            'genre_ids'  => $request->input('genre_ids', []),
            'tag_ids'    => $request->input('tag_ids', []),
            'author_ids' => $request->input('author_ids', []),
        ]);

        return redirect()->route('admin.comics.index')
            ->with('success', 'Đăng bộ truyện mới thành công!');
    }

    /**
     * Giao diện chỉnh sửa bộ truyện
     */
    public function edit($id)
    {
        $comic   = Comic::with(['genres', 'authors', 'tags'])->findOrFail($id);
        $genres  = Genre::orderBy('name')->get();
        $authors = Author::orderBy('name')->get();
        $tags    = Tag::orderBy('name')->get();

        return view('admin.comics.edit', compact('comic', 'genres', 'authors', 'tags'));
    }

    /**
     * Cập nhật thông tin bộ truyện.
     * Validation & authorization đã được xử lý bởi UpdateComicRequest.
     */
    public function update(UpdateComicRequest $request, $id)
    {
        $comic = Comic::findOrFail($id);

        $data = $request->safe()->except(['genre_ids', 'tag_ids', 'author_ids', 'cover_image']);

        // Xử lý upload ảnh bìa mới (nếu có)
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->imageService->uploadCover($request->file('cover_image'));
        }

        $comic->update($data);

        // Sync quan hệ nhiều-nhiều (chỉ khi field được gửi lên)
        if ($request->has('genre_ids')) {
            $comic->genres()->sync($request->input('genre_ids', []));
        }
        if ($request->has('tag_ids')) {
            $comic->tags()->sync($request->input('tag_ids', []));
        }
        if ($request->has('author_ids')) {
            $comic->authors()->sync($request->input('author_ids', []));
        }

        // Ghi activity log
        ActivityLog::record('admin.comic.updated', $comic, [
            'changed_fields' => array_keys($data),
        ]);

        return redirect()->route('admin.comics.index')
            ->with('success', 'Cập nhật bộ truyện thành công!');
    }

    /**
     * Xóa bộ truyện (soft delete)
     */
    public function destroy($id)
    {
        $comic = Comic::findOrFail($id);

        // Ghi log trước khi xóa (sau khi xóa không còn subject)
        ActivityLog::record('admin.comic.deleted', $comic, [
            'title' => $comic->title,
            'slug'  => $comic->slug,
        ]);

        $comic->delete();

        return redirect()->route('admin.comics.index')
            ->with('success', 'Đã xóa bộ truyện!');
    }
}
