<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminGenreController extends Controller
{
    public function index()
    {
        $genres = Genre::withCount('comics')->orderBy('name')->paginate(20);
        return view('admin.genres.index', compact('genres'));
    }

    public function create()
    {
        return view('admin.genres.create');
    }

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

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $genre = Genre::create($validated);
        $this->flushGenreCache();
        ActivityLog::record('admin.genre.created', $genre);

        return redirect()->route('admin.genres.index')->with('success', "Thêm thể loại \"{$genre->name}\" thành công!");
    }

    public function edit(Genre $genre)
    {
        return view('admin.genres.edit', compact('genre'));
    }

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

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $genre->update($validated);
        $this->flushGenreCache();
        ActivityLog::record('admin.genre.updated', $genre);

        return redirect()->route('admin.genres.index')->with('success', "Cập nhật thể loại \"{$genre->name}\" thành công!");
    }

    public function destroy(Genre $genre)
    {
        if ($genre->comics()->exists()) {
            return redirect()->route('admin.genres.index')->with('error', "Không thể xóa \"{$genre->name}\" vì đang có truyện sử dụng thể loại này.");
        }

        $name = $genre->name;
        ActivityLog::record('admin.genre.deleted', $genre, ['name' => $name]);
        $genre->delete();
        $this->flushGenreCache();

        return redirect()->route('admin.genres.index')->with('success', "Đã xóa thể loại \"{$name}\".");
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'integer|distinct|exists:genres,id',
        ]);

        $genres = Genre::withCount('comics')->whereIn('id', $validated['ids'])->get();
        $blocked = $genres->where('comics_count', '>', 0);
        $deletable = $genres->where('comics_count', 0);

        DB::transaction(function () use ($deletable) {
            foreach ($deletable as $genre) {
                ActivityLog::record('admin.genre.deleted', $genre, ['bulk' => true, 'name' => $genre->name]);
                $genre->delete();
            }
        });

        $this->flushGenreCache();

        $message = 'Đã xóa ' . $deletable->count() . ' thể loại không được sử dụng.';
        if ($blocked->isNotEmpty()) {
            $message .= ' Bỏ qua ' . $blocked->count() . ' thể loại đang có truyện liên kết.';
        }

        return redirect()->route('admin.genres.index')->with($deletable->isNotEmpty() ? 'success' : 'error', $message);
    }

    private function flushGenreCache(): void
    {
        Cache::forget('all_genres');
        Cache::forget('home.genres');
    }
}
