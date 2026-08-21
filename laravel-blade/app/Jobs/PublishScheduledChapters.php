<?php

namespace App\Jobs;

use App\Models\Chapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PublishScheduledChapters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Thực thi quét và kích hoạt phát hành các chapter đã đến giờ hẹn (published_at <= now()).
     * Xóa cache trang chủ (home.latest, home.trending) và cache gợi ý khi có chapter mới được publish.
     */
    public function handle(): int
    {
        // 1. Tìm các chapter vừa đến giờ phát hành (trong vòng 10 phút gần nhất)
        $now = now();
        $recentThreshold = $now->copy()->subMinutes(10);

        $justPublishedChapters = Chapter::where('published_at', '<=', $now)
            ->where('published_at', '>=', $recentThreshold)
            ->with('comic')
            ->get();

        if ($justPublishedChapters->isEmpty()) {
            return 0;
        }

        // 2. Invalidate cache trang chủ và cache truyện tương ứng
        Cache::forget('home.latest');
        Cache::forget('home.trending');
        Cache::forget('recommendations.guest');

        foreach ($justPublishedChapters as $chap) {
            if ($chap->comic) {
                Cache::forget("comic.detail.{$chap->comic->slug}");
                Cache::forget("comic.{$chap->comic_id}.chapters_list");
                Cache::forget("comic.related.{$chap->comic_id}");
            }
        }

        Log::info("PublishScheduledChapters: Đã kích hoạt phát hành và làm mới cache cho {$justPublishedChapters->count()} chapters.");

        return $justPublishedChapters->count();
    }
}
