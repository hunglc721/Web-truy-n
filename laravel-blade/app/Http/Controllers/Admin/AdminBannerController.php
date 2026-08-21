<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AdminBannerController extends Controller
{
    /**
     * Danh sách tất cả banner quảng cáo / slider trang chủ.
     */
    public function index()
    {
        $banners = Banner::orderBy('order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        $stats = [
            'total'     => $banners->count(),
            'active'    => $banners->where('is_active', true)->count(),
            'inactive'  => $banners->where('is_active', false)->count(),
            'expired'   => $banners->filter->is_expired->count(),
            'scheduled' => $banners->filter->is_scheduled->count(),
        ];

        return view('admin.banners.index', compact('banners', 'stats'));
    }

    /**
     * Thêm mới banner.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'     => 'required|string|max:255',
            'image'     => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'image_url' => 'nullable|string|max:1000',
            'link_url'  => 'nullable|string|max:1000',
            'order'     => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'start_at'  => 'nullable|date',
            'end_at'    => 'nullable|date|after_or_equal:start_at',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $validated['image_url'] = $path;
        } elseif (empty($validated['image_url'])) {
            return redirect()->back()->withErrors(['image_url' => 'Vui lòng tải lên file ảnh hoặc nhập URL ảnh banner.'])->withInput();
        }

        $validated['order']     = $validated['order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        $banner = Banner::create($validated);

        Cache::forget('home.banners');

        ActivityLog::record('admin.banner.created', $banner, [
            'admin_id' => Auth::id(),
            'title'    => $banner->title,
        ]);

        return redirect()->route('admin.banners.index')->with('success', "Đã thêm banner \"{$banner->title}\" thành công!");
    }

    /**
     * Cập nhật banner.
     */
    public function update(Request $request, Banner $banner)
    {
        $validated = $request->validate([
            'title'     => 'required|string|max:255',
            'image'     => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'image_url' => 'nullable|string|max:1000',
            'link_url'  => 'nullable|string|max:1000',
            'order'     => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'start_at'  => 'nullable|date',
            'end_at'    => 'nullable|date|after_or_equal:start_at',
        ]);

        if ($request->hasFile('image')) {
            // Xóa file ảnh cũ nếu lưu local
            if (!str_starts_with($banner->image_url, 'http')) {
                Storage::disk('public')->delete($banner->image_url);
            }
            $validated['image_url'] = $request->file('image')->store('banners', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active', $banner->is_active);

        $banner->update($validated);

        Cache::forget('home.banners');

        ActivityLog::record('admin.banner.updated', $banner, [
            'admin_id' => Auth::id(),
            'title'    => $banner->title,
        ]);

        return redirect()->route('admin.banners.index')->with('success', "Đã cập nhật banner \"{$banner->title}\" thành công!");
    }

    /**
     * Bật / Tắt nhanh hiển thị banner.
     */
    public function toggleActive(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);

        Cache::forget('home.banners');

        $statusStr = $banner->is_active ? 'Bật' : 'Tắt';

        ActivityLog::record('admin.banner.toggled', $banner, [
            'admin_id'  => Auth::id(),
            'is_active' => $banner->is_active,
        ]);

        return redirect()->back()->with('success', "Đã {$statusStr} hiển thị banner \"{$banner->title}\".");
    }

    /**
     * Xóa banner.
     */
    public function destroy(Banner $banner)
    {
        $title = $banner->title;

        if (!str_starts_with($banner->image_url, 'http')) {
            Storage::disk('public')->delete($banner->image_url);
        }

        $banner->delete();

        Cache::forget('home.banners');

        ActivityLog::record('admin.banner.deleted', $banner, [
            'admin_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', "Đã xóa banner \"{$title}\".");
    }
}
