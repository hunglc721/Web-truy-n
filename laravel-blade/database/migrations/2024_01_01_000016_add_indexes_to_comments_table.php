<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm composite indexes cho bảng comments.
     *
     * Các query thường dùng cần index:
     *  1. Lấy comment approved của 1 truyện, sort mới nhất
     *     → WHERE comic_id = ? AND status = 'approved' ORDER BY created_at DESC
     *  2. Lấy comment của 1 chương cụ thể (reader page)
     *     → WHERE comic_id = ? AND chapter_id = ? AND status = 'approved'
     *  3. Load replies (nested comments)
     *     → WHERE parent_id = ?
     *  4. Trang profile user – lịch sử comment
     *     → WHERE user_id = ?
     *
     * Lưu ý: deleted_at KHÔNG thêm vào composite index vì SoftDeletes
     * thêm `AND deleted_at IS NULL` — MySQL/MariaDB xử lý hiệu quả bằng partial index
     * hoặc để engine tự lọc sau khi dùng composite index.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Query 1: Trang chi tiết truyện – load tất cả bình luận approved
            $table->index(['comic_id', 'status', 'created_at'], 'comments_comic_status_created_idx');

            // Query 2: Trang đọc chương – load bình luận theo chương
            $table->index(['comic_id', 'chapter_id', 'status'], 'comments_comic_chapter_status_idx');

            // Query 3: Load replies
            $table->index(['parent_id'], 'comments_parent_id_idx');

            // Query 4: Trang profile / admin quản lý user
            $table->index(['user_id'], 'comments_user_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_comic_status_created_idx');
            $table->dropIndex('comments_comic_chapter_status_idx');
            $table->dropIndex('comments_parent_id_idx');
            $table->dropIndex('comments_user_id_idx');
        });
    }
};
