<?php
// database/migrations/2024_01_01_000015_create_comic_likes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng lưu lượt "Thích" của người dùng đối với từng truyện.
     * Mỗi user chỉ có thể like 1 truyện 1 lần (unique constraint).
     */
    public function up(): void
    {
        Schema::create('comic_likes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('comic_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->timestamp('liked_at')->useCurrent();
            $table->timestamps();

            // Mỗi user chỉ like 1 truyện 1 lần
            $table->unique(['user_id', 'comic_id']);
            $table->index('comic_id'); // index để đếm nhanh
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comic_likes');
    }
};
