<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Models\User;
use App\Notifications\NewChapterNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendChapterFollowerNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(public Chapter $chapter)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $chapter = $this->chapter->fresh(['comic']);

        if (!$chapter || $chapter->processing_status !== 'ready' || !$chapter->isPublished()) {
            $this->chapter->newQuery()->whereKey($this->chapter->id)->update(['followers_notified_at' => null]);
            return;
        }

        User::query()
            ->whereHas('libraries', fn ($q) => $q->where('comic_id', $chapter->comic_id))
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($chapter) {
                foreach ($users as $user) {
                    DB::transaction(function () use ($user, $chapter) {
                        $inserted = DB::table('chapter_notification_receipts')->insertOrIgnore([
                            'user_id' => $user->id,
                            'chapter_id' => $chapter->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        if ($inserted !== 1) {
                            return;
                        }

                        $user->notify(new NewChapterNotification($chapter));

                        DB::table('chapter_notification_receipts')
                            ->where('user_id', $user->id)
                            ->where('chapter_id', $chapter->id)
                            ->update([
                                'delivered_at' => now(),
                                'updated_at' => now(),
                            ]);
                    });
                }
            });
    }

    public function failed(\Throwable $e): void
    {
        $this->chapter->newQuery()
            ->whereKey($this->chapter->id)
            ->update(['followers_notified_at' => null]);
    }
}
