<?php

namespace App\Notifications;

use App\Models\Chapter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewChapterNotification extends Notification
{
    use Queueable;

    public function __construct(public Chapter $chapter)
    {
        $this->chapter->loadMissing('comic');
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $comic = $this->chapter->comic;

        return [
            'type' => 'new_chapter',
            'icon' => '📖',
            'title' => ($comic?->title ?? 'Truyện bạn theo dõi') . ' có chapter mới',
            'message' => $this->chapter->label . ($this->chapter->title ? ' - ' . $this->chapter->title : '') . ' vừa được phát hành.',
            'comic_id' => $comic?->id,
            'comic_title' => $comic?->title,
            'comic_cover' => $comic?->cover_image,
            'chapter_id' => $this->chapter->id,
            'chapter_number' => $this->chapter->chapter_number,
            'url' => $comic ? route('chapters.show', [
                'comicSlug' => $comic->slug,
                'chapterSlug' => $this->chapter->slug,
            ]) : route('home'),
        ];
    }
}
