<?php

namespace App\Services;

use App\Jobs\SendChapterFollowerNotifications;
use App\Models\Chapter;

class ChapterNotificationService
{
    public function dispatchIfEligible(Chapter $chapter): bool
    {
        $chapter = $chapter->fresh();

        if (!$chapter
            || $chapter->processing_status !== 'ready'
            || !$chapter->isPublished()
            || $chapter->followers_notified_at !== null) {
            return false;
        }

        $claimed = Chapter::query()
            ->whereKey($chapter->id)
            ->whereNull('followers_notified_at')
            ->where('processing_status', 'ready')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['followers_notified_at' => now()]);

        if ($claimed !== 1) {
            return false;
        }

        try {
            SendChapterFollowerNotifications::dispatch($chapter)->onQueue('notifications');
            return true;
        } catch (\Throwable $e) {
            Chapter::query()->whereKey($chapter->id)->update(['followers_notified_at' => null]);
            throw $e;
        }
    }

    public function dispatchDue(int $limit = 200): int
    {
        $count = 0;

        Chapter::query()
            ->where('processing_status', 'ready')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNull('followers_notified_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (Chapter $chapter) use (&$count) {
                if ($this->dispatchIfEligible($chapter)) {
                    $count++;
                }
            });

        return $count;
    }
}
