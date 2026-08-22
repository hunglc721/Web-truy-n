<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Helpers\VietnameseHelper;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bổ sung các cột tìm kiếm nâng cao cho bảng comics
        Schema::table('comics', function (Blueprint $table) {
            if (!Schema::hasColumn('comics', 'title_normalized')) {
                $table->string('title_normalized')->nullable()->after('title');
            }
            if (!Schema::hasColumn('comics', 'alt_titles')) {
                $table->text('alt_titles')->nullable()->after('title_normalized');
            }
            if (!Schema::hasColumn('comics', 'alt_titles_normalized')) {
                $table->text('alt_titles_normalized')->nullable()->after('alt_titles');
            }
            if (!Schema::hasColumn('comics', 'country')) {
                $table->string('country', 20)->default('OTHER')->after('is_original');
            }
            if (!Schema::hasColumn('comics', 'released_year')) {
                $table->unsignedSmallInteger('released_year')->nullable()->after('country');
            }

            $table->index('title_normalized', 'comics_title_normalized_idx');
            $table->index('country', 'comics_country_idx');
            $table->index('released_year', 'comics_released_year_idx');
        });

        // 2. Tạo bảng search_keywords để lưu lịch sử và thống kê từ khoá hot
        if (!Schema::hasTable('search_keywords')) {
            Schema::create('search_keywords', function (Blueprint $table) {
                $table->id();
                $table->string('keyword');
                $table->string('keyword_normalized')->index();
                $table->unsignedBigInteger('hits')->default(1);
                $table->unsignedInteger('results_count')->default(0);
                $table->timestamp('last_searched_at')->useCurrent()->index();
                $table->timestamps();

                $table->unique('keyword_normalized');
            });
        }

        // 3. Backfill dữ liệu title_normalized cho các comics hiện có
        try {
            $comics = DB::table('comics')->select('id', 'title', 'is_original')->get();
            foreach ($comics as $c) {
                $normalized = VietnameseHelper::removeAccents($c->title);
                $country = 'OTHER';
                if (stripos($c->title, 'manhwa') !== false || stripos($c->title, 'solo leveling') !== false || stripos($c->title, 'reader') !== false) {
                    $country = 'KR';
                } elseif (stripos($c->title, 'one piece') !== false || stripos($c->title, 'jujutsu') !== false) {
                    $country = 'JP';
                } elseif ($c->is_original) {
                    $country = 'VN';
                }

                DB::table('comics')->where('id', $c->id)->update([
                    'title_normalized' => $normalized,
                    'country'          => $country,
                ]);
            }

            // Seed các từ khoá hot mẫu
            $sampleKeywords = [
                ['keyword' => 'Solo Leveling', 'hits' => 1250, 'results_count' => 1],
                ['keyword' => 'One Piece', 'hits' => 980, 'results_count' => 1],
                ['keyword' => 'Omniscient Reader', 'hits' => 840, 'results_count' => 1],
                ['keyword' => 'Manhwa', 'hits' => 760, 'results_count' => 10],
                ['keyword' => 'Hành Động', 'hits' => 690, 'results_count' => 8],
                ['keyword' => 'Tu Tiên', 'hits' => 540, 'results_count' => 6],
                ['keyword' => 'Trùng Sinh', 'hits' => 430, 'results_count' => 5],
                ['keyword' => 'Hài Hước', 'hits' => 380, 'results_count' => 7],
            ];

            foreach ($sampleKeywords as $kw) {
                DB::table('search_keywords')->insertOrIgnore([
                    'keyword'            => $kw['keyword'],
                    'keyword_normalized' => VietnameseHelper::removeAccents($kw['keyword']),
                    'hits'               => $kw['hits'],
                    'results_count'      => $kw['results_count'],
                    'last_searched_at'   => now(),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }
        } catch (\Throwable) {}
    }

    public function down(): void
    {
        Schema::dropIfExists('search_keywords');

        Schema::table('comics', function (Blueprint $table) {
            if (Schema::hasColumn('comics', 'title_normalized')) {
                $table->dropIndex('comics_title_normalized_idx');
                $table->dropColumn('title_normalized');
            }
            if (Schema::hasColumn('comics', 'alt_titles')) {
                $table->dropColumn('alt_titles');
            }
            if (Schema::hasColumn('comics', 'alt_titles_normalized')) {
                $table->dropColumn('alt_titles_normalized');
            }
            if (Schema::hasColumn('comics', 'country')) {
                $table->dropIndex('comics_country_idx');
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('comics', 'released_year')) {
                $table->dropIndex('comics_released_year_idx');
                $table->dropColumn('released_year');
            }
        });
    }
};
