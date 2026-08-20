<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration 000020 — Bổ sung composite indexes cho toàn bộ hệ thống.
     *
     * Mục tiêu: phủ kín các slow queries phổ biến nhất, đặc biệt khi data lớn.
     *
     * Nguyên tắc đặt index:
     *  - Equality columns TRƯỚC, range columns SAU trong composite index
     *  - Chỉ index các cột thường xuyên xuất hiện trong WHERE / ORDER BY / JOIN
     *  - Không duplicate index nếu đã có unique constraint (đã implicit index)
     *  - Đặt tên index theo pattern: {table}_{cols}_{type}_idx
     */
    public function up(): void
    {
        // ── 1. comics — bổ sung composite indexes cho trang home & filter ────
        Schema::table('comics', function (Blueprint $table) {
            // Trang home "Latest Updates": WHERE status='ongoing' ORDER BY updated_at DESC
            $table->index(['status', 'updated_at'], 'comics_status_updated_idx');

            // Filter trending + là original: WHERE is_original=1 AND trending_rank IS NOT NULL
            $table->index(['is_original', 'trending_rank'], 'comics_original_trending_idx');

            // Filter featured: WHERE is_featured=1 AND published_at DESC
            $table->index(['is_featured', 'published_at'], 'comics_featured_published_idx');

            // Full-text search chuẩn bị (nếu upgrade sau)
            // Đã có index('slug') qua unique() — bỏ qua
        });

        // ── 2. chapters — bổ sung indexes reader page & admin ────────────────
        Schema::table('chapters', function (Blueprint $table) {
            // Reader: next/prev chapter navigation
            // WHERE comic_id=? AND chapter_number > ? ORDER BY chapter_number ASC
            $table->index(['comic_id', 'chapter_number'], 'chapters_comic_number_idx');

            // Admin panel: sắp xếp theo ngày đăng mới nhất
            // WHERE comic_id=? ORDER BY published_at DESC
            // Note: đã có ['comic_id', 'published_at'] trong migration 000008,
            // bỏ qua để tránh duplicate

            // Lọc chapter miễn phí/trả phí
            $table->index(['comic_id', 'is_free'], 'chapters_comic_free_idx');

            // Theo dõi trạng thái queue xử lý ảnh
            $table->index('processing_status', 'chapters_processing_status_idx');
        });

        // ── 3. reading_histories — bổ sung index cho RecommendationService ──
        Schema::table('reading_histories', function (Blueprint $table) {
            // RecommendationService: WHERE user_id=? AND last_read_at >= ?
            // Note: unique(['user_id', 'comic_id']) đã có — không duplicate
            $table->index(['user_id', 'last_read_at'], 'reading_histories_user_last_read_idx');

            // Thống kê: Truyện nào được đọc nhiều nhất (hot)
            $table->index(['comic_id', 'last_read_at'], 'reading_histories_comic_last_read_idx');
        });

        // ── 4. comic_genre pivot — JOIN performance khi filter theo genre ────
        Schema::table('comic_genre', function (Blueprint $table) {
            // Genre page: WHERE genre_id=? (dùng cùng comic_id join)
            // Foreign key tạo index tự động trên MySQL InnoDB
            // Thêm composite để cover ORDER BY trong genre page
            $table->index(['genre_id', 'comic_id'], 'comic_genre_genre_comic_idx');
        });

        // ── 5. comic_likes — check user đã like chưa ─────────────────────────
        Schema::table('comic_likes', function (Blueprint $table) {
            // WHERE comic_id=? AND user_id=? (toggle like check)
            // unique sẽ được tạo ở bước sau nếu cần; tạm thời index
            $table->index(['comic_id', 'user_id'], 'comic_likes_comic_user_idx');

            // Thống kê: tổng likes của 1 truyện
            $table->index('comic_id', 'comic_likes_comic_idx');
        });

        // ── 6. ratings — avg_rating recalculation ────────────────────────────
        Schema::table('ratings', function (Blueprint $table) {
            // WHERE comic_id=? GROUP BY score (recalculate avg)
            $table->index(['comic_id', 'score'], 'ratings_comic_score_idx');

            // WHERE user_id=? AND comic_id=? (user đã rate chưa)
            $table->index(['user_id', 'comic_id'], 'ratings_user_comic_idx');
        });

        // ── 7. activity_logs — admin dashboard query ──────────────────────────
        Schema::table('activity_logs', function (Blueprint $table) {
            // Admin: lọc log theo user + action + time range
            $table->index(['user_id', 'action', 'created_at'], 'activity_logs_user_action_time_idx');
        });
    }

    public function down(): void
    {
        Schema::table('comics', function (Blueprint $table) {
            $table->dropIndex('comics_status_updated_idx');
            $table->dropIndex('comics_original_trending_idx');
            $table->dropIndex('comics_featured_published_idx');
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->dropIndex('chapters_comic_number_idx');
            $table->dropIndex('chapters_comic_free_idx');
            $table->dropIndex('chapters_processing_status_idx');
        });

        Schema::table('reading_histories', function (Blueprint $table) {
            $table->dropIndex('reading_histories_user_last_read_idx');
            $table->dropIndex('reading_histories_comic_last_read_idx');
        });

        Schema::table('comic_genre', function (Blueprint $table) {
            $table->dropIndex('comic_genre_genre_comic_idx');
        });

        Schema::table('comic_likes', function (Blueprint $table) {
            $table->dropIndex('comic_likes_comic_user_idx');
            $table->dropIndex('comic_likes_comic_idx');
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropIndex('ratings_comic_score_idx');
            $table->dropIndex('ratings_user_comic_idx');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_user_action_time_idx');
        });
    }
};
