<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('story_publishing_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Thông tin người gửi / tác giả / nhóm dịch
            $table->string('creator_name', 150);
            $table->string('email', 150);
            $table->string('phone_or_social', 150)->nullable();
            $table->string('team_name', 150)->nullable();
            $table->string('experience_level', 50)->default('beginner'); // beginner, experienced, professional, group
            
            // Thông tin tác phẩm muốn đăng
            $table->string('story_title', 200);
            $table->string('story_original_title', 200)->nullable();
            $table->string('story_type', 50)->default('translation'); // translation, original, novel
            $table->json('genres')->nullable();
            $table->string('story_status', 50)->default('ongoing'); // ongoing, completed, translating
            $table->text('summary');
            $table->string('sample_link', 500)->nullable();
            $table->string('cover_image_path', 500)->nullable();
            $table->string('sample_file_path', 500)->nullable();
            $table->text('note')->nullable();
            
            // Trạng thái thẩm định
            $table->string('status', 50)->default('pending'); // pending, reviewing, approved, rejected
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('story_type');
            $table->index('created_at');
        });

        // Add permission
        if (Schema::hasTable('permissions')) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name'        => 'Quản lý duyệt đơn đăng truyện',
                'slug'        => 'story_requests.manage',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Assign to admin role
            $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
            if ($adminRoleId && $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id'       => $adminRoleId,
                    'permission_id' => $permissionId,
                ]);
            }

            // Assign to editor role
            $editorRoleId = DB::table('roles')->where('slug', 'editor')->value('id');
            if ($editorRoleId && $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id'       => $editorRoleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $permId = DB::table('permissions')->where('slug', 'story_requests.manage')->value('id');
            if ($permId) {
                DB::table('permission_role')->where('permission_id', $permId)->delete();
                DB::table('permissions')->where('id', $permId)->delete();
            }
        }

        Schema::dropIfExists('story_publishing_requests');
    }
};
