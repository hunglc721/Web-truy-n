<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    /**
     * Bộ lọc tìm kiếm truyện đa tiêu chí (Multi-criteria Filter)
     * Supports:
     *   ?genres[]=action&genres[]=fantasy (hoặc ?genre=action,fantasy)
     *   ?status=ongoing | completed | all
     *   ?sort=hot | rating | latest
     */
    public function index(Request $request)
    {
        // 1. Xử lý Lọc theo Thể loại (Hỗ trợ chọn nhiều thể loại cùng lúc)
        $selectedGenres = [];

        if ($request->has('genres') && is_array($request->input('genres'))) {
            $selectedGenres = array_filter($request->input('genres'), fn($g) => $g !== 'all');
        } elseif ($request->has('genre') && $request->input('genre') !== 'all') {
            $selectedGenres = array_filter(explode(',', $request->input('genre')));
        }

        $status = $request->input('status', 'all');
        $sortBy = $request->input('sort', 'hot');

        // Lấy danh sách tất cả Thể loại từ CSDL
        $genres = Genre::orderBy('name')->get();

        // 2. Xây dựng Query tìm kiếm với Eager Loading & Count Chapters
        $query = Comic::with([
            'genres',
            'latestChapter',
            'authors',
            'tags',
        ])->withCount('chapters');

        // Lọc theo nhiều thể loại (nếu có chọn)
        if (!empty($selectedGenres)) {
            foreach ($selectedGenres as $slug) {
                $query->whereHas('genres', function ($g) use ($slug) {
                    $g->where('slug', $slug);
                });
            }
        }

        // Lọc theo Trạng thái truyện (ONGOING / COMPLETED)
        if ($status && $status !== 'all') {
            $query->where('status', strtoupper($status));
        }

        // Sắp xếp kết quả (Top Views / Top Rated / Latest Upload)
        match ($sortBy) {
            'rating' => $query->orderByDesc('avg_rating')->orderByDesc('views'),
            'latest' => $query->withMax('chapters', 'published_at')
                               ->orderByDesc('chapters_max_published_at'),
            default  => $query->orderByDesc('views'), // 'hot' = Top lượt xem
        };

        // Phân trang 12 truyện/trang và giữ lại tham số Query String khi sang trang mới
        $comics = $query->paginate(12)->withQueryString();

        // Danh sách object thể loại đang active (để hiển thị tiêu đề)
        $activeGenres = !empty($selectedGenres)
            ? Genre::whereIn('slug', $selectedGenres)->get()
            : collect([]);

        // Giữ tương thích ngược với tham số đơn ?genre=action
        $genreSlug = count($selectedGenres) === 1 ? $selectedGenres[0] : (empty($selectedGenres) ? 'all' : 'multi');

        return view('genres', compact(
            'comics',
            'genres',
            'selectedGenres',
            'activeGenres',
            'genreSlug',
            'status',
            'sortBy'
        ));
    }
}
