<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm processing_status vào chapters để theo dõi trạng thái upload ảnh queue.
     */
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->enum('processing_status', ['pending', 'processing', 'ready', 'failed'])
                  ->default('ready')
                  ->after('is_free')
                  ->comment('Trạng thái xử lý ảnh: pending=chờ, processing=đang xử lý, ready=xong, failed=lỗi');
        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropColumn('processing_status');
        });
    }
};
