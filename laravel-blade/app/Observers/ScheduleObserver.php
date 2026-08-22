<?php

namespace App\Observers;

use App\Models\Schedule;
use Illuminate\Support\Facades\Cache;

/**
 * ScheduleObserver — Tự động xóa cache lịch phát hành khi schedule thay đổi.
 */
class ScheduleObserver
{
    public function saved(Schedule $schedule): void
    {
        $this->invalidate();
    }

    public function deleted(Schedule $schedule): void
    {
        $this->invalidate();
    }

    protected function invalidate(): void
    {
        Cache::forget('schedule.day_counts');
        for ($i = 0; $i <= 6; $i++) {
            Cache::forget("schedule.day.{$i}");
        }
    }
}
