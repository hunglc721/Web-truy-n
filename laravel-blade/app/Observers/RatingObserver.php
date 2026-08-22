<?php

namespace App\Observers;

use App\Models\Rating;

/**
 * RatingObserver — Tự động cập nhật `avg_rating`, `rating_avg`, `total_ratings`, `rating_count` trên Comic.
 */
class RatingObserver
{
    public function created(Rating $rating): void
    {
        $rating->comic?->recalculateRating();
    }

    public function updated(Rating $rating): void
    {
        if ($rating->wasChanged('score')) {
            $rating->comic?->recalculateRating();
        }
    }

    public function deleted(Rating $rating): void
    {
        $rating->comic?->recalculateRating();
    }

    public function restored(Rating $rating): void
    {
        $rating->comic?->recalculateRating();
    }
}
