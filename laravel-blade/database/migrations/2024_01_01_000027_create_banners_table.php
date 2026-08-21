<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng quản lý Banner quảng cáo / Hero Slider trang chủ.
     */
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');                          // Tiêu đề banner
            $table->string('image_url', 1000);                // URL ảnh hoặc path lưu trữ
            $table->string('link_url', 1000)->nullable();     // URL liên kết khi nhấp vào banner
            $table->unsignedInteger('order')->default(0);     // Thứ tự hiển thị (nhỏ đứng trước)
            $table->boolean('is_active')->default(true);      // Bật / tắt hiển thị
            $table->dateTime('start_at')->nullable();         // Ngày bắt đầu hiển thị
            $table->dateTime('end_at')->nullable();           // Ngày hết hạn tự động ẩn
            $table->unsignedBigInteger('clicks_count')->default(0); // Lượt click thống kê
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'order']);
            $table->index(['start_at', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
