<?php
// app/Http/Controllers/Admin/AdminGenreController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminGenreController extends Controller
{
    /**
     * Danh sách tất cả thể loại, kèm số lượng truyện mỗi thể loại.
     */
    public function index()
    {
        $genres = Genre::withCount('comics')
            ->orderBy('name', 'asc')
            ->paginate(20);

        return view('admin.genres.index', compact('genres'));
    }

    /**
     * Giao diện thêm thể loại mới.
     */
    public function create()
    {
        return view('admin.genres.create');
    }

    /**
     * Lưu thể loại mới vào CSDL.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:genres,name',
            'slug'        => 'nullable|string|max:120|unique:genres,slug|alpha_dash',
            'icon'        => 'nullable|string|max:10',
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'Tên thể loại không được để trống.',
            'name.unique'   => 'Thể loại này đã tồn tại.',
            'slug.unique'   => 'Slug này đã được dùng, hãy chọn slug khác.',
        ]);

        // Tự động tạo slug nếu user không nhập
        $validated['slug'] = $validated['slug']
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        Genre::create($validated);

        // Invalidate cache danh sách genres
        Cache::forget('all_genres');
        Cache::forget('home.genres');

        return redirect()->route('admin.genres.index')
            ->with('success', "Thêm thể loại \"{$validated['name']}\" thành công!");
    }

    /**
     * Giao diện chỉnh sửa thể loại.
     */
    public function edit(Genre $genre)
    {
        return view('admin.genres.edit', compact('genre'));
    }

    /**
     * Cập nhật thông tin thể loại.
     */
    public function update(Request $request, Genre $genre)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', Rule::unique('genres', 'name')->ignore($genre->id)],
            'slug'        => ['nullable', 'string', 'max:120', 'alpha_dash', Rule::unique('genres', 'slug')->ignore($genre->id)],
            'icon'        => 'nullable|string|max:10',
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'Tên thể loại không được để trống.',
            'name.unique'   => 'Thể loại này đã tồn tại.',
            'slug.unique'   => 'Slug này đã được dùng, hãy chọn slug khác.',
        ]);

        $validated['slug'] = $validated['slug']
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $genre->update($validated);

        // Invalidate cache danh sách genres
        Cache::forget('all_genres');
        Cache::forget('home.genres');

        return redirect()->route('admin.genres.index')
            ->with('success', "Cập nhật thể loại \"{$genre->name}\" thành công!");
    }

    /**
     * Xóa thể loại (chỉ xóa được nếu không có truyện nào dùng).
     */
    public function destroy(Genre $genre)
    {
        if ($genre->comics()->exists()) {
            return redirect()->route('admin.genres.index')
                ->with('error', "Không thể xóa \"{$genre->name}\" vì đang có truyện sử dụng thể loại này.");
        }

        $name = $genre->name;
        $genre->delete();

        // Invalidate cache danh sách genres
        Cache::forget('all_genres');
        Cache::forget('home.genres');

        return redirect()->route('admin.genres.index')
            ->with('success', "Đã xóa thể loại \"{$name}\".");
    }
}
