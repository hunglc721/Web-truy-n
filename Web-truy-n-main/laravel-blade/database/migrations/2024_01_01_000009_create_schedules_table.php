<?php
// database/migrations/2024_01_01_000009_create_schedules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng lịch ra tập theo ngày trong tuần
     *
     * Từ trang Schedule:
     *   MON (18 Series), TUE (22), WED (19), THU (26 🔥), FRI (24), SAT (30), SUN (28)
     *   Solo Leveling → THU
     *   Tower of God  → THU
     */
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comic_id')
                  ->constrained()
                  ->cascadeOnDelete();

            /**
             * Ngày trong tuần theo chuẩn PHP date('w'):
             *   0 = Sunday, 1 = Monday, 2 = Tuesday,
             *   3 = Wednesday, 4 = Thursday, 5 = Friday, 6 = Saturday
             */
            $table->tinyInteger('day_of_week');            // 0–6
            $table->time('release_time')->nullable();       // Giờ ra tập (UTC), ví dụ: 08:00:00
            $table->boolean('is_active')->default(true);   // Lịch đang áp dụng

            $table->timestamps();

            // 1 truyện chỉ có 1 lịch cho mỗi ngày
            $table->unique(['comic_id', 'day_of_week']);

            $table->index('day_of_week');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
