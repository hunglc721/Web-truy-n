<?php
// database/migrations/2024_01_01_000010_create_libraries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thư viện người dùng — "Add to Library" button trên trang Originals
     *
     * Lưu: truyện user đã thêm vào thư viện + chương đang đọc tới đâu
     */
    public function up(): void
    {
        Schema::create('libraries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('comic_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Tiến độ đọc
            $table->foreignId('last_read_chapter_id')
                  ->nullable()
                  ->constrained('chapters')
                  ->nullOnDelete();

            // Trạng thái theo dõi
            $table->enum('status', [
                'reading',      // Đang đọc
                'completed',    // Đã xong
                'on_hold',      // Tạm dừng
                'dropped',      // Bỏ đọc
                'plan_to_read', // Sắp đọc
            ])->default('reading');

            $table->timestamp('added_at')->useCurrent();  // Thời gian thêm vào lib

            $table->timestamps();

            // 1 user chỉ add 1 lần mỗi truyện
            $table->unique(['user_id', 'comic_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libraries');
    }
};
