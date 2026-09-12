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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminComicController extends Controller
{
    public function __construct(
        protected ImageService $imageService
    ) {}

    /**
     * Danh sách tất cả bộ truyện (Admin Dashboard) kèm bộ lọc & tìm kiếm
     */
    public function index(Request $request)
    {
        $query = Comic::withCount('chapters');

        // 1. Tìm kiếm theo từ khóa (tên hoặc slug)
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }

        // 2. Lọc theo thể loại
        if ($request->filled('genre_id') && $request->genre_id !== 'all') {
            $genreId = (int) $request->genre_id;
            $query->whereHas('genres', function ($g) use ($genreId) {
                $g->where('genres.id', $genreId);
            });
        }

        // 3. Lọc theo tác giả
        if ($request->filled('author_id') && $request->author_id !== 'all') {
            $authorId = (int) $request->author_id;
            $query->whereHas('authors', function ($a) use ($authorId) {
                $a->where('authors.id', $authorId);
            });
        }

        // 4. Lọc theo trạng thái
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // 5. Sắp xếp
        match ($request->input('sort', 'latest')) {
            'oldest'   => $query->orderBy('id', 'asc'),
            'views'    => $query->orderByDesc('views'),
            'chapters' => $query->orderByDesc('chapters_count'),
            'title'    => $query->orderBy('title', 'asc'),
            default    => $query->orderByDesc('id'),
        };

        $comics = $query->paginate(15)->withQueryString();

        $genres  = Genre::orderBy('name')->get(['id', 'name']);
        $authors = Author::orderBy('name')->get(['id', 'name']);

        return view('admin.comics.index', compact('comics', 'genres', 'authors'));
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
        $coverPath = null;

        // Xử lý upload ảnh bìa (nếu có)
        if ($request->hasFile('cover_image')) {
            $coverPath = $this->imageService->uploadCover($request->file('cover_image'));
            $data['cover_image'] = $coverPath;
        } else {
            $data['cover_image'] = '';
        }

        try {
            $comic = DB::transaction(function () use ($data, $request) {
                $comic = Comic::create($data);

                // Sync quan hệ nhiều-nhiều
                $comic->genres()->sync($request->input('genre_ids', []));
                $comic->tags()->sync($request->input('tag_ids', []));

                if (!empty($request->input('author_ids'))) {
                    $comic->authors()->sync($request->input('author_ids'));
                }

                return $comic;
            });
        } catch (\Throwable $e) {
            if ($coverPath && Storage::disk('public')->exists($coverPath)) {
                Storage::disk('public')->delete($coverPath);
            }
            throw $e;
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
        $newCoverPath = null;
        $oldCoverPath = $comic->cover_image;

        // Xử lý upload ảnh bìa mới (nếu có)
        if ($request->hasFile('cover_image')) {
            $newCoverPath = $this->imageService->uploadCover($request->file('cover_image'));
            $data['cover_image'] = $newCoverPath;
        }

        try {
            DB::transaction(function () use ($comic, $data, $request) {
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
            });
        } catch (\Throwable $e) {
            // Nếu update DB thất bại sau khi đã upload file mới, xóa file mới để tránh orphan file
            if ($newCoverPath && Storage::disk('public')->exists($newCoverPath)) {
                Storage::disk('public')->delete($newCoverPath);
            }
            throw $e;
        }

        // Chỉ sau khi DB update thành công mới xóa cover local cũ
        // Điều kiện xóa an toàn:
        // - Đã upload cover mới
        // - Cover cũ không rỗng
        // - Không xóa nếu là URL ngoài (http://, https://, //)
        // - Chỉ xóa khi chắc chắn path thuộc comics/covers/
        // - File cũ tồn tại trên disk
        if (
            $newCoverPath !== null &&
            !empty($oldCoverPath) &&
            !str_starts_with($oldCoverPath, 'http://') &&
            !str_starts_with($oldCoverPath, 'https://') &&
            !str_starts_with($oldCoverPath, '//') &&
            str_starts_with($oldCoverPath, 'comics/covers/') &&
            $oldCoverPath !== $newCoverPath &&
            Storage::disk('public')->exists($oldCoverPath)
        ) {
            Storage::disk('public')->delete($oldCoverPath);
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
