<?php

namespace App\Http\Controllers;

use App\Helpers\VietnameseHelper;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\SearchKeyword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GenreController extends Controller
{
    /**
     * Bộ lọc tìm kiếm truyện đa tiêu chí (Multi-criteria Filter & Search)
     * Supports:
     *   ?q=keyword (tự động normalize tiếng Việt)
     *   ?genres[]=action&genres[]=fantasy (hoặc ?genre=action,fantasy)
     *   ?exclude_genres[]=horror
     *   ?country=JP | KR | CN | VN | all
     *   ?status=ongoing | completed | hiatus | all
     *   ?min_chapters=10 | 50 | 100
     *   ?sort=hot | rating | latest | alphabetical | trending
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $selectedGenres = [];
        if ($request->has('genres') && is_array($request->input('genres'))) {
            $selectedGenres = array_filter($request->input('genres'), fn ($g) => $g !== 'all');
        } elseif ($request->has('genre') && $request->input('genre') !== 'all') {
            $selectedGenres = array_filter(explode(',', $request->input('genre')));
        }

        $excludeGenres = [];
        if ($request->has('exclude_genres')) {
            $input = $request->input('exclude_genres');
            if (is_array($input)) {
                $excludeGenres = array_filter($input, fn ($g) => !empty($g) && $g !== 'none');
            } else {
                $excludeGenres = array_filter(explode(',', $input));
            }
        }

        $country = strtolower((string) $request->input('country', 'all'));
        $status = strtolower((string) $request->input('status', 'all'));
        $minChapters = (int) $request->input('min_chapters', 0);
        $sortBy = (string) $request->input('sort', 'hot');

        $genres = Cache::remember('all_genres', 3600, fn () => Genre::orderBy('name')->get());

        $query = Comic::query()
            ->whereHas('chapters', fn ($chapterQuery) => $chapterQuery->published())
            ->with(['genres', 'latestChapter', 'authors', 'tags', 'teams'])
            ->withCount(['chapters' => fn ($chapQ) => $chapQ->published()]);

        if ($q !== '') {
            $normalized = VietnameseHelper::removeAccents($q);
            $escapedOrig = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
            $escapedNorm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $normalized);

            $query->where(function ($subQ) use ($escapedOrig, $escapedNorm) {
                $subQ->where('title', 'like', "%{$escapedOrig}%")
                    ->orWhere('title_normalized', 'like', "%{$escapedNorm}%")
                    ->orWhere('alt_titles', 'like', "%{$escapedOrig}%")
                    ->orWhere('alt_titles_normalized', 'like', "%{$escapedNorm}%")
                    ->orWhere('description', 'like', "%{$escapedOrig}%")
                    ->orWhereHas('authors', fn ($authorQuery) => $authorQuery->where('name', 'like', "%{$escapedOrig}%"))
                    ->orWhereHas('genres', fn ($genreQuery) => $genreQuery->where('name', 'like', "%{$escapedOrig}%"))
                    ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->where('name', 'like', "%{$escapedOrig}%"))
                    ->orWhereHas('teams', fn ($teamQuery) => $teamQuery->where('name', 'like', "%{$escapedOrig}%"));
            });

            SearchKeyword::record($q, 1);
        }

        if (!empty($selectedGenres)) {
            foreach ($selectedGenres as $slug) {
                $query->whereHas('genres', fn ($genreQuery) => $genreQuery->where('slug', $slug));
            }
        }

        if (!empty($excludeGenres)) {
            $query->whereDoesntHave('genres', fn ($genreQuery) => $genreQuery->whereIn('slug', $excludeGenres));
        }

        if ($country !== 'all') {
            $countryMap = [
                'manga'   => 'JP',
                'manhwa'  => 'KR',
                'manhua'  => 'CN',
                'vietnam' => 'VN',
                'jp'      => 'JP',
                'kr'      => 'KR',
                'cn'      => 'CN',
                'vn'      => 'VN',
            ];
            $targetCountry = $countryMap[$country] ?? strtoupper($country);
            $query->where('country', $targetCountry);
        }

        if ($status !== 'all') {
            $query->where(function ($statusQuery) use ($status) {
                $statusQuery->where('status', strtolower($status))
                    ->orWhere('status', strtoupper($status));
            });
        }

        if ($minChapters > 0) {
            $query->having('chapters_count', '>=', $minChapters);
        }

        match ($sortBy) {
            'rating'       => $query->orderByDesc('avg_rating')->orderByDesc('views'),
            'latest'       => $query->latestUpdated(),
            'alphabetical' => $query->orderBy('title', 'asc'),
            'trending'     => $query->trending(),
            default        => $query->orderByDesc('views'),
        };

        $comics = $query->paginate(12)->withQueryString();

        $activeGenres = !empty($selectedGenres)
            ? Genre::whereIn('slug', $selectedGenres)->get()
            : collect([]);

        $genreSlug = count($selectedGenres) === 1 ? $selectedGenres[0] : (empty($selectedGenres) ? 'all' : 'multi');

        return view('genres', compact(
            'comics',
            'genres',
            'selectedGenres',
            'excludeGenres',
            'activeGenres',
            'genreSlug',
            'q',
            'country',
            'status',
            'minChapters',
            'sortBy'
        ));
    }
}
