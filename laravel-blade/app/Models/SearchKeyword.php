<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\VietnameseHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SearchKeyword extends Model
{
    protected $fillable = [
        'keyword',
        'keyword_normalized',
        'hits',
        'results_count',
        'last_searched_at',
    ];

    protected $casts = [
        'hits'             => 'integer',
        'results_count'    => 'integer',
        'last_searched_at' => 'datetime',
    ];

    /**
     * Scope lấy top từ khoá hot nhất.
     */
    public function scopeHot($query, int $limit = 10)
    {
        return $query->orderByDesc('hits')
                     ->orderByDesc('last_searched_at')
                     ->limit($limit);
    }

    /**
     * Ghi nhận 1 lượt tìm kiếm từ khoá (atomic & debounce).
     */
    public static function record(string $keyword, int $resultsCount = 0): void
    {
        $clean = trim($keyword);
        if (mb_strlen($clean) < 2) {
            return;
        }

        $normalized = VietnameseHelper::removeAccents($clean);
        if (empty($normalized)) {
            return;
        }

        try {
            DB::statement("
                INSERT INTO search_keywords (keyword, keyword_normalized, hits, results_count, last_searched_at, created_at, updated_at)
                VALUES (?, ?, 1, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    hits = hits + 1,
                    results_count = VALUES(results_count),
                    last_searched_at = VALUES(last_searched_at),
                    updated_at = VALUES(updated_at)
            ", [
                $clean,
                $normalized,
                $resultsCount,
                now(),
                now(),
                now(),
            ]);
        } catch (\Throwable) {
            // Fallback cho SQLite / standard Eloquent updateOrCreate
            try {
                $record = static::where('keyword_normalized', $normalized)->first();
                if ($record) {
                    $record->increment('hits', 1, [
                        'results_count'    => $resultsCount,
                        'last_searched_at' => now(),
                    ]);
                } else {
                    static::create([
                        'keyword'            => $clean,
                        'keyword_normalized' => $normalized,
                        'hits'               => 1,
                        'results_count'      => $resultsCount,
                        'last_searched_at'   => now(),
                    ]);
                }
            } catch (\Throwable) {}
        }
    }
}
