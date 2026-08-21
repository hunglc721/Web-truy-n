<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\ComicLike;
use App\Models\Rating;
use App\Models\ReadingHistory;
use App\Services\UserStatisticsService;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function __construct(
        protected UserStatisticsService $statisticsService
    ) {}

    public function dashboard(Request $request)
    {
        $user = $request->user();

        $overview = $this->statisticsService->getOverview($user);
        $favoriteGenres = $this->statisticsService->getFavoriteGenres($user);
        $badges = $this->statisticsService->getBadges($user);
        $weekly = $this->statisticsService->getWeeklyActivity($user);

        $recentHistory = $user->readingHistories()
            ->with(['comic:id,title,slug,cover_image', 'chapter:id,comic_id,slug,chapter_number,title'])
            ->take(6)
            ->get();

        $recentComments = $user->comments()
            ->with(['comic:id,title,slug', 'chapter:id,comic_id,slug,chapter_number'])
            ->take(5)
            ->get();

        $recentLikes = $user->likes()
            ->with('comic:id,title,slug,cover_image,status,avg_rating')
            ->latest('liked_at')
            ->take(6)
            ->get();

        return view('user.dashboard', compact(
            'user',
            'overview',
            'favoriteGenres',
            'badges',
            'weekly',
            'recentHistory',
            'recentComments',
            'recentLikes'
        ));
    }

    public function history(Request $request)
    {
        $histories = ReadingHistory::with([
                'comic:id,title,slug,cover_image,status,avg_rating',
                'chapter:id,comic_id,slug,chapter_number,title',
            ])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_read_at')
            ->paginate(20);

        return view('user.history', compact('histories'));
    }

    public function likes(Request $request)
    {
        $likes = ComicLike::with('comic:id,title,slug,cover_image,status,avg_rating')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('liked_at')
            ->paginate(20);

        return view('user.likes', compact('likes'));
    }

    public function comments(Request $request)
    {
        $comments = Comment::with([
                'comic:id,title,slug',
                'chapter:id,comic_id,slug,chapter_number,title',
                'parent.user:id,name',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('user.comments', compact('comments'));
    }

    public function ratings(Request $request)
    {
        $ratings = Rating::with('comic:id,title,slug,cover_image,status,avg_rating')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('user.ratings', compact('ratings'));
    }
}
