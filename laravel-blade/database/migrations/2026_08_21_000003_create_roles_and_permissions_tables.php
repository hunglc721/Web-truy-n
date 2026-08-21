<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
        });

        $now = now();
        $roles = [
            ['name' => 'Member', 'slug' => 'member'],
            ['name' => 'Administrator', 'slug' => 'admin'],
            ['name' => 'Moderator', 'slug' => 'moderator'],
            ['name' => 'Editor', 'slug' => 'editor'],
            ['name' => 'Viewer', 'slug' => 'viewer'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert($role + ['created_at' => $now, 'updated_at' => $now]);
        }

        $permissions = [
            ['dashboard.view', 'Xem dashboard quản trị'],
            ['analytics.view', 'Xem thống kê quản trị'],
            ['comics.view', 'Xem truyện'],
            ['comics.create', 'Tạo truyện'],
            ['comics.update', 'Sửa truyện'],
            ['comics.delete', 'Xóa truyện'],
            ['chapters.view', 'Xem chapter'],
            ['chapters.create', 'Tạo chapter'],
            ['chapters.update', 'Sửa chapter'],
            ['chapters.delete', 'Xóa chapter'],
            ['genres.manage', 'Quản lý thể loại'],
            ['tags.manage', 'Quản lý tag'],
            ['authors.manage', 'Quản lý tác giả'],
            ['users.view', 'Xem thành viên'],
            ['users.manage_role', 'Thay đổi vai trò thành viên'],
            ['users.ban', 'Khóa/mở khóa thành viên'],
            ['comments.view', 'Xem bình luận quản trị'],
            ['comments.moderate', 'Kiểm duyệt bình luận'],
            ['reports.view', 'Xem báo cáo lỗi'],
            ['reports.manage', 'Xử lý báo cáo lỗi'],
            ['schedules.manage', 'Quản lý lịch ra truyện'],
            ['banners.manage', 'Quản lý banner'],
            ['audit.view', 'Xem audit log'],
            ['settings.manage', 'Quản lý cài đặt website'],
            ['permissions.manage', 'Quản lý phân quyền'],
        ];

        foreach ($permissions as [$slug, $description]) {
            DB::table('permissions')->insert([
                'name' => ucwords(str_replace(['.', '_'], ' ', $slug)),
                'slug' => $slug,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleIds = DB::table('roles')->pluck('id', 'slug');
        $permissionIds = DB::table('permissions')->pluck('id', 'slug');

        $grants = [
            'admin' => $permissionIds->keys()->all(),
            'moderator' => [
                'dashboard.view', 'analytics.view', 'users.view', 'users.ban',
                'comments.view', 'comments.moderate', 'reports.view', 'reports.manage', 'audit.view',
            ],
            'editor' => [
                'dashboard.view', 'analytics.view',
                'comics.view', 'comics.create', 'comics.update', 'comics.delete',
                'chapters.view', 'chapters.create', 'chapters.update', 'chapters.delete',
                'genres.manage', 'tags.manage', 'authors.manage', 'schedules.manage', 'banners.manage',
            ],
            'viewer' => [
                'dashboard.view', 'analytics.view', 'comics.view', 'chapters.view',
                'users.view', 'comments.view', 'reports.view', 'audit.view',
            ],
            'member' => [],
        ];

        foreach ($grants as $roleSlug => $slugs) {
            foreach ($slugs as $permissionSlug) {
                DB::table('permission_role')->insert([
                    'role_id' => $roleIds[$roleSlug],
                    'permission_id' => $permissionIds[$permissionSlug],
                ]);
            }
        }

        DB::table('users')->update(['role_id' => $roleIds['member']]);
        DB::table('users')->where('is_admin', true)->update(['role_id' => $roleIds['admin']]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
