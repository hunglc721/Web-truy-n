<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng activity_logs — ghi lại hành động người dùng & admin.
     *
     * action examples: 'comment.created', 'comic.liked', 'comic.followed',
     *                  'admin.user.banned', 'admin.user.role_changed'
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // user_id nullable — hỗ trợ log guest action nếu cần sau này
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Hành động: 'comment.created', 'comic.liked', ...
            $table->string('action', 80)->index();

            // Polymorphic subject (comment, comic, chapter, user...)
            $table->nullableMorphs('subject'); // subject_type + subject_id + index

            // Dữ liệu bổ sung (JSON): comic_id, chapter_id, old/new value...
            $table->json('payload')->nullable();

            $table->string('ip_address', 45)->nullable(); // hỗ trợ IPv6

            $table->timestamp('created_at')->useCurrent()->index();
            // Không cần updated_at — log là immutable
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
