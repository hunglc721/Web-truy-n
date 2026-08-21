<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Comic;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminScheduleController extends Controller
{
    /**
     * Danh sách lịch phát hành theo 7 ngày trong tuần (Chủ Nhật - Thứ Bảy).
     */
    public function index()
    {
        // 1. Lấy tất cả lịch phát sóng kèm thông tin truyện và chapter mới nhất
        $allSchedules = Schedule::with(['comic.latestChapter'])
            ->where('is_active', true)
            ->get();

        // 2. Nhóm lịch theo từng ngày trong tuần (0: Chủ Nhật, 1: Thứ Hai ... 6: Thứ Bảy)
        $daysData = [];
        $dayNames = [
            1 => ['key' => 1, 'label' => 'Thứ Hai',   'short' => 'T2', 'name' => 'Monday'],
            2 => ['key' => 2, 'label' => 'Thứ Ba',    'short' => 'T3', 'name' => 'Tuesday'],
            3 => ['key' => 3, 'label' => 'Thứ Tư',    'short' => 'T4', 'name' => 'Wednesday'],
            4 => ['key' => 4, 'label' => 'Thứ Năm',   'short' => 'T5', 'name' => 'Thursday'],
            5 => ['key' => 5, 'label' => 'Thứ Sáu',   'short' => 'T6', 'name' => 'Friday'],
            6 => ['key' => 6, 'label' => 'Thứ Bảy',   'short' => 'T7', 'name' => 'Saturday'],
            0 => ['key' => 0, 'label' => 'Chủ Nhật',  'short' => 'CN', 'name' => 'Sunday'],
        ];

        $todayIndex = now()->dayOfWeek;

        foreach ($dayNames as $dayIndex => $info) {
            $schedulesForDay = $allSchedules->where('day_of_week', $dayIndex);
            $daysData[$dayIndex] = array_merge($info, [
                'is_today'  => $dayIndex === $todayIndex,
                'count'     => $schedulesForDay->count(),
                'schedules' => $schedulesForDay,
            ]);
        }

        // 3. Danh sách truyện để chọn trong Modal
        $comics = Comic::select('id', 'title', 'cover_image')
            ->orderBy('title')
            ->get();

        return view('admin.schedules.index', compact('daysData', 'comics', 'todayIndex'));
    }

    /**
     * Gán hoặc cập nhật ngày phát hành cho bộ truyện.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'comic_id'     => 'required|exists:comics,id',
            'day_of_week'  => 'required|integer|between:0,6',
            'release_time' => 'nullable|string|max:10',
        ]);

        $schedule = Schedule::updateOrCreate(
            [
                'comic_id'    => $validated['comic_id'],
                'day_of_week' => $validated['day_of_week'],
            ],
            [
                'release_time' => $validated['release_time'] ?: '20:00',
                'is_active'    => true,
            ]
        );

        ActivityLog::record('admin.schedule.saved', $schedule, [
            'comic_id'     => $schedule->comic_id,
            'day_of_week'  => $schedule->day_of_week,
            'release_time' => $schedule->release_time,
            'admin_id'     => Auth::id(),
        ]);

        $comic = Comic::find($validated['comic_id']);
        $dayName = Schedule::DAY_FULL_NAMES[$validated['day_of_week']] ?? 'ngày được chọn';

        return redirect()->back()->with('success', "Đã gán lịch phát hành cho truyện \"{$comic->title}\" vào {$dayName} lúc {$schedule->release_time}.");
    }

    /**
     * Xóa lịch phát hành của bộ truyện.
     */
    public function destroy(Schedule $schedule)
    {
        $comicTitle = $schedule->comic->title ?? 'Truyện';
        $schedule->delete();

        ActivityLog::record('admin.schedule.deleted', $schedule, [
            'admin_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', "Đã xóa lịch phát hành của truyện \"{$comicTitle}\".");
    }
}
