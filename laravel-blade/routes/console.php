<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

// ── Artisan Commands ─────────────────────────────────────────────────────────

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/**
 * Xóa toàn bộ cache ứng dụng.
 * Dùng khi deploy hoặc cần force-refresh sau khi sửa data.
 *
 * Usage: php artisan cache:flush-app
 */
Artisan::command('cache:flush-app', function () {
    Cache::flush();
    $this->info('✅ Đã xóa toàn bộ application cache.');
})->purpose('Xóa toàn bộ application cache');

/**
 * Xóa cache của một comic cụ thể theo slug.
 * Dùng khi admin sửa truyện nhưng cache chưa tự expire.
 *
 * Usage: php artisan cache:flush-comic {slug}
 */
Artisan::command('cache:flush-comic {slug}', function (string $slug) {
    Cache::forget("comic.detail.{$slug}");

    // Lấy ID để xóa related cache
    $comic = \App\Models\Comic::where('slug', $slug)->first();
    if ($comic) {
        Cache::forget("comic.related.{$comic->id}");
        Cache::forget("comic.{$comic->id}.chapters_list");
        $this->info("✅ Đã xóa cache cho comic: {$slug} (ID: {$comic->id})");
    } else {
        Cache::forget("comic.detail.{$slug}"); // xóa key dù comic không tồn tại
        $this->warn("⚠️  Comic '{$slug}' không tìm thấy trong DB nhưng cache key đã được xóa.");
    }
})->purpose('Xóa cache của một comic theo slug');

/**
 * Xóa cache recommendation cho toàn bộ users.
 * Dùng sau khi bulk import comics mới hoặc thay đổi thuật toán gợi ý.
 *
 * Usage: php artisan cache:flush-recommendations
 */
Artisan::command('cache:flush-recommendations', function () {
    Cache::forget('recommendations.guest');

    // Xóa cache recommendation cho tất cả users (pattern matching với tags)
    // Với driver file/array: dùng Cache::flush() trong trường hợp khẩn cấp
    // Với Redis: có thể dùng pattern 'recommendations.user.*'
    $count = 0;
    \App\Models\User::select('id')->chunk(500, function ($users) use (&$count) {
        foreach ($users as $user) {
            Cache::forget("recommendations.user.{$user->id}");
            $count++;
        }
    });

    $this->info("✅ Đã xóa cache recommendation cho guest + {$count} users.");
})->purpose('Xóa cache gợi ý truyện cho tất cả users');

/**
 * Kiểm tra trạng thái các chapters đang processing.
 * Dùng để monitor queue worker có đang chạy không.
 *
 * Usage: php artisan queue:status-chapters
 */
Artisan::command('queue:status-chapters', function () {
    $stats = \App\Models\Chapter::select('processing_status', \DB::raw('count(*) as total'))
        ->groupBy('processing_status')
        ->pluck('total', 'processing_status');

    $this->table(
        ['Processing Status', 'Số lượng'],
        $stats->map(fn($count, $status) => [$status, $count])->values()->toArray()
    );

    $failed = \App\Models\Chapter::where('processing_status', 'failed')->count();
    if ($failed > 0) {
        $this->error("❌ Có {$failed} chapter bị lỗi xử lý! Kiểm tra queue:failed.");
    } else {
        $this->info('✅ Không có chapter nào bị lỗi.');
    }
})->purpose('Kiểm tra trạng thái xử lý ảnh của các chapters');

/**
 * Retry tất cả chapters bị lỗi xử lý (processing_status = failed).
 * Re-dispatch ProcessChapterImages job.
 *
 * Usage: php artisan queue:retry-failed-chapters
 */
Artisan::command('queue:retry-failed-chapters', function () {
    $failedChapters = \App\Models\Chapter::with('comic')
        ->where('processing_status', 'failed')
        ->get();

    if ($failedChapters->isEmpty()) {
        $this->info('✅ Không có chapter nào cần retry.');
        return;
    }

    foreach ($failedChapters as $chapter) {
        // Reset về pending để re-queue
        $chapter->update(['processing_status' => 'pending']);

        // Dispatch lại job với pages hiện tại (nếu có URL list cũ)
        $urlList = array_filter(
            $chapter->pages ?? [],
            fn($p) => str_starts_with($p, 'http')
        );

        \App\Jobs\ProcessChapterImages::dispatch(
            $chapter->comic,
            $chapter,
            [],       // tmpPaths trống (file tmp đã bị xóa)
            array_values($urlList)
        )->onQueue('chapter-images');
    }

    $this->info("✅ Đã re-queue {$failedChapters->count()} chapters bị lỗi.");
})->purpose('Retry xử lý ảnh cho các chapters bị lỗi');

// ── Scheduled Tasks ──────────────────────────────────────────────────────────

Schedule::command('queue:retry-failed-chapters')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground();
