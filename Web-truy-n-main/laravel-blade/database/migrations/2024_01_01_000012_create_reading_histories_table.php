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
        Schema::create('reading_histories', function (Blueprint $table) {
            $table->id();
            
            // Khóa ngoại liên kết tới bảng users, comics, chapters
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            
            // Thời điểm đọc gần nhất
            $table->timestamp('last_read_at')->useCurrent();
            $table->timestamps();

            // Mỗi user chỉ có 1 bản ghi lịch sử cho 1 bộ truyện (cập nhật chapter mới nhất)
            $table->unique(['user_id', 'comic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_histories');
    }
};
