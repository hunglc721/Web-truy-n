<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\UserStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserStatisticsController extends Controller
{
    public function __construct(
        protected UserStatisticsService $statisticsService
    ) {}

    /**
     * Lấy tổng quan thống kê đọc truyện & cấp bậc.
     * GET /api/user/statistics/overview
     */
    public function overview(): JsonResponse
    {
        $user = Auth::user();
        $data = $this->statisticsService->getOverview($user);

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Lấy danh sách thể loại yêu thích & phân bổ.
     * GET /api/user/statistics/genres
     */
    public function genres(): JsonResponse
    {
        $user   = Auth::user();
        $genres = $this->statisticsService->getFavoriteGenres($user);

        return response()->json([
            'status' => 'success',
            'data'   => $genres,
        ]);
    }

    /**
     * Lấy danh sách huy hiệu và thành tích.
     * GET /api/user/statistics/badges
     */
    public function badges(): JsonResponse
    {
        $user   = Auth::user();
        $badges = $this->statisticsService->getBadges($user);

        return response()->json([
            'status' => 'success',
            'data'   => $badges,
        ]);
    }

    /**
     * Lấy biểu đồ hoạt động đọc 7 ngày gần nhất.
     * GET /api/user/statistics/weekly
     */
    public function weekly(): JsonResponse
    {
        $user   = Auth::user();
        $weekly = $this->statisticsService->getWeeklyActivity($user);

        return response()->json([
            'status' => 'success',
            'data'   => $weekly,
        ]);
    }

    /**
     * Xuất dữ liệu đọc truyện cá nhân dạng JSON.
     * GET /api/user/statistics/export
     */
    public function export(): JsonResponse
    {
        $user   = Auth::user();
        $export = $this->statisticsService->exportUserData($user);

        return response()->json($export, 200, [
            'Content-Disposition' => 'attachment; filename="webcomics-history-backup-' . $user->id . '.json"',
        ]);
    }
}
