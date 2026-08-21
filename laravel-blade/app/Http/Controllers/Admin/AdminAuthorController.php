<?php
// app/Http/Controllers/Admin/AdminAuthorController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminAuthorController extends Controller
{
    public function index(Request $request)
    {
        $query = Author::withCount('comics');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('bio', 'like', "%{$search}%");
            });
        }

        $authors = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.authors.index', compact('authors'));
    }

    public function create()
    {
        return view('admin.authors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:150|unique:authors,name',
            'slug'   => 'nullable|string|max:180|unique:authors,slug|alpha_dash',
            'bio'    => 'nullable|string|max:2000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'name.required' => 'Tên tác giả không được để trống.',
            'name.unique'   => 'Tác giả này đã tồn tại.',
            'slug.unique'   => 'Slug này đã được dùng.',
            'avatar.image'  => 'Avatar phải là file ảnh.',
            'avatar.max'    => 'Kích thước avatar không được vượt quá 2MB.',
        ]);

        $validated['slug'] = $validated['slug']
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('authors/avatars', 'public');
        }

        $author = Author::create($validated);

        ActivityLog::record('admin.author.created', $author, [
            'name' => $author->name,
            'admin_id' => Auth::id(),
        ]);

        return redirect()->route('admin.authors.index')
            ->with('success', "Thêm tác giả \"{$author->name}\" thành công!");
    }

    public function edit(Author $author)
    {
        return view('admin.authors.edit', compact('author'));
    }

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
            'slug.unique'   => 'Slug này đã được dùng.',
            'avatar.image'  => 'Avatar phải là file ảnh.',
            'avatar.max'    => 'Kích thước avatar không được vượt quá 2MB.',
        ]);

        $old = $author->only(['name', 'slug', 'bio', 'avatar']);
        $validated['slug'] = $validated['slug']
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        if ($request->hasFile('avatar')) {
            if ($author->avatar && Storage::disk('public')->exists($author->avatar)) {
                Storage::disk('public')->delete($author->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('authors/avatars', 'public');
        } else {
            unset($validated['avatar']);
        }

        $author->update($validated);

        ActivityLog::record('admin.author.updated', $author, [
            'before' => $old,
            'after' => $author->only(['name', 'slug', 'bio', 'avatar']),
            'admin_id' => Auth::id(),
        ]);

        return redirect()->route('admin.authors.index')
            ->with('success', "Cập nhật tác giả \"{$author->name}\" thành công!");
    }

    public function destroy(Author $author)
    {
        if ($author->comics()->exists()) {
            return redirect()->route('admin.authors.index')
                ->with('error', "Không thể xóa \"{$author->name}\" vì đang có truyện liên kết.");
        }

        $name = $author->name;
        ActivityLog::record('admin.author.deleted', $author, [
            'name' => $name,
            'admin_id' => Auth::id(),
        ]);

        if ($author->avatar && Storage::disk('public')->exists($author->avatar)) {
            Storage::disk('public')->delete($author->avatar);
        }

        $author->delete();

        return redirect()->route('admin.authors.index')
            ->with('success', "Đã xóa tác giả \"{$name}\".");
    }
}
