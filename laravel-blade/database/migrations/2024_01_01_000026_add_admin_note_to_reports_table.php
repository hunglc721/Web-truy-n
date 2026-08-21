<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm cột admin_note vào bảng reports để admin lưu ghi chú xử lý sự cố.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->text('admin_note')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('admin_note');
        });
    }
};
