<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng lưu trữ báo cáo lỗi (ảnh hỏng, vi phạm, dịch sai...) từ độc giả.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('comic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained()->cascadeOnDelete();

            $table->unsignedInteger('page_number')->nullable(); // Trang ảnh bị lỗi (1, 2, 3...)
            $table->string('image_url', 1000)->nullable();      // URL ảnh bị lỗi
            $table->string('type', 50)->default('broken_image');// 'broken_image', 'content_error', 'spoiler'
            $table->text('description')->nullable();            // Mô tả thêm của user
            $table->string('status', 30)->default('pending');   // 'pending', 'resolved', 'dismissed'
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['chapter_id', 'page_number']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
