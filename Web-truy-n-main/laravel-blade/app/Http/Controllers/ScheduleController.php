<?php
// app/Http/Controllers/ScheduleController.php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Schedule;
use Illuminate\Http\Request;

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
        | Kết quả: [0 => 5, 1 => 18, 4 => 26, ...]
        |──────────────────────────────────────────────────────────
        */
        $dayCounts = Schedule::where('is_active', true)
            ->selectRaw('day_of_week, COUNT(*) as total')
            ->groupBy('day_of_week')
            ->pluck('total', 'day_of_week');   // ['day_of_week' => count]

        /*
        |──────────────────────────────────────────────────────────
        | COMICS của ngày được chọn
        | Lấy qua bảng schedules → join comics
        |──────────────────────────────────────────────────────────
        */
        $comics = Comic::with([
            'genres',
            'latestChapter',
            'tags',
            'authors',
        ])
        ->whereHas('schedules', fn($q) => $q->where('day_of_week', $selectedDay)->where('is_active', true))
        ->orderByDesc('avg_rating')
        ->get();

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
