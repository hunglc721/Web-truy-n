<?php
// app/Http/Controllers/ScheduleController.php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        /*
        |──────────────────────────────────────────────────────────
        | DAY PARAM:
        |   /schedule        → today (now()->dayOfWeek)
        |   /schedule?day=4  → Thursday (0=Sun … 6=Sat)
        |──────────────────────────────────────────────────────────
        */
        $selectedDay = (int) $request->input('day', now()->dayOfWeek);
        // clamp 0–6
        $selectedDay = max(0, min(6, $selectedDay));

        /*
        |──────────────────────────────────────────────────────────
        | SUMMARY BAR — đếm số series theo từng ngày trong tuần
        | Cache 15 phút — Bị invalidate bởi ScheduleObserver & ChapterObserver
        | Kết quả: [0 => 5, 1 => 18, 4 => 26, ...]
        |──────────────────────────────────────────────────────────
        */
        $dayCounts = Cache::remember('schedule.day_counts', 900, function () {
            return Schedule::where('is_active', true)
                ->selectRaw('day_of_week, COUNT(*) as total')
                ->groupBy('day_of_week')
                ->pluck('total', 'day_of_week');
        });

        /*
        |──────────────────────────────────────────────────────────
        | COMICS của ngày được chọn
        | Cache 15 phút theo ngày — Bị invalidate bởi ScheduleObserver & ChapterObserver
        | Lấy qua bảng schedules → join comics
        |──────────────────────────────────────────────────────────
        */
        $comics = Cache::remember("schedule.day.{$selectedDay}", 900, function () use ($selectedDay) {
            return Comic::with([
                'genres',
                'latestChapter',
                'tags',
                'authors',
            ])
            ->whereHas('schedules', fn($q) => $q->where('day_of_week', $selectedDay)->where('is_active', true))
            ->orderByDesc('avg_rating')
            ->get();
        });

        // Dữ liệu 7 ngày để render Day Selector Bar
        $days = collect(range(0, 6))->map(fn($d) => [
            'day'     => $d,
            'name'    => Schedule::DAY_NAMES[$d],           // "MON", "TUE"...
            'full'    => Schedule::DAY_FULL_NAMES[$d],      // "Monday"...
            'count'   => $dayCounts->get($d, 0),
            'active'  => $d === $selectedDay,
            'is_today'=> $d === now()->dayOfWeek,
        ]);

        $selectedDayName = Schedule::DAY_FULL_NAMES[$selectedDay] ?? 'Today';

        return view('schedule', compact('comics', 'days', 'selectedDay', 'selectedDayName'));
    }
}

