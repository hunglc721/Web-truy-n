<?php
// app/Http/Controllers/Admin/AdminUserController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('role');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $role = $request->string('role')->toString();
            if ($role === 'admin') {
                $query->where(function ($q) {
                    $q->where('is_admin', true)
                        ->orWhereHas('role', fn ($rq) => $rq->where('slug', 'admin'));
                });
            } else {
                $query->where('is_admin', false)
                    ->whereHas('role', fn ($rq) => $rq->where('slug', $role));
            }
        }

        if ($request->filled('status')) {
            $request->input('status') === 'banned'
                ? $query->whereNotNull('banned_at')
                : $query->whereNull('banned_at');
        }

        $users = $query->withCount(['comments', 'libraries', 'ratings', 'readingHistories'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();
        $stats = [
            'total' => User::count(),
            'admins' => User::where(function ($q) {
                $q->where('is_admin', true)
                    ->orWhereHas('role', fn ($rq) => $rq->where('slug', 'admin'));
            })->count(),
            'members' => User::where('is_admin', false)
                ->whereHas('role', fn ($q) => $q->where('slug', 'member'))
                ->count(),
            'active' => User::whereNull('banned_at')->count(),
            'banned' => User::whereNotNull('banned_at')->count(),
        ];

        return view('admin.users.index', compact('users', 'roles', 'stats'));
    }

    public function updateRole(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Bạn không thể thay đổi vai trò của chính mình.');
        }

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::exists('roles', 'slug')],
        ]);

        $role = Role::where('slug', $validated['role'])->firstOrFail();
        $wasAdmin = $user->isAdmin();

        if ($wasAdmin && $role->slug !== 'admin' && $this->adminCount() <= 1) {
            return back()->with('error', 'Hệ thống phải luôn còn ít nhất một Admin.');
        }

        $oldRole = $user->roleSlug();
        $user->update([
            'role_id' => $role->id,
            'is_admin' => $role->slug === 'admin',
        ]);
        $user->unsetRelation('role');

        ActivityLog::record('admin.user.role_changed', $user, [
            'old_role' => $oldRole,
            'new_role' => $role->slug,
            'changed_by' => Auth::id(),
        ]);

        return back()->with('success', "Đã đổi vai trò của \"{$user->name}\" thành {$role->name}.");
    }

    /**
     * Route cũ được giữ để không làm hỏng link/bookmark trước migration.
     * Chỉ toggle giữa Member và Admin.
     */
    public function toggleRole(User $user)
    {
        $targetRole = $user->isAdmin() ? 'member' : 'admin';
        request()->merge(['role' => $targetRole]);
        return $this->updateRole(request(), $user);
    }

    public function toggleBan(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Bạn không thể tự khóa tài khoản của mình.');
        }

        if ($user->isAdmin()) {
            return back()->with('error', 'Không thể khóa tài khoản Admin.');
        }

        if ($user->banned_at) {
            $user->update(['banned_at' => null]);
            ActivityLog::record('admin.user.unbanned', $user, [
                'unbanned_by' => Auth::id(),
            ]);

            return back()->with('success', "Đã mở khóa tài khoản \"{$user->name}\".");
        }

        $user->update(['banned_at' => now()]);

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        ActivityLog::record('admin.user.banned', $user, [
            'banned_by' => Auth::id(),
            'reason' => 'Manual ban by authorized staff',
        ]);

        return back()->with('success', "Đã khóa tài khoản \"{$user->name}\" và vô hiệu hóa các phiên đăng nhập.");
    }

    public function show(User $user)
    {
        $user->load([
            'role',
            'comments' => fn ($q) => $q->with('comic')->latest()->take(10),
            'libraries' => fn ($q) => $q->with('comic')->latest()->take(10),
            'readingHistories' => fn ($q) => $q->with(['comic', 'chapter'])->latest('last_read_at')->take(10),
        ]);

        $stats = [
            'comments_count' => $user->comments()->count(),
            'libraries_count' => $user->libraries()->count(),
            'ratings_count' => $user->ratings()->count(),
            'history_count' => $user->readingHistories()->count(),
        ];

        return view('admin.users.show', compact('user', 'stats'));
    }

    private function adminCount(): int
    {
        return User::where(function ($q) {
            $q->where('is_admin', true)
                ->orWhereHas('role', fn ($rq) => $rq->where('slug', 'admin'));
        })->count();
    }
}
