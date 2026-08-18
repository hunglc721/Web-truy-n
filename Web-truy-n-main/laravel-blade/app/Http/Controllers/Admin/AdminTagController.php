<?php
// app/Http/Controllers/Admin/AdminTagController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminTagController extends Controller
{
    /**
     * Danh sách tất cả tags, kèm số truyện.
     */
    public function index()
    {
        $tags = Tag::withCount('comics')
            ->orderBy('name', 'asc')
            ->paginate(25);

        return view('admin.tags.index', compact('tags'));
    }

    /**
     * Giao diện thêm tag mới.
     */
    public function create()
    {
        return view('admin.tags.create');
    }

    /**
     * Lưu tag mới.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:60|unique:tags,name',
            'slug'  => 'nullable|string|max:80|unique:tags,slug|alpha_dash',
            'color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        ], [
            'name.required' => 'Tên tag không được để trống.',
            'name.unique'   => 'Tag này đã tồn tại.',
            'color.regex'   => 'Màu phải đúng định dạng hex (ví dụ: #FF5733).',
        ]);

        $validated['slug'] = $validated['slug']
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        Tag::create($validated);

        return redirect()->route('admin.tags.index')
            ->with('success', "Thêm tag \"{$validated['name']}\" thành công!");
    }

    /**
     * Giao diện chỉnh sửa tag.
     */
    public function edit(Tag $tag)
    {
        return view('admin.tags.edit', compact('tag'));
    }

    /**
     * Cập nhật tag.
     */
    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:60', Rule::unique('tags', 'name')->ignore($tag->id)],
            'slug'  => ['nullable', 'string', 'max:80', 'alpha_dash', Rule::unique('tags', 'slug')->ignore($tag->id)],
            'color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        ], [
            'name.required' => 'Tên tag không được để trống.',
            'name.unique'   => 'Tag này đã tồn tại.',
            'color.regex'   => 'Màu phải đúng định dạng hex (ví dụ: #FF5733).',
        ]);

        $validated['slug'] = $validated['slug']
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $tag->update($validated);

        return redirect()->route('admin.tags.index')
            ->with('success', "Cập nhật tag \"{$tag->name}\" thành công!");
    }

    /**
     * Xóa tag.
     */
    public function destroy(Tag $tag)
    {
        if ($tag->comics()->exists()) {
            return redirect()->route('admin.tags.index')
                ->with('error', "Không thể xóa tag \"{$tag->name}\" vì đang được gán cho truyện.");
        }

        $name = $tag->name;
        $tag->delete();

        return redirect()->route('admin.tags.index')
            ->with('success', "Đã xóa tag \"{$name}\".");
    }
}
