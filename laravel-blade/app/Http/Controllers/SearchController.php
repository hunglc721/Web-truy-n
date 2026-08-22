<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * API Live search autocomplete.
     * GET /api/search/live?q=solo&limit=8
     */
    public function live(Request $request): JsonResponse
    {
        $keyword = (string) $request->query('q', '');
        $limit   = min(20, max(1, (int) $request->query('limit', 8)));

        $results = $this->searchService->liveSearch($keyword, $limit);

        return response()->json([
            'status' => 'success',
            'query'  => $keyword,
            'count'  => $results->count(),
            'data'   => $results,
        ]);
    }

    /**
     * API Lấy danh sách từ khoá tìm kiếm hot nhất.
     * GET /api/search/hot?limit=10
     */
    public function hot(Request $request): JsonResponse
    {
        $limit = min(30, max(1, (int) $request->query('limit', 10)));
        $keywords = $this->searchService->getHotKeywords($limit);

        return response()->json([
            'status' => 'success',
            'data'   => $keywords,
        ]);
    }

    /**
     * API Tìm kiếm & Lọc nâng cao.
     * GET /api/search/advanced
     */
    public function advanced(Request $request): JsonResponse
    {
        $params = $request->only([
            'q',
            'genres',
            'genre_mode',
            'exclude_genres',
            'tags',
            'status',
            'country',
            'year',
            'is_original',
            'min_rating',
            'min_chapters',
            'sort',
            'per_page',
        ]);

        $paginator = $this->searchService->advancedSearch($params);

        return response()->json([
            'status' => 'success',
            'data'   => $paginator,
        ]);
    }
}
