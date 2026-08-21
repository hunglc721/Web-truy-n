<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function __construct(
        protected RecommendationService $recommendationService
    ) {}

    /**
     * GET /api/recommendations
     *
     * Trả về danh sách truyện gợi ý dạng JSON chuẩn RESTful.
     *
     * Query Parameters:
     *   - limit:    int (mặc định 6, tối đa 24)
     *   - comic_id: int (tuỳ chọn — tìm truyện tương tự theo comic này)
     *
     * SECURITY: user_id KHÔNG được nhận từ query param — luôn lấy từ auth().
     * Điều này ngăn client tùy ý giả mạo identity để lấy recommendations của user khác.
     *
     * Responses:
     *   - Nếu có ?comic_id → Trả về similar comics (genre + tag signal)
     *   - Nếu User đã đăng nhập → Personalized recommendations (genre + tag signal)
     *   - Nếu là Guest → Trending / Top Rated recommendations
     */
    public function index(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->query('limit', 6), 1), 24);
        $comicId = $request->query('comic_id');

        // user_id luôn lấy từ auth() — KHÔNG bao giờ nhận từ query param
        $user = auth()->user();

        $source = 'trending';
        $comics = collect();

        if ($comicId) {
            $comic = Comic::find($comicId);
            if ($comic) {
                $comics = $this->recommendationService->forComic($comic, $limit);
                $source = 'similar';
            } else {
                // comic_id không tồn tại → fallback về personalized / trending
                $comics = $user
                    ? $this->recommendationService->forUser($user, $limit)
                    : $this->recommendationService->forGuest($limit);
                $source = $user ? 'personalized' : 'trending';
            }
        } elseif ($user) {
            $comics = $this->recommendationService->forUser($user, $limit);
            $source = 'personalized';
        } else {
            $comics = $this->recommendationService->forGuest($limit);
            $source = 'trending';
        }

        return response()->json([
            'status' => 'success',
            'source' => $source,
            'count'  => $comics->count(),
            'comics' => $comics->map(function (Comic $comic) {
                return [
                    'id'             => $comic->id,
                    'title'          => $comic->title,
                    'slug'           => $comic->slug,
                    'url'            => route('comics.show', $comic->slug),
                    'cover_image'    => $comic->cover_image,
                    'description'    => \Illuminate\Support\Str::limit($comic->description, 120),
                    'status'         => $comic->status,
                    'avg_rating'     => (float) $comic->avg_rating,
                    'views'          => $comic->formatted_views ?? number_format($comic->views),
                    'raw_views'      => $comic->views,
                    'genres'         => $comic->genres->pluck('name')->toArray(),
                    'tags'           => $comic->tags->pluck('name')->toArray(),
                    'authors'        => $comic->authors->pluck('name')->toArray(),
                    'latest_chapter' => $comic->latestChapter?->label ?? ($comic->chapters_count ? "Ch.{$comic->chapters_count}" : null),
                ];
            }),
        ]);
    }
}
