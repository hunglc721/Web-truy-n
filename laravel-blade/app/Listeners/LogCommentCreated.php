<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Models\ActivityLog;

class LogCommentCreated
{
    /**
     * Ghi activity log khi có bình luận mới được đăng.
     *
     * Chỉ log bình luận approved — spam không cần thiết phải hiển thị
     * trong dashboard activity.
     */
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment;

        ActivityLog::record('comment.created', $comment, [
            'comic_id'   => $comment->comic_id,
            'chapter_id' => $comment->chapter_id,
            'status'     => $comment->status,
            'is_reply'   => !is_null($comment->parent_id),
        ]);
    }
}
