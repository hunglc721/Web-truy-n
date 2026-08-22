<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::withCount(['comics', 'followers'])
            ->orderByDesc('comics_count')
            ->paginate(18);

        return view('teams.index', compact('teams'));
    }

    public function show(string $slug)
    {
        $team = Team::where('slug', $slug)
            ->with(['comics' => function ($q) {
                $q->with(['genres', 'latestChapter'])
                  ->orderByDesc('views');
            }])
            ->withCount('followers')
            ->firstOrFail();

        $isFollowed = auth()->check() ? $team->isFollowedBy(auth()->user()) : false;

        return view('teams.show', compact('team', 'isFollowed'));
    }

    public function follow(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'unauthenticated', 'message' => 'Vui lòng đăng nhập.'], 401);
        }

        $team = Team::findOrFail($id);
        $isFollowing = $user->followedTeams()->where('teams.id', $team->id)->exists();

        if ($isFollowing) {
            $user->followedTeams()->detach($team->id);
            $action = 'unfollowed';
            $message = 'Đã hủy theo dõi nhóm dịch.';
        } else {
            $user->followedTeams()->attach($team->id);
            $action = 'followed';
            $message = 'Đã theo dõi nhóm dịch thành công! Bạn sẽ nhận được thông báo khi nhóm ra chương mới.';
        }

        $followersCount = $team->followers()->count();

        return response()->json([
            'status'          => 'success',
            'action'          => $action,
            'is_followed'     => $action === 'followed',
            'followers_count' => $followersCount,
            'message'         => $message,
        ]);
    }
}
