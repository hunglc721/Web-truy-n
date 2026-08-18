<?php
// database/migrations/2024_01_01_000008_create_chapters_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng chương truyện
     *
     * Từ giao diện: "Ch.200", "Ch.590", "Ch.185", "2h ago", "3h ago"...
     * Schedule page: "Ch.200 Released", "Ch.590 Released"
     */
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comic_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // ── Thông tin chương ─────────────────────────────────────────
            $table->unsignedInteger('chapter_number');      // 200, 590, 185...
            $table->string('title')->nullable();             // "Shadow Monarch's Battle" (tuỳ chọn)
            $table->string('slug')->nullable();              // cho URL đẹp

            // ── Nội dung ─────────────────────────────────────────────────
            $table->json('pages')->nullable();               // Mảng URL các trang ảnh
            $table->text('content')->nullable();             // Hoặc text (light novel)

            // ── Thống kê ─────────────────────────────────────────────────
            $table->unsignedBigInteger('views')->default(0); // Lượt xem chương

            // ── Thời gian ────────────────────────────────────────────────
            $table->timestamp('published_at')->nullable();   // "2h ago" = now() - 2h
            $table->boolean('is_free')->default(true);       // Có tính phí không

            $table->timestamps();
            $table->softDeletes();

            // Mỗi truyện không có 2 chương cùng số
            $table->unique(['comic_id', 'chapter_number']);

            // Index để lấy chương mới nhất nhanh
            $table->index(['comic_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
