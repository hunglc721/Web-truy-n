<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Banner;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Illuminate\View\View;

class AdminAnalyticsController extends Controller
{
    public function index(): View
    {
        $stats = [
            'comics' => Comic::count(),
            'chapters' => Chapter::count(),
            'users' => User::count(),
            'views' => Comic::sum('views'),
            'comments' => Comment::count(),
            'reports' => Report::count(),
            'banners' => Banner::count(),
            'active_banners' => Banner::active()->count(),
        ];

        $topComics = Comic::query()
            ->with(['genres'])
            ->withCount('chapters')
            ->orderByDesc('views')
            ->take(10)
            ->get();

        $chapterLeaders = Comic::query()
            ->withCount('chapters')
            ->orderByDesc('chapters_count')
            ->take(8)
            ->get();

        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(10)
            ->get();

        $commentStatuses = Comment::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $reportStatuses = Report::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $hotKeywords = \App\Models\SearchKeyword::hot(10)->get();

        return view('admin.analytics.index', compact(
            'stats',
            'topComics',
            'chapterLeaders',
            'recentActivities',
            'commentStatuses',
            'reportStatuses',
            'hotKeywords'
        ));
    }
}
