<?php
// app/Http/Controllers/Admin/AdminUserController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    /**
     * Danh sách tất cả thành viên, hỗ trợ tìm kiếm và lọc.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Tìm kiếm theo tên hoặc email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Lọc theo vai trò
        if ($request->filled('role')) {
            $query->where('is_admin', $request->input('role') === 'admin');
        }

        // Lọc theo trạng thái khóa
        if ($request->filled('status')) {
            if ($request->input('status') === 'banned') {
                $query->whereNotNull('banned_at');
            } else {
                $query->whereNull('banned_at');
            }
        }

        $users = $query->withCount(['comments', 'libraries'])
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString(); // Giữ query string khi phân trang

        return view('admin.users.index', compact('users'));
    }

    /**
     * Phân quyền: Toggle Admin / User thường.
     * Không thể tự tước quyền admin của chính mình.
     */
    public function toggleRole(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Bạn không thể thay đổi quyền của chính mình.');
        }

        $user->update(['is_admin' => !$user->is_admin]);

        $role = $user->is_admin ? 'Admin' : 'User';
        return redirect()->route('admin.users.index')
            ->with('success', "Đã đổi quyền của \"{$user->name}\" thành {$role}.");
    }

    /**
     * Khóa / Mở khóa tài khoản người dùng (Ban / Unban).
     * Admin không thể tự khóa mình.
     */
    public function toggleBan(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Bạn không thể tự khóa tài khoản của mình.');
        }

        if ($user->isAdmin()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Không thể khóa tài khoản Admin.');
        }

        if ($user->banned_at) {
            // Mở khóa
            $user->update(['banned_at' => null]);
            return redirect()->route('admin.users.index')
                ->with('success', "Đã mở khóa tài khoản \"{$user->name}\".");
        } else {
            // Khóa tài khoản
            $user->update(['banned_at' => now()]);
            return redirect()->route('admin.users.index')
                ->with('success', "Đã khóa tài khoản \"{$user->name}\".");
        }
    }

    /**
     * Xem chi tiết 1 thành viên.
     */
    public function show(User $user)
    {
        $user->load(['comments' => function ($q) {
            $q->with('comic')->latest()->take(10);
        }, 'libraries' => function ($q) {
            $q->with('comic')->latest()->take(10);
        }, 'readingHistories' => function ($q) {
            $q->with(['comic', 'chapter'])->take(10);
        }]);

        $stats = [
            'comments_count'  => $user->comments()->count(),
            'libraries_count' => $user->libraries()->count(),
            'ratings_count'   => $user->ratings()->count(),
            'history_count'   => $user->readingHistories()->count(),
        ];

        return view('admin.users.show', compact('user', 'stats'));
    }
}
