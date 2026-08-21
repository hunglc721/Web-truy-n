<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Comic;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminScheduleController extends Controller
{
    public function index()
    {
        $allSchedules = Schedule::with(['comic.latestChapter'])
            ->orderBy('day_of_week')
            ->orderBy('release_time')
            ->get();

        $dayNames = [
            1 => ['key' => 1, 'label' => 'Thứ Hai', 'short' => 'T2'],
            2 => ['key' => 2, 'label' => 'Thứ Ba', 'short' => 'T3'],
            3 => ['key' => 3, 'label' => 'Thứ Tư', 'short' => 'T4'],
            4 => ['key' => 4, 'label' => 'Thứ Năm', 'short' => 'T5'],
            5 => ['key' => 5, 'label' => 'Thứ Sáu', 'short' => 'T6'],
            6 => ['key' => 6, 'label' => 'Thứ Bảy', 'short' => 'T7'],
            0 => ['key' => 0, 'label' => 'Chủ Nhật', 'short' => 'CN'],
        ];

        $todayIndex = now()->dayOfWeek;
        $daysData = [];

        foreach ($dayNames as $dayIndex => $info) {
            $items = $allSchedules->where('day_of_week', $dayIndex)->values();
            $daysData[$dayIndex] = $info + [
                'is_today' => $dayIndex === $todayIndex,
                'count' => $items->where('is_active', true)->count(),
                'schedules' => $items,
            ];
        }

        $comics = Comic::select('id', 'title', 'cover_image')->orderBy('title')->get();

        return view('admin.schedules.index', compact('daysData', 'comics', 'todayIndex'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'comic_id' => 'required|exists:comics,id',
            'day_of_week' => 'required|integer|between:0,6',
            'release_time' => 'required|date_format:H:i',
            'is_active' => 'nullable|boolean',
        ]);

        $schedule = Schedule::updateOrCreate(
            ['comic_id' => $validated['comic_id'], 'day_of_week' => $validated['day_of_week']],
            ['release_time' => $validated['release_time'], 'is_active' => $request->boolean('is_active', true)]
        );

        ActivityLog::record('admin.schedule.saved', $schedule, [
            'comic_id' => $schedule->comic_id,
            'day_of_week' => $schedule->day_of_week,
            'release_time' => $schedule->release_time,
            'is_active' => $schedule->is_active,
            'admin_id' => Auth::id(),
        ]);

        return back()->with('success', 'Đã lưu lịch phát hành.');
    }

    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'comic_id' => 'required|exists:comics,id',
            'day_of_week' => [
                'required', 'integer', 'between:0,6',
                Rule::unique('schedules', 'day_of_week')
                    ->where(fn ($q) => $q->where('comic_id', $request->integer('comic_id')))
                    ->ignore($schedule->id),
            ],
            'release_time' => 'required|date_format:H:i',
            'is_active' => 'nullable|boolean',
        ], [
            'day_of_week.unique' => 'Truyện này đã có lịch ở ngày đã chọn.',
        ]);

        $old = $schedule->only(['comic_id', 'day_of_week', 'release_time', 'is_active']);
        $schedule->update([
            'comic_id' => $validated['comic_id'],
            'day_of_week' => $validated['day_of_week'],
            'release_time' => $validated['release_time'],
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLog::record('admin.schedule.updated', $schedule, [
            'before' => $old,
            'after' => $schedule->only(['comic_id', 'day_of_week', 'release_time', 'is_active']),
            'admin_id' => Auth::id(),
        ]);

        return back()->with('success', 'Đã cập nhật lịch phát hành.');
    }

    public function destroy(Schedule $schedule)
    {
        $comicTitle = $schedule->comic->title ?? 'Truyện';

        ActivityLog::record('admin.schedule.deleted', $schedule, [
            'comic_id' => $schedule->comic_id,
            'day_of_week' => $schedule->day_of_week,
            'admin_id' => Auth::id(),
        ]);

        $schedule->delete();

        return back()->with('success', "Đã xóa lịch phát hành của \"{$comicTitle}\".");
    }
}
