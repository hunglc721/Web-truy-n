<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    public function __construct(
        protected RatingService $ratingService
    ) {}

    /**
     * Gửi hoặc cập nhật đánh giá cho bộ truyện.
     * POST /api/comics/{comicId}/ratings
     */
    public function store(Request $request, int $comicId): JsonResponse
    {
        $validated = $request->validate([
            'score'  => ['required', 'numeric', 'min:1.0', 'max:5.0'],
            'review' => ['nullable', 'string', 'max:1000'],
        ], [
            'score.required' => 'Vui lòng chọn số sao đánh giá.',
            'score.numeric'  => 'Điểm đánh giá phải là số hợp lệ.',
            'score.min'      => 'Điểm đánh giá tối thiểu là 1.0 sao.',
            'score.max'      => 'Điểm đánh giá tối đa là 5.0 sao.',
            'review.max'     => 'Nhận xét không được vượt quá 1000 ký tự.',
        ]);

        $comic = Comic::findOrFail($comicId);
        $user  = Auth::user();

        $result = $this->ratingService->rate(
            $user,
            $comic,
            (float) $validated['score'],
            $validated['review'] ?? null
        );

        return response()->json([
            'status'        => 'success',
            'message'       => $result['message'],
            'avg_rating'    => $result['avg_rating'],
            'total_ratings' => $result['total_ratings'],
            'is_updated'    => $result['is_updated'],
            'user_score'    => $result['rating']->score,
            'user_review'   => $result['rating']->review,
        ]);
    }

    /**
     * Xóa đánh giá của user đối với bộ truyện.
     * DELETE /api/comics/{comicId}/ratings
     */
    public function destroy(int $comicId): JsonResponse
    {
        $comic = Comic::findOrFail($comicId);
        $user  = Auth::user();

        $result = $this->ratingService->removeRating($user, $comic);

        return response()->json([
            'status'        => 'success',
            'message'       => $result['message'],
            'avg_rating'    => $result['avg_rating'],
            'total_ratings' => $result['total_ratings'],
        ]);
    }

    /**
     * Lấy đánh giá của chính user hiện tại đối với truyện.
     * GET /api/comics/{comicId}/my-rating
     */
    public function userRating(int $comicId): JsonResponse
    {
        $comic  = Comic::findOrFail($comicId);
        $user   = Auth::user();
        $rating = $this->ratingService->getUserRating($user, $comic);

        return response()->json([
            'status'     => 'success',
            'has_rated'  => $rating !== null,
            'score'      => $rating?->score,
            'review'     => $rating?->review,
            'updated_at' => $rating?->updated_at?->toISOString(),
        ]);
    }

    /**
     * Thống kê tổng quan & phân bổ số sao (Công khai).
     * GET /api/comics/{comicId}/ratings/summary
     */
    public function summary(int $comicId): JsonResponse
    {
        $comic     = Comic::findOrFail($comicId);
        $breakdown = $this->ratingService->getRatingBreakdown($comic);

        return response()->json([
            'status' => 'success',
            'data'   => $breakdown,
        ]);
    }

    /**
     * Danh sách nhận xét đánh giá (Công khai, phân trang).
     * GET /api/comics/{comicId}/ratings/reviews
     */
    public function reviews(Request $request, int $comicId): JsonResponse
    {
        $comic   = Comic::findOrFail($comicId);
        $perPage = min(50, max(1, (int) $request->query('per_page', 10)));
        $reviews = $this->ratingService->getReviews($comic, $perPage);

        return response()->json([
            'status' => 'success',
            'data'   => $reviews,
        ]);
    }
}
