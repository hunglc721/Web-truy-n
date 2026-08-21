<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminTagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('comics')->orderBy('name')->paginate(25);
        return view('admin.tags.index', compact('tags'));
    }

    public function create()
    {
        return view('admin.tags.create');
    }

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

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $tag = Tag::create($validated);
        ActivityLog::record('admin.tag.created', $tag);

        return redirect()->route('admin.tags.index')->with('success', "Thêm tag \"{$tag->name}\" thành công!");
    }

    public function edit(Tag $tag)
    {
        return view('admin.tags.edit', compact('tag'));
    }

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

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $tag->update($validated);
        ActivityLog::record('admin.tag.updated', $tag);

        return redirect()->route('admin.tags.index')->with('success', "Cập nhật tag \"{$tag->name}\" thành công!");
    }

    public function destroy(Tag $tag)
    {
        if ($tag->comics()->exists()) {
            return redirect()->route('admin.tags.index')->with('error', "Không thể xóa tag \"{$tag->name}\" vì đang được gán cho truyện.");
        }

        $name = $tag->name;
        ActivityLog::record('admin.tag.deleted', $tag, ['name' => $name]);
        $tag->delete();

        return redirect()->route('admin.tags.index')->with('success', "Đã xóa tag \"{$name}\".");
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'integer|distinct|exists:tags,id',
        ]);

        $tags = Tag::withCount('comics')->whereIn('id', $validated['ids'])->get();
        $blocked = $tags->where('comics_count', '>', 0);
        $deletable = $tags->where('comics_count', 0);

        DB::transaction(function () use ($deletable) {
            foreach ($deletable as $tag) {
                ActivityLog::record('admin.tag.deleted', $tag, ['bulk' => true, 'name' => $tag->name]);
                $tag->delete();
            }
        });

        $message = 'Đã xóa ' . $deletable->count() . ' tag không được sử dụng.';
        if ($blocked->isNotEmpty()) {
            $message .= ' Bỏ qua ' . $blocked->count() . ' tag đang được gán cho truyện.';
        }

        return redirect()->route('admin.tags.index')->with($deletable->isNotEmpty() ? 'success' : 'error', $message);
    }
}
