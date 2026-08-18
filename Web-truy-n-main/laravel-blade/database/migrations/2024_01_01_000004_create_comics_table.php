<?php
// database/migrations/2024_01_01_000004_create_comics_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comics', function (Blueprint $table) {
            $table->id();

            // ── Thông tin cơ bản ──────────────────────────────────────────
            $table->string('title');                      // "Solo Leveling"
            $table->string('slug')->unique();              // "solo-leveling" — dùng cho URL
            $table->string('cover_image');                 // URL hoặc path ảnh bìa
            $table->text('description')->nullable();       // Mô tả/synopsis

            // ── Phân loại ──────────────────────────────────────────────────
            $table->enum('status', [
                'ongoing',      // Đang ra
                'completed',    // Hoàn thành
                'hiatus',       // Tạm dừng
                'cancelled',    // Đã hủy
            ])->default('ongoing');

            $table->boolean('is_original')->default(false);  // WebComics Original?
            $table->boolean('is_featured')->default(false);  // Spotlight/Editor's Pick?

            // ── Thống kê ──────────────────────────────────────────────────
            $table->unsignedBigInteger('views')->default(0);     // Lượt xem tổng
            $table->decimal('avg_rating', 3, 1)->default(0.0);  // ★ 9.9 — cập nhật sau mỗi rating
            $table->unsignedInteger('total_ratings')->default(0);
            $table->unsignedInteger('trending_rank')->nullable(); // Xếp hạng trending (1,2,3...)

            // ── Thông tin xuất bản ────────────────────────────────────────
            $table->date('published_at')->nullable();  // Ngày ra mắt

            $table->timestamps();
            $table->softDeletes(); // Cho phép ẩn truyện thay vì xóa hẳn

            // Index cho các cột thường dùng trong WHERE / ORDER BY
            $table->index('status');
            $table->index('is_original');
            $table->index('is_featured');
            $table->index('trending_rank');
            $table->index('avg_rating');
            $table->index('views');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comics');
    }
};
