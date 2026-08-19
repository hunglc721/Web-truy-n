<?php

namespace App\Http\Controllers;

use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;

class RecommendationController extends Controller
{
    public function __construct(
        protected RecommendationService $recommendationService
    ) {}

    /**
     * GET /api/recommendations
     * Trả về danh sách truyện gợi ý dạng JSON.
     * Authenticated → personalized; Guest → trending.
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $comics = $user
            ? $this->recommendationService->forUser($user)
            : $this->recommendationService->forGuest();

        return response()->json([
            'status' => 'success',
            'source' => $user ? 'personalized' : 'trending',
            'comics' => $comics->map(fn($c) => [
                'id'            => $c->id,
                'title'         => $c->title,
                'slug'          => $c->slug,
                'cover_image'   => $c->cover_image,
                'avg_rating'    => $c->avg_rating,
                'views'         => $c->formatted_views,
                'genres'        => $c->genres->pluck('name'),
                'latest_chapter'=> $c->latestChapter->first()?->label,
            ]),
        ]);
    }
}
