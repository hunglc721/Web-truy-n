<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            
            // Khóa ngoại liên kết tới users, comics, chapters (chapter_id nullable nếu bình luận ở trang chi tiết truyện)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Hỗ trợ bình luận trả lời (nested comments)
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();

            // Nội dung bình luận & số lượt thích
            $table->text('content');
            $table->unsignedInteger('likes_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
