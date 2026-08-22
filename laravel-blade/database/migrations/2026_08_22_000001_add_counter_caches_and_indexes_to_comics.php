<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration thêm các cột Counter Cache và Indexes cho bảng comics.
     * Hỗ trợ scale lên 10.000+ bộ truyện mà không bị nghẽn bởi COUNT/AVG queries.
     */
    public function up(): void
    {
        Schema::table('comics', function (Blueprint $table) {
            if (!Schema::hasColumn('comics', 'likes_count')) {
                $table->unsignedInteger('likes_count')->default(0)->after('views');
            }
            if (!Schema::hasColumn('comics', 'comments_count')) {
                $table->unsignedInteger('comments_count')->default(0)->after('likes_count');
            }
            if (!Schema::hasColumn('comics', 'rating_avg')) {
                $table->decimal('rating_avg', 3, 1)->default(0.0)->after('avg_rating');
            }
            if (!Schema::hasColumn('comics', 'rating_count')) {
                $table->unsignedInteger('rating_count')->default(0)->after('total_ratings');
            }
            if (!Schema::hasColumn('comics', 'views_count')) {
                $table->unsignedBigInteger('views_count')->default(0)->after('views');
            }

            // Index cho các cột counter để sort siêu nhanh
            $table->index('likes_count', 'comics_likes_count_idx');
            $table->index('comments_count', 'comics_comments_count_idx');
            $table->index('rating_avg', 'comics_rating_avg_idx');
            $table->index('views_count', 'comics_views_count_idx');
        });

        // ── BACKFILL DỮ LIỆU ĐẾM CHO DỮ LIỆU HIỆN TẠI ─────────────────────────
        try {
            // 1. Đồng bộ rating & views có sẵn
            DB::statement('UPDATE comics SET rating_avg = avg_rating, rating_count = total_ratings, views_count = views');

            // 2. Backfill likes_count từ bảng comic_likes
            $likes = DB::table('comic_likes')
                ->select('comic_id', DB::raw('COUNT(*) as total'))
                ->groupBy('comic_id')
                ->get();

            foreach ($likes as $item) {
                DB::table('comics')->where('id', $item->comic_id)->update(['likes_count' => $item->total]);
            }

            // 3. Backfill comments_count từ bảng comments (chỉ đếm approved)
            $comments = DB::table('comments')
                ->select('comic_id', DB::raw('COUNT(*) as total'))
                ->where('status', 'approved')
                ->groupBy('comic_id')
                ->get();

            foreach ($comments as $item) {
                DB::table('comics')->where('id', $item->comic_id)->update(['comments_count' => $item->total]);
            }
        } catch (\Throwable $e) {
            // Log fallback if DB statement encounters driver variance
            Log::warning('Counter cache backfill note: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        Schema::table('comics', function (Blueprint $table) {
            if (Schema::hasColumn('comics', 'likes_count')) {
                $table->dropIndex('comics_likes_count_idx');
                $table->dropColumn('likes_count');
            }
            if (Schema::hasColumn('comics', 'comments_count')) {
                $table->dropIndex('comics_comments_count_idx');
                $table->dropColumn('comments_count');
            }
            if (Schema::hasColumn('comics', 'rating_avg')) {
                $table->dropIndex('comics_rating_avg_idx');
                $table->dropColumn('rating_avg');
            }
            if (Schema::hasColumn('comics', 'rating_count')) {
                $table->dropColumn('rating_count');
            }
            if (Schema::hasColumn('comics', 'views_count')) {
                $table->dropIndex('comics_views_count_idx');
                $table->dropColumn('views_count');
            }
        });
    }
};
