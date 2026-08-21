<?php

namespace App\Jobs;

use App\Services\RecommendationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InvalidateUserRecommendation implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected int $userId,
        protected int $debounceSeconds = 60
    ) {}

    /**
     * Thực thi xóa/làm mới cache recommendation cho user kèm debounce.
     */
    public function handle(RecommendationService $recommendationService): void
    {
        $recommendationService->invalidateForUser($this->userId, false, $this->debounceSeconds);
    }
}
