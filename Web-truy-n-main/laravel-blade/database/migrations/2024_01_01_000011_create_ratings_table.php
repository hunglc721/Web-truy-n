<?php
// database/migrations/2024_01_01_000011_create_ratings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng đánh giá — "★ 9.9", "★ 9.8" trên giao diện
     *
     * avg_rating trong bảng comics sẽ được tính từ bảng này.
     * Dùng trigger hoặc Observer để cập nhật comics.avg_rating sau mỗi rating mới.
     */
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('comic_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Thang điểm 1–10 (×10 so với ★ 9.9 trên UI = lưu 99 để tránh float)
            // Hoặc decimal nếu muốn rõ ràng hơn
            $table->decimal('score', 3, 1);    // 1.0 – 10.0

            $table->text('review')->nullable(); // Review text (tuỳ chọn)

            $table->timestamps();

            // 1 user chỉ rate 1 lần mỗi truyện
            $table->unique(['user_id', 'comic_id']);
            $table->index('comic_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
