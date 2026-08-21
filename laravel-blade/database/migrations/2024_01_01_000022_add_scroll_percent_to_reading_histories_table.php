<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm cột scroll_percent vào bảng reading_histories để ghi nhớ phần trăm vị trí đọc của user.
     */
    public function up(): void
    {
        Schema::table('reading_histories', function (Blueprint $table) {
            $table->decimal('scroll_percent', 5, 2)->default(0.00)->after('chapter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reading_histories', function (Blueprint $table) {
            $table->dropColumn('scroll_percent');
        });
    }
};
