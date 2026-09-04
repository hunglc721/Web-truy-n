<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;

class ComicReleaseMetaController extends Controller
{
    public function show(Comic $comic): JsonResponse
    {
        $comic->load([
            'schedules' => fn ($query) => $query->where('is_active', true)->orderBy('day_of_week'),
            'tags:id,name,slug,color',
            'latestChapter:id,comic_id,chapter_number,title,slug,published_at',
        ]);

        $viDays = [
            0 => 'Chủ Nhật',
            1 => 'Thứ Hai',
            2 => 'Thứ Ba',
            3 => 'Thứ Tư',
            4 => 'Thứ Năm',
            5 => 'Thứ Sáu',
            6 => 'Thứ Bảy',
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'status' => $comic->status,
                'chapter_count' => $comic->chapters()->published()->count(),
                'latest_chapter' => $comic->latestChapter ? [
                    'number' => $comic->latestChapter->chapter_number,
                    'title' => $comic->latestChapter->title,
                    'published_at' => $comic->latestChapter->published_at?->toIso8601String(),
                    'time_ago' => $comic->latestChapter->time_ago,
                    'url' => route('chapters.show', [$comic->slug, $comic->latestChapter->slug]),
                ] : null,
                'schedules' => $comic->schedules->map(fn (Schedule $schedule) => [
                    'day_of_week' => $schedule->day_of_week,
                    'day_name' => $viDays[(int) $schedule->day_of_week] ?? (Schedule::DAY_FULL_NAMES[$schedule->day_of_week] ?? 'Đang cập nhật'),
                ])->values(),
                'tags' => $comic->tags->map(fn ($tag) => [
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'url' => route('tags.show', $tag->slug),
                ])->values(),
            ],
        ]);
    }
}
