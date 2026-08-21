<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->text('message');
            $table->string('severity', 20)->default('info'); // info|success|warning|emergency
            $table->string('audience', 30)->default('all'); // all|guests|authenticated|role|user
            $table->string('role_slug', 50)->nullable();
            $table->string('link_url', 500)->nullable();
            $table->boolean('show_banner')->default(true);
            $table->boolean('send_to_inbox')->default(false);
            $table->boolean('is_dismissible')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index(['audience', 'role_slug']);
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->timestamp('followers_notified_at')->nullable()->after('published_at');
            $table->index(['processing_status', 'published_at', 'followers_notified_at'], 'chapters_notification_due_idx');
        });

        if (Schema::hasTable('permissions')) {
            $now = now();
            DB::table('permissions')->updateOrInsert(
                ['slug' => 'notifications.manage'],
                [
                    'name' => 'Notifications Manage',
                    'description' => 'Tạo và quản lý thông báo hệ thống/khẩn cấp',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $permissionId = DB::table('permissions')->where('slug', 'notifications.manage')->value('id');
            $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

            if ($permissionId && $adminRoleId) {
                DB::table('permission_role')->updateOrInsert([
                    'role_id' => $adminRoleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $permissionId = DB::table('permissions')->where('slug', 'notifications.manage')->value('id');
            if ($permissionId) {
                DB::table('permission_role')->where('permission_id', $permissionId)->delete();
                DB::table('permissions')->where('id', $permissionId)->delete();
            }
        }

        Schema::table('chapters', function (Blueprint $table) {
            $table->dropIndex('chapters_notification_due_idx');
            $table->dropColumn('followers_notified_at');
        });

        Schema::dropIfExists('announcements');
        Schema::dropIfExists('notifications');
    }
};