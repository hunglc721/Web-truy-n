<?php

namespace App\Console\Commands;

use App\Models\Comic;
use App\Models\ComicRecommendation;
use App\Models\Library;
use App\Models\ReadingHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComputeRecommendationsCommand extends Command
{
    protected $signature = 'comics:compute-recommendations {--min-shared=1 : Số lượng người dùng tương tác chung tối thiểu}';
    protected $description = 'Tính toán ma trận đồng xuất hiện (Item-based Collaborative Filtering) cho toàn bộ truyện';

    public function handle(): int
    {
        $this->info('Bắt đầu tính toán Item-based Collaborative Filtering Recommendations...');
        $minShared = (int) $this->option('min-shared');

        // 1. Lấy danh sách tương tác (User ID -> Comic ID) từ Library (Bookmark) và ReadingHistory
        $libraryPairs = Library::query()
            ->select('user_id', 'comic_id')
            ->distinct()
            ->get();

        $historyPairs = ReadingHistory::query()
            ->select('user_id', 'comic_id')
            ->distinct()
            ->get();

        $comicUsers = []; // [comic_id => [user_id => true, ...]]
        $allComicIds = Comic::pluck('id')->all();

        foreach ($libraryPairs as $pair) {
            $comicUsers[$pair->comic_id][$pair->user_id] = true;
        }

        foreach ($historyPairs as $pair) {
            $comicUsers[$pair->comic_id][$pair->user_id] = true;
        }

        $activeComicIds = array_keys($comicUsers);
        $totalComics = count($activeComicIds);
        $this->info("Tìm thấy {$totalComics} truyện có dữ liệu tương tác người dùng.");

        $recommendationRows = [];
        $now = now();

        // 2. Tính toán ma trận độ tương đồng Jaccard giữa từng cặp truyện
        for ($i = 0; $i < $totalComics; $i++) {
            $comicA = $activeComicIds[$i];
            $usersA = array_keys($comicUsers[$comicA]);
            $countA = count($usersA);

            if ($countA === 0) continue;

            $similarities = [];

            for ($j = 0; $j < $totalComics; $j++) {
                if ($i === $j) continue;
                $comicB = $activeComicIds[$j];
                $usersB = array_keys($comicUsers[$comicB]);
                $countB = count($usersB);

                if ($countB === 0) continue;

                $intersection = count(array_intersect($usersA, $usersB));

                if ($intersection >= $minShared) {
                    $union = $countA + $countB - $intersection;
                    $score = $union > 0 ? round($intersection / $union, 4) : 0;

                    if ($score > 0) {
                        $similarities[] = [
                            'comic_id' => $comicA,
                            'recommended_comic_id' => $comicB,
                            'score' => $score,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }

            // Sắp xếp giảm dần theo điểm tương đồng và lấy top 12
            usort($similarities, fn($a, $b) => $b['score'] <=> $a['score']);
            $topSimilarities = array_slice($similarities, 0, 12);

            foreach ($topSimilarities as $row) {
                $recommendationRows[] = $row;
            }
        }

        // 3. Cập nhật bảng comic_recommendations trong Database Transaction
        DB::transaction(function () use ($recommendationRows) {
            ComicRecommendation::truncate();

            if (!empty($recommendationRows)) {
                // Chunk insert 500 rows per batch
                foreach (array_chunk($recommendationRows, 500) as $chunk) {
                    ComicRecommendation::insert($chunk);
                }
            }
        });

        $totalInserted = count($recommendationRows);
        $this->info("Đã hoàn tất tính toán! Đã cập nhật {$totalInserted} cặp gợi ý truyện.");

        return Command::SUCCESS;
    }
}
