<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AdminBroadcastNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendAdminBroadcastNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(public Announcement $announcement)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $announcement = $this->announcement->fresh();
        if (!$announcement || !$announcement->send_to_inbox) {
            return;
        }

        $query = User::query()->orderBy('id');

        match ($announcement->audience) {
            'role' => $query->whereHas('role', fn ($q) => $q->where('slug', $announcement->role_slug)),
            'user' => $query->whereKey($announcement->target_user_id),
            'guests' => $query->whereRaw('1 = 0'),
            default => null,
        };

        $query->chunkById(500, function ($users) use ($announcement) {
            Notification::send($users, new AdminBroadcastNotification($announcement));
        });
    }
}
