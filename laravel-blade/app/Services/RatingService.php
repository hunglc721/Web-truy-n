<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Comic;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RatingService
{
    public function __construct(
        protected RecommendationService $recommendationService
    ) {}

    /**
     * Gửi hoặc cập nhật đánh giá (sao & review) cho một bộ truyện.
     *
     * @param  User         $user
     * @param  Comic        $comic
     * @param  float        $score    Điểm đánh giá từ 1.0 đến 5.0
     * @param  string|null  $review   Nội dung nhận xét (tùy chọn)
     * @return array{status: string, message: string, rating: Rating, avg_rating: float, total_ratings: int, is_updated: bool}
     *
     * @throws InvalidArgumentException Khi điểm số không hợp lệ (ngoài 1.0 - 5.0)
     */
    public function rate(User $user, Comic $comic, float $score, ?string $review = null): array
    {
        // 1. Kiểm tra ràng buộc điểm số
        if ($score < 1.0 || $score > 5.0) {
            throw new InvalidArgumentException('Điểm đánh giá phải nằm trong khoảng từ 1.0 đến 5.0 sao.');
        }

        // Làm tròn đến 1 chữ số thập phân
        $roundedScore = round($score, 1);

        return DB::transaction(function () use ($user, $comic, $roundedScore, $review) {
            $existingRating = Rating::where('user_id', $user->id)
                ->where('comic_id', $comic->id)
                ->first();

            $isUpdated = $existingRating !== null;

            $rating = Rating::updateOrCreate(
                [
                    'user_id'  => $user->id,
                    'comic_id' => $comic->id,
                ],
                [
                    'score'  => $roundedScore,
                    'review' => $review !== null ? trim($review) : null,
                ]
            );

            // Cập nhật lại điểm trung bình của truyện
            $comic->recalculateRating();
            $comic->refresh();

            // Ghi nhật ký hoạt động
            ActivityLog::record(
                ActivityLog::ACTION_COMIC_RATED,
                $comic,
                [
                    'score'      => $roundedScore,
                    'has_review' => !empty($review),
                    'is_updated' => $isUpdated,
                ]
            );

            // Invalidate recommendation cache cho người dùng
            $this->recommendationService->invalidateForUser($user->id);

            return [
                'status'        => 'success',
                'message'       => $isUpdated ? 'Đã cập nhật đánh giá thành công!' : 'Đã gửi đánh giá thành công!',
                'rating'        => $rating,
                'avg_rating'    => (float) $comic->avg_rating,
                'total_ratings' => (int) $comic->total_ratings,
                'is_updated'    => $isUpdated,
            ];
        });
    }

    /**
     * Xóa đánh giá của người dùng khỏi bộ truyện.
     *
     * @param  User   $user
     * @param  Comic  $comic
     * @return array{status: string, message: string, avg_rating: float, total_ratings: int}
     */
    public function removeRating(User $user, Comic $comic): array
    {
        return DB::transaction(function () use ($user, $comic) {
            $rating = Rating::where('user_id', $user->id)
                ->where('comic_id', $comic->id)
                ->first();

            if ($rating) {
                $oldScore = $rating->score;
                $rating->delete();

                // Cập nhật lại điểm trung bình
                $comic->recalculateRating();
                $comic->refresh();

                // Ghi nhật ký
                ActivityLog::record(
                    ActivityLog::ACTION_COMIC_UNRATED,
                    $comic,
                    ['previous_score' => $oldScore]
                );

                $this->recommendationService->invalidateForUser($user->id);
            }

            return [
                'status'        => 'success',
                'message'       => 'Đã xóa đánh giá của bạn.',
                'avg_rating'    => (float) $comic->avg_rating,
                'total_ratings' => (int) $comic->total_ratings,
            ];
        });
    }

    /**
     * Lấy đánh giá hiện tại của người dùng cho truyện này.
     */
    public function getUserRating(User $user, Comic $comic): ?Rating
    {
        return Rating::where('user_id', $user->id)
            ->where('comic_id', $comic->id)
            ->first();
    }

    /**
     * Lấy thông tin thống kê phân bổ số sao (Breakdown / Histogram).
     *
     * @param  Comic  $comic
     * @return array{
     *     avg_rating: float,
     *     total_ratings: int,
     *     stars: array<int, array{count: int, percentage: float}>
     * }
     */
    public function getRatingBreakdown(Comic $comic): array
    {
        $ratings = Rating::where('comic_id', $comic->id)->get();
        $total = $ratings->count();

        // Khởi tạo histogram 1 -> 5 sao
        $stars = [
            5 => ['count' => 0, 'percentage' => 0.0],
            4 => ['count' => 0, 'percentage' => 0.0],
            3 => ['count' => 0, 'percentage' => 0.0],
            2 => ['count' => 0, 'percentage' => 0.0],
            1 => ['count' => 0, 'percentage' => 0.0],
        ];

        if ($total > 0) {
            foreach ($ratings as $r) {
                // Điểm làm tròn về số nguyên gần nhất (vd 4.5 -> 5, 4.2 -> 4)
                $starBucket = (int) max(1, min(5, round($r->score)));
                $stars[$starBucket]['count']++;
            }

            foreach ($stars as $star => $data) {
                $stars[$star]['percentage'] = round(($data['count'] / $total) * 100, 1);
            }
        }

        return [
            'avg_rating'    => (float) ($comic->avg_rating ?? 0.0),
            'total_ratings' => $total,
            'stars'         => $stars,
        ];
    }

    /**
     * Lấy danh sách các nhận xét (reviews) có kèm nội dung chữ.
     */
    public function getReviews(Comic $comic, int $perPage = 10): LengthAwarePaginator
    {
        return Rating::with(['user:id,name'])
            ->where('comic_id', $comic->id)
            ->whereNotNull('review')
            ->where('review', '!=', '')
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }
}
