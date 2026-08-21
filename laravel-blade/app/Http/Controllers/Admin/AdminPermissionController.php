<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')
            ->whereIn('slug', ['admin', 'moderator', 'editor', 'viewer'])
            ->orderByRaw("FIELD(slug, 'admin', 'moderator', 'editor', 'viewer')")
            ->get();

        // SQLite không hỗ trợ FIELD(), nên fallback sort ở Collection nếu query lỗi là không đáng.
        // Dùng sortBy để giữ test SQLite tương thích.
        $roles = $roles->sortBy(fn ($role) => array_search($role->slug, ['admin', 'moderator', 'editor', 'viewer'], true))->values();

        $permissions = Permission::orderBy('slug')->get();

        return view('admin.permissions.index', compact('roles', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        if (in_array($role->slug, ['admin', 'member'], true)) {
            return back()->with('error', 'Quyền của Admin/Member không chỉnh trực tiếp tại ma trận này.');
        }

        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $oldPermissions = $role->permissions()->pluck('permissions.slug')->all();
        $role->permissions()->sync($validated['permissions'] ?? []);
        $newPermissions = $role->permissions()->pluck('permissions.slug')->all();

        ActivityLog::record('admin.permissions.updated', $role, [
            'role' => $role->slug,
            'old_permissions' => $oldPermissions,
            'new_permissions' => $newPermissions,
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', "Đã cập nhật quyền cho {$role->name}.");
    }
}
