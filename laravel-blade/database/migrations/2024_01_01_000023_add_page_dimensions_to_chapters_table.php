<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm cột page_dimensions (JSON) lưu width và height của từng trang ảnh chapter.
     */
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->json('page_dimensions')->nullable()->after('pages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropColumn('page_dimensions');
        });
    }
};
