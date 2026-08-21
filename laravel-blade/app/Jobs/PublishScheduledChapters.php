<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Services\ChapterNotificationService;
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

    public function handle(): int
    {
        $now = now();
        $recentThreshold = $now->copy()->subMinutes(10);

        $justPublishedChapters = Chapter::where('published_at', '<=', $now)
            ->where('published_at', '>=', $recentThreshold)
            ->with('comic')
            ->get();

        if ($justPublishedChapters->isEmpty()) {
            return 0;
        }

        Cache::forget('home.latest');
        Cache::forget('home.trending');
        Cache::forget('recommendations.guest');

        $notificationService = app(ChapterNotificationService::class);

        foreach ($justPublishedChapters as $chap) {
            if ($chap->comic) {
                Cache::forget("comic.detail.{$chap->comic->slug}");
                Cache::forget("comic.{$chap->comic_id}.chapters_list");
                Cache::forget("comic.related.{$chap->comic_id}");
            }

            if ($chap->processing_status === 'ready') {
                $notificationService->dispatchIfEligible($chap);
            }
        }

        Log::info("PublishScheduledChapters: Đã kích hoạt phát hành, làm mới cache và kiểm tra thông báo cho {$justPublishedChapters->count()} chapters.");

        return $justPublishedChapters->count();
    }
}
