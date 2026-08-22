<?php

namespace App\Observers;

use App\Models\ComicLike;
use Illuminate\Support\Facades\DB;

/**
 * ComicLikeObserver — Tự động duy trì counter cache `likes_count` trên Comic.
 */
class ComicLikeObserver
{
    public function created(ComicLike $comicLike): void
    {
        if ($comicLike->comic_id) {
            DB::table('comics')
                ->where('id', $comicLike->comic_id)
                ->increment('likes_count');
        }
    }

    public function deleted(ComicLike $comicLike): void
    {
        if ($comicLike->comic_id) {
            DB::table('comics')
                ->where('id', $comicLike->comic_id)
                ->where('likes_count', '>', 0)
                ->decrement('likes_count');
        }
    }
}
