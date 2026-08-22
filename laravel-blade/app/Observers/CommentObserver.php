<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Comic;
use Illuminate\Support\Facades\DB;

/**
 * CommentObserver — Tự động duy trì counter cache `comments_count` trên Comic.
 */
class CommentObserver
{
    public function created(Comment $comment): void
    {
        if ($comment->status === Comment::STATUS_APPROVED && $comment->comic_id) {
            DB::table('comics')
                ->where('id', $comment->comic_id)
                ->increment('comments_count');
        }
    }

    public function updated(Comment $comment): void
    {
        if (!$comment->comic_id || !$comment->wasChanged('status')) {
            return;
        }

        $oldStatus = $comment->getOriginal('status');
        $newStatus = $comment->status;

        // Chuyển từ chưa duyệt sang đã duyệt: Tăng counter
        if ($oldStatus !== Comment::STATUS_APPROVED && $newStatus === Comment::STATUS_APPROVED) {
            DB::table('comics')
                ->where('id', $comment->comic_id)
                ->increment('comments_count');
        }

        // Chuyển từ đã duyệt sang spam/pending/rejected: Giảm counter
        if ($oldStatus === Comment::STATUS_APPROVED && $newStatus !== Comment::STATUS_APPROVED) {
            DB::table('comics')
                ->where('id', $comment->comic_id)
                ->where('comments_count', '>', 0)
                ->decrement('comments_count');
        }
    }

    public function deleted(Comment $comment): void
    {
        if ($comment->status === Comment::STATUS_APPROVED && $comment->comic_id) {
            DB::table('comics')
                ->where('id', $comment->comic_id)
                ->where('comments_count', '>', 0)
                ->decrement('comments_count');
        }
    }

    public function restored(Comment $comment): void
    {
        if ($comment->status === Comment::STATUS_APPROVED && $comment->comic_id) {
            DB::table('comics')
                ->where('id', $comment->comic_id)
                ->increment('comments_count');
        }
    }
}
