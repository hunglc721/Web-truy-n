<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminBroadcastNotification extends Notification
{
    use Queueable;

    public function __construct(public Announcement $announcement)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $icon = match ($this->announcement->severity) {
            'success' => '✅',
            'warning' => '⚠️',
            'emergency' => '🚨',
            default => '🔔',
        };

        return [
            'type' => 'admin_broadcast',
            'severity' => $this->announcement->severity,
            'icon' => $icon,
            'title' => $this->announcement->title,
            'message' => $this->announcement->message,
            'announcement_id' => $this->announcement->id,
            'url' => $this->announcement->link_url ?: route('user.notifications.index'),
        ];
    }
}
