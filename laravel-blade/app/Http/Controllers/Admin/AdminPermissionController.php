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
        $order = ['admin', 'moderator', 'editor', 'viewer'];

        $roles = Role::with('permissions')
            ->whereIn('slug', $order)
            ->get()
            ->sortBy(fn ($role) => array_search($role->slug, $order, true))
            ->values();

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
