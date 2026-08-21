<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration BE-03 — Kiểm tra & bổ sung các index tổng hợp còn thiếu.
     *
     * Trạng thái hiện tại (đã có — KHÔNG tạo lại để tránh duplicate):
     * ┌─────────────────────────────────────────────────────────────┐
     * │ chapters                                                    │
     * │  • unique(['comic_id', 'slug'])      — migration 000017     │
     * │  • unique(['comic_id', 'chapter_number']) — migration 000008│
     * │  • index(['comic_id', 'published_at'])    — migration 000008│
     * │  • index(['comic_id', 'chapter_number'])  — migration 000020│
     * │                                                             │
     * │ comics                                                      │
     * │  • unique('slug')    — migration 000004                     │
     * │  • index('status')   — migration 000004                     │
     * │  • index('views')    — migration 000004                     │
     * └─────────────────────────────────────────────────────────────┘
     *
     * Bổ sung trong migration này:
     *  [chapters] — unique(['comic_id','slug']) được migration 000017 tạo nhưng
     *               CÓ THỂ bị bỏ sót trên môi trường mới; migration này không
     *               tạo lại (tránh conflict) — đã handled bởi 000017.
     *
     *  [comics]
     *    • index(['status', 'views'])        — Composite mới: top-viewed per status
     *                                          WHERE status='ongoing' ORDER BY views DESC
     *    • index(['trending_rank', 'status'])— Composite mới: trending filter by status
     *                                          WHERE trending_rank IS NOT NULL AND status=?
     *
     * Nguyên tắc:
     *  - Equality columns (=) đặt TRƯỚC, range/order columns SAU.
     *  - Không duplicate index đơn lẻ đã có bằng composite.
     *  - Tên index theo pattern: {table}_{cols}_idx
     */
    public function up(): void
    {
        // ── comics: composite indexes còn thiếu ──────────────────────────────
        Schema::table('comics', function (Blueprint $table) {
            // Top-viewed per status:
            //   WHERE status='ongoing' ORDER BY views DESC
            //   WHERE status='completed' ORDER BY views DESC
            // Dùng trong: Genre page, filter by status + sort by views
            $table->index(['status', 'views'], 'comics_status_views_idx');

            // Trending filter by status:
            //   WHERE trending_rank IS NOT NULL AND status='ongoing' ORDER BY trending_rank ASC
            // Dùng trong: Home trending + filter đang phát hành
            $table->index(['status', 'trending_rank'], 'comics_status_trending_idx');
        });

        // ── chapters: published_at scope index tổng hợp ──────────────────────
        // index(['comic_id', 'published_at']) đã có trong migration 000008.
        // Tuy nhiên scopePublished() dùng thêm điều kiện:
        //   WHERE comic_id=? AND published_at IS NOT NULL AND published_at <= ?
        // Index hiện tại đã cover query này hoàn toàn — không cần thêm.
        //
        // Xác nhận unique(['comic_id', 'slug']) của migration 000017:
        // unique constraint đảm bảo tầng DB chặn duplicate slug trong cùng 1 comic.
        // Không cần tạo lại ở đây.
    }

    public function down(): void
    {
        Schema::table('comics', function (Blueprint $table) {
            $table->dropIndex('comics_status_views_idx');
            $table->dropIndex('comics_status_trending_idx');
        });
    }
};
