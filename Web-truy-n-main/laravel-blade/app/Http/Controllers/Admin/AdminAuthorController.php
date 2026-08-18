<?php
// app/Http/Controllers/Admin/AdminAuthorController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminAuthorController extends Controller
{
    /**
     * Danh sách tác giả với số truyện.
     */
    public function index()
    {
        $authors = Author::withCount('comics')
            ->orderBy('name', 'asc')
            ->paginate(20);

        return view('admin.authors.index', compact('authors'));
    }

    /**
     * Giao diện thêm tác giả mới.
     */
    public function create()
    {
        return view('admin.authors.create');
    }

    /**
     * Lưu tác giả mới (hỗ trợ upload avatar).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:150|unique:authors,name',
            'slug'   => 'nullable|string|max:180|unique:authors,slug|alpha_dash',
            'bio'    => 'nullable|string|max:2000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // max 2MB
        ], [
            'name.required' => 'Tên tác giả không được để trống.',
            'name.unique'   => 'Tác giả này đã tồn tại.',
            'avatar.image'  => 'Avatar phải là file ảnh.',
            'avatar.max'    => 'Kích thước avatar không được vượt quá 2MB.',
        ]);

        $validated['slug'] = $validated['slug']
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Xử lý upload avatar
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('authors/avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        Author::create($validated);

        return redirect()->route('admin.authors.index')
            ->with('success', "Thêm tác giả \"{$validated['name']}\" thành công!");
    }

    /**
     * Giao diện chỉnh sửa tác giả.
     */
    public function edit(Author $author)
    {
        return view('admin.authors.edit', compact('author'));
    }

    /**
     * Cập nhật thông tin tác giả.
     */
    public function update(Request $request, Author $author)
    {
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:150', Rule::unique('authors', 'name')->ignore($author->id)],
            'slug'   => ['nullable', 'string', 'max:180', 'alpha_dash', Rule::unique('authors', 'slug')->ignore($author->id)],
            'bio'    => 'nullable|string|max:2000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'name.required' => 'Tên tác giả không được để trống.',
            'name.unique'   => 'Tác giả này đã tồn tại.',
            'avatar.image'  => 'Avatar phải là file ảnh.',
            'avatar.max'    => 'Kích thước avatar không được vượt quá 2MB.',
        ]);

        $validated['slug'] = $validated['slug']
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Upload avatar mới nếu có
        if ($request->hasFile('avatar')) {
            // Xóa avatar cũ nếu tồn tại
            if ($author->avatar && \Storage::disk('public')->exists($author->avatar)) {
                \Storage::disk('public')->delete($author->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('authors/avatars', 'public');
        } else {
            // Giữ nguyên avatar cũ
            unset($validated['avatar']);
        }

        $author->update($validated);

        return redirect()->route('admin.authors.index')
            ->with('success', "Cập nhật tác giả \"{$author->name}\" thành công!");
    }

    /**
     * Xóa tác giả.
     */
    public function destroy(Author $author)
    {
        if ($author->comics()->exists()) {
            return redirect()->route('admin.authors.index')
                ->with('error', "Không thể xóa \"{$author->name}\" vì đang có truyện liên kết.");
        }

        // Xóa avatar khi xóa tác giả
        if ($author->avatar && \Storage::disk('public')->exists($author->avatar)) {
            \Storage::disk('public')->delete($author->avatar);
        }

        $name = $author->name;
        $author->delete();

        return redirect()->route('admin.authors.index')
            ->with('success', "Đã xóa tác giả \"{$name}\".");
    }
}
