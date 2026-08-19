<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm unique constraint (comic_id, slug) cho chapters.
     *
     * Bước 1: Auto-fix duplicate slugs nếu DB đã có data
     *   → Thêm suffix -v2, -v3 cho các slug trùng trong cùng comic
     * Bước 2: Thêm unique index
     *
     * Lưu ý: SoftDeletes – các bản ghi deleted_at IS NOT NULL cũng
     * tham gia unique constraint; thêm suffix để tránh conflict kể cả
     * với bản ghi đã xóa mềm.
     */
    public function up(): void
    {
        // ─── Bước 1: Fix duplicate slugs trước khi add unique ────────────
        $duplicates = DB::table('chapters')
            ->select('comic_id', 'slug')
            ->whereNotNull('slug')
            ->groupBy('comic_id', 'slug')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            // Lấy tất cả chapter có cùng (comic_id, slug) — bỏ qua bản ghi đầu tiên
            $rows = DB::table('chapters')
                ->where('comic_id', $dup->comic_id)
                ->where('slug', $dup->slug)
                ->orderBy('id')
                ->skip(1) // Giữ nguyên bản đầu tiên
                ->pluck('id');

            $suffix = 2;
            foreach ($rows as $id) {
                // Tìm suffix chưa tồn tại
                do {
                    $newSlug = $dup->slug . '-v' . $suffix;
                    $exists  = DB::table('chapters')
                        ->where('comic_id', $dup->comic_id)
                        ->where('slug', $newSlug)
                        ->exists();
                    if ($exists) $suffix++;
                } while ($exists);

                DB::table('chapters')->where('id', $id)->update(['slug' => $newSlug]);
                $suffix++;
            }
        }

        // ─── Bước 2: Thêm unique constraint ──────────────────────────────
        Schema::table('chapters', function (Blueprint $table) {
            $table->unique(['comic_id', 'slug'], 'chapters_comic_id_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropUnique('chapters_comic_id_slug_unique');
        });
    }
};
