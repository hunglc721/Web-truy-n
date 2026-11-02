<?php

namespace App\Services\AI\Agent;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Library;
use App\Models\ReadingHistory;
use App\Models\User;
use App\Services\PreferenceRecommendationService;
use App\Services\RecommendationTaxonomyService;
use App\Data\RecommendationPreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Read-only tool allowlist. The model never sees SQL, Eloquent, file paths or
 * secrets: it can only name one of these tools and validated arguments.
 */
final class ChatToolRegistry
{
    public const TOOLS = [
        'search_comics', 'get_comic', 'list_chapters', 'get_chapter',
        'get_my_library', 'get_my_history', 'get_taxonomy',
    ];

    public function __construct(
        private RecommendationTaxonomyService $taxonomy,
        private PreferenceRecommendationService $recommendations,
    ) {}

    public function allows(string $name): bool
    {
        return in_array($name, self::TOOLS, true);
    }

    /** Compact description used inside the planner prompt. */
    public function definitions(): array
    {
        return [
            ['name' => 'search_comics', 'description' => 'Find published comics by taste, title or free-text description. query and keywords are matched against title, alt titles, description, genre names and tag names; structured filters are applied on top. Returns only the best few matches (not the whole catalogue) with id, title, score and matched reasons.', 'arguments' => [
                'query' => 'optional string, title or keyword',
                'keywords' => 'optional string[] of 2-5 short Vietnamese keywords/synonyms (2-40 chars each) matched against title, description, genres and tags',
                'genres' => 'optional string[] of allowed genre names',
                'themes' => 'optional string[] of allowed theme tags',
                'character_traits' => 'optional string[] of allowed character tags',
                'settings' => 'optional string[] of allowed setting tags',
                'tones' => 'optional string[] of allowed tone tags',
                'status' => 'optional one of ongoing|completed|hiatus|cancelled',
                'exclude' => 'optional string[] of taxonomy values to exclude',
                'limit' => 'optional integer 1-8',
            ]],
            ['name' => 'get_comic', 'description' => 'Read one published comic (description, tags, authors, rating, chapter count).', 'arguments' => [
                'comic_id' => 'optional integer',
                'slug' => 'optional string',
            ]],
            ['name' => 'list_chapters', 'description' => 'List published chapters of one comic, newest or oldest first. Returns total_published, returned and truncated so counts stay exact.', 'arguments' => [
                'comic_id' => 'optional integer', 'slug' => 'optional string',
                'limit' => 'optional integer 1-30', 'order' => 'optional asc|desc',
            ]],
            ['name' => 'get_chapter', 'description' => 'Read metadata of one published chapter, plus a text excerpt only when that chapter has text content.', 'arguments' => [
                'comic_id' => 'optional integer', 'slug' => 'optional string', 'number' => 'required number, for example 12 or 12.5',
            ]],
            ['name' => 'get_my_library', 'description' => 'List comics saved in the signed-in user\'s own library, with total and truncated counts. Fails for guests.', 'arguments' => [
                'limit' => 'optional integer 1-10',
            ]],
            ['name' => 'get_my_history', 'description' => 'Recent reading history of the signed-in user only, with total and truncated counts. Fails for guests.', 'arguments' => [
                'limit' => 'optional integer 1-10',
            ]],
            ['name' => 'get_taxonomy', 'description' => 'Allowed filter values (genres, tags by category, statuses). Use before inventing a filter name.', 'arguments' => [
                'field' => 'optional all|genres|themes|settings|character_traits|tones|relationships|statuses',
            ]],
        ];
    }

    public function execute(string $name, array $args, ?User $user): array
    {
        if (! $this->allows($name)) {
            return $this->failure($name, 'unknown_tool');
        }

        try {
            $data = match ($name) {
                'search_comics' => $this->searchComics($args),
                'get_comic' => $this->getComic($args),
                'list_chapters' => $this->listChapters($args),
                'get_chapter' => $this->getChapter($args),
                'get_my_library' => $this->getMyLibrary($args, $user),
                'get_my_history' => $this->getMyHistory($args, $user),
                'get_taxonomy' => $this->getTaxonomy($args),
            };

            return ['tool' => $name, 'ok' => true, 'data' => $data];
        } catch (ChatToolException $exception) {
            return $this->failure($name, $exception->reason);
        } catch (\Throwable $exception) {
            Log::warning('Chat tool failure', ['tool' => $name, 'error' => $exception::class]);

            return $this->failure($name, 'tool_error');
        }
    }

    private function failure(string $tool, string $reason): array
    {
        return ['tool' => $tool, 'ok' => false, 'error' => $reason];
    }

    private function searchComics(array $args): array
    {
        $data = $this->validate($args, [
            'query' => ['nullable', 'string', 'max:80'],
            'keywords' => ['nullable', 'array', 'max:5'],
            'keywords.*' => ['string', 'min:2', 'max:40', 'distinct', 'regex:/^[\pL\pN\s]+$/u'],
            'genres' => ['nullable', 'array', 'max:5'],
            'genres.*' => ['string', 'max:60', 'distinct'],
            'themes' => ['nullable', 'array', 'max:5'],
            'themes.*' => ['string', 'max:60', 'distinct'],
            'character_traits' => ['nullable', 'array', 'max:5'],
            'character_traits.*' => ['string', 'max:60', 'distinct'],
            'settings' => ['nullable', 'array', 'max:5'],
            'settings.*' => ['string', 'max:60', 'distinct'],
            'tones' => ['nullable', 'array', 'max:5'],
            'tones.*' => ['string', 'max:60', 'distinct'],
            'status' => ['nullable', 'string', Rule::in(RecommendationTaxonomyService::STATUSES)],
            'exclude' => ['nullable', 'array', 'max:10'],
            'exclude.*' => ['string', 'max:60', 'distinct'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:8'],
        ]);

        $limit = (int) ($data['limit'] ?? 5);
        $terms = $this->terms($data);
        $filters = [
            'genres' => $data['genres'] ?? [],
            'themes' => $data['themes'] ?? [],
            'character_traits' => $data['character_traits'] ?? [],
            'settings' => $data['settings'] ?? [],
            'tones' => $data['tones'] ?? [],
            'status' => $data['status'] ?? null,
            'exclude' => $data['exclude'] ?? [],
        ];
        $hasFilters = $terms !== [] || $filters['status'] !== null || $filters['exclude'] !== [];
        foreach (['genres', 'themes', 'character_traits', 'settings', 'tones'] as $field) {
            $hasFilters = $hasFilters || $filters[$field] !== [];
        }
        if (! $hasFilters) {
            throw new ChatToolException('invalid_arguments');
        }

        if ($terms !== []) {
            return $this->searchByTerms($terms, $filters, $limit);
        }

        $preference = RecommendationPreference::fromArray($filters);
        $ranked = $this->recommendations->recommend($preference, $limit);
        $ids = $ranked->pluck('comic.id')->all();
        $comics = $this->comicQuery()->whereIn('id', $ids)->get()->keyBy('id');

        return $ranked
            ->map(function (array $result) use ($comics) {
                $comic = $comics->get($result['comic']->id);

                return $comic === null ? null : $this->comicRow($comic, $result['score'], $result['matched_reasons']);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Free-text terms come from the model, so wildcards are stripped before LIKE.
     *
     * @return list<string>
     */
    private function terms(array $data): array
    {
        $terms = [];
        $query = trim((string) ($data['query'] ?? ''));
        if ($query !== '') {
            $terms[] = $this->cleanTerm($query);
        }
        foreach ($data['keywords'] ?? [] as $keyword) {
            $terms[] = $this->cleanTerm((string) $keyword);
        }

        return array_values(array_unique(array_filter($terms, fn (string $term) => $term !== '')));
    }

    private function cleanTerm(string $term): string
    {
        $term = preg_replace('/[%_]+/u', ' ', $term) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $term) ?? '');
    }

    /** Matches a free-text term against title, description, genres and tags. */
    private function searchByTerms(array $terms, array $filters, int $limit): array
    {
        $query = $this->comicQuery();
        $query->where(function (Builder $outer) use ($terms) {
            $outer->whereRaw('1 = 0');
            foreach ($terms as $term) {
                $like = '%'.$term.'%';
                $normalized = AgentText::normalize($term);
                $outer->orWhere(function (Builder $inner) use ($like, $normalized) {
                    $inner->where('title', 'like', $like)
                        ->orWhere('alt_titles', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas('genres', fn (Builder $genres) => $genres->where('name', 'like', $like))
                        ->orWhereHas('tags', fn (Builder $tags) => $tags->where('name', 'like', $like));
                    // Accent-free titles are stored normalized, so "truyen thu" still matches "Truyện Thử".
                    if ($normalized !== '') {
                        $inner->orWhere('title_normalized', 'like', '%'.$normalized.'%')
                            ->orWhere('alt_titles_normalized', 'like', '%'.$normalized.'%');
                    }
                });
            }
        });
        $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('views_count')
            ->orderByDesc('avg_rating')
            ->limit($limit)
            ->get()
            ->map(fn (Comic $comic) => $this->comicRow($comic, null, $this->termReasons($comic, $terms)))
            ->all();
    }

    /** Structured filters stay AND-ed on top of a free-text match. */
    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }
        if ($filters['genres'] !== []) {
            $query->whereHas('genres', fn (Builder $genres) => $genres->whereIn('name', $filters['genres']));
        }
        foreach (RecommendationTaxonomyService::CATEGORIES as $category => $field) {
            if (($filters[$field] ?? []) !== []) {
                $query->whereHas('tags', fn (Builder $tags) => $tags
                    ->where('category', $category)->whereIn('name', $filters[$field]));
            }
        }
        if ($filters['exclude'] !== []) {
            $query->whereDoesntHave('genres', fn (Builder $genres) => $genres->whereIn('name', $filters['exclude']))
                ->whereDoesntHave('tags', fn (Builder $tags) => $tags
                    ->whereIn('category', array_keys(RecommendationTaxonomyService::CATEGORIES))
                    ->whereIn('name', $filters['exclude']))
                ->whereNotIn('status', $filters['exclude']);
        }
    }

    /** @return list<string> */
    private function termReasons(Comic $comic, array $terms): array
    {
        $haystacks = [
            AgentText::normalize($comic->title),
            AgentText::normalize((string) $comic->alt_titles),
            AgentText::normalize((string) $comic->description),
            ...$comic->genres->map(fn ($genre) => AgentText::normalize($genre->name))->all(),
            ...$comic->tags->map(fn ($tag) => AgentText::normalize($tag->name))->all(),
        ];
        $reasons = [];
        foreach ($terms as $term) {
            $needle = AgentText::normalize($term);
            foreach ($haystacks as $haystack) {
                if ($needle !== '' && $haystack !== '' && str_contains($haystack, $needle)) {
                    $reasons[] = 'Khớp "'.$term.'"';
                    break;
                }
            }
        }

        return array_values(array_unique($reasons));
    }

    private function getComic(array $args): array
    {
        $comic = $this->findComic($args);

        return [
            'id' => $comic->id,
            'title' => $comic->title,
            'slug' => $comic->slug,
            'description' => AgentText::clean($comic->description, 800),
            'status' => $comic->status,
            'released_year' => $comic->released_year,
            'rating' => round((float) ($comic->avg_rating ?? 0), 1),
            'views' => (int) ($comic->views_count ?? $comic->views ?? 0),
            'genres' => $comic->genres->pluck('name')->values()->all(),
            'tags' => $comic->tags->pluck('name')->values()->all(),
            'authors' => $comic->authors->pluck('name')->values()->all(),
            'chapter_count' => (int) ($comic->published_chapters_count ?? 0),
            'latest_chapter' => $comic->latestChapter?->label,
            'url' => route('comics.show', $comic->slug),
        ];
    }

    private function listChapters(array $args): array
    {
        $data = $this->validate($args, [
            'comic_id' => ['nullable', 'integer', 'min:1'],
            'slug' => ['nullable', 'string', 'max:160'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:30'],
            'order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ]);
        $comic = $this->findComic([
            'comic_id' => $data['comic_id'] ?? null,
            'slug' => $data['slug'] ?? null,
        ]);
        $limit = (int) ($data['limit'] ?? 10);
        $order = $data['order'] ?? 'desc';
        $total = $this->publishedChapters($comic)->count();
        $chapters = $this->publishedChapters($comic)
            ->orderBy('chapter_number', $order)
            ->limit($limit)
            ->get()
            ->map(fn (Chapter $chapter) => $this->chapterRow($comic, $chapter))
            ->all();

        return [
            'comic' => ['id' => $comic->id, 'title' => $comic->title, 'slug' => $comic->slug],
            'total_published' => $total,
            'returned' => count($chapters),
            'truncated' => $total > count($chapters),
            'chapters' => $chapters,
        ];
    }

    private function getChapter(array $args): array
    {
        $data = $this->validate($args, [
            'comic_id' => ['nullable', 'integer', 'min:1'],
            'slug' => ['nullable', 'string', 'max:160'],
            'number' => ['required', 'numeric', 'min:0', 'max:100000'],
        ]);
        $number = Chapter::formatNumber((string) $data['number']);
        if (! Chapter::isValidNumber($number)) {
            throw new ChatToolException('invalid_arguments');
        }
        $comic = $this->findComic([
            'comic_id' => $data['comic_id'] ?? null,
            'slug' => $data['slug'] ?? null,
        ]);
        $chapter = $this->publishedChapters($comic)
            ->where('chapter_number', $number)
            ->first();
        if ($chapter === null) {
            throw new ChatToolException('not_found');
        }

        $row = $this->chapterRow($comic, $chapter);
        $content = AgentText::clean((string) $chapter->content, 1200);
        $row['has_text'] = $content !== '';
        $row['content_excerpt'] = $content !== '' ? $content : null;
        $row['content_truncated'] = mb_strlen((string) $chapter->content) > mb_strlen($content);

        return $row;
    }

    private function getMyLibrary(array $args, ?User $user): array
    {
        if ($user === null) {
            throw new ChatToolException('unauthorized');
        }
        $data = $this->validate($args, ['limit' => ['nullable', 'integer', 'min:1', 'max:10']]);
        $limit = (int) ($data['limit'] ?? 5);

        $saved = Library::query()
            ->where('user_id', $user->id)
            ->whereHas('comic', fn (Builder $comic) => $comic
                ->whereNotNull('published_at')->where('published_at', '<=', now()));
        $total = $saved->count();
        $items = $saved
            ->with(['comic.genres', 'comic.tags', 'lastReadChapter'])
            ->latest('added_at')
            ->limit($limit)
            ->get()
            ->map(fn (Library $entry) => [
                'id' => $entry->comic->id,
                'title' => $entry->comic->title,
                'slug' => $entry->comic->slug,
                'status' => $entry->status,
                'last_read_chapter' => $entry->lastReadChapter?->label,
                'added_at' => $entry->added_at?->toIso8601String(),
                'url' => route('comics.show', $entry->comic->slug),
            ])
            ->all();

        return [
            'total' => $total,
            'returned' => count($items),
            'truncated' => $total > count($items),
            'items' => $items,
        ];
    }

    private function getMyHistory(array $args, ?User $user): array
    {
        if ($user === null) {
            throw new ChatToolException('unauthorized');
        }
        $data = $this->validate($args, ['limit' => ['nullable', 'integer', 'min:1', 'max:10']]);
        $limit = (int) ($data['limit'] ?? 5);

        $history = ReadingHistory::query()
            ->where('user_id', $user->id)
            ->whereHas('comic', fn (Builder $comic) => $comic
                ->whereNotNull('published_at')->where('published_at', '<=', now()));
        $total = $history->count();
        $items = $history
            ->with(['comic', 'chapter'])
            ->latest('last_read_at')
            ->limit($limit)
            ->get()
            ->map(fn (ReadingHistory $entry) => [
                'id' => $entry->comic->id,
                'title' => $entry->comic->title,
                'slug' => $entry->comic->slug,
                'chapter' => $entry->chapter?->label,
                'scroll_percent' => round((float) $entry->scroll_percent, 1),
                'last_read_at' => $entry->last_read_at?->toIso8601String(),
                'url' => route('comics.show', $entry->comic->slug),
            ])
            ->all();

        return [
            'total' => $total,
            'returned' => count($items),
            'truncated' => $total > count($items),
            'items' => $items,
        ];
    }

    private function getTaxonomy(array $args): array
    {
        $data = $this->validate($args, [
            'field' => ['nullable', 'string', Rule::in([
                'all', 'genres', 'themes', 'settings', 'character_traits', 'tones', 'relationships', 'statuses',
            ])],
        ]);
        $field = $data['field'] ?? 'all';
        $taxonomy = $this->taxonomy->all();
        $pick = fn (array $values): array => array_slice(array_values($values), 0, 80);

        if ($field !== 'all' && isset($taxonomy[$field])) {
            return [$field => $pick($taxonomy[$field])];
        }

        return [
            'genres' => $pick($taxonomy['genres']),
            'themes' => $pick($taxonomy['themes']),
            'settings' => $pick($taxonomy['settings']),
            'character_traits' => $pick($taxonomy['character_traits']),
            'tones' => $pick($taxonomy['tones']),
            'relationships' => $pick($taxonomy['relationships']),
            'statuses' => $pick($taxonomy['statuses']),
        ];
    }

    private function findComic(array $args): Comic
    {
        $data = $this->validate($args, [
            'comic_id' => ['nullable', 'integer', 'min:1'],
            'slug' => ['nullable', 'string', 'max:160'],
        ]);
        if (($data['comic_id'] ?? null) === null && ($data['slug'] ?? null) === null) {
            throw new ChatToolException('invalid_arguments');
        }

        $query = $this->comicQuery();
        $comic = isset($data['comic_id'])
            ? $query->whereKey((int) $data['comic_id'])->first()
            : $query->where('slug', (string) $data['slug'])->first();
        if ($comic === null) {
            throw new ChatToolException('not_found');
        }

        return $comic;
    }

    private function comicQuery(): Builder
    {
        // Null/future publication dates are never catalog-visible.
        return Comic::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with(['genres:id,name', 'tags:id,name', 'latestChapter'])
            ->withCount(['chapters as published_chapters_count' => fn (Builder $chapters) => $chapters
                ->whereNotNull('published_at')->where('published_at', '<=', now())]);
    }

    private function publishedChapters(Comic $comic): Builder
    {
        return Chapter::query()
            ->where('comic_id', $comic->id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    private function comicRow(Comic $comic, ?int $score, array $reasons): array
    {
        return [
            'id' => $comic->id,
            'title' => $comic->title,
            'slug' => $comic->slug,
            'status' => $comic->status,
            'rating' => round((float) ($comic->avg_rating ?? 0), 1),
            'chapter_count' => (int) ($comic->published_chapters_count ?? 0),
            'latest_chapter' => $comic->latestChapter?->label,
            'genres' => $comic->genres->pluck('name')->values()->all(),
            'tags' => $comic->tags->pluck('name')->values()->all(),
            'score' => $score,
            'matched_reasons' => array_values(array_unique($reasons)),
            'url' => route('comics.show', $comic->slug),
        ];
    }

    private function chapterRow(Comic $comic, Chapter $chapter): array
    {
        return [
            'number' => Chapter::formatNumber($chapter->chapter_number),
            'label' => $chapter->label,
            'title' => $chapter->title,
            'published_at' => $chapter->published_at?->toIso8601String(),
            'pages_count' => is_array($chapter->pages) ? count($chapter->pages) : 0,
            'url' => route('chapters.show', [$comic->slug, $chapter->slug]),
        ];
    }

    private function validate(array $args, array $rules): array
    {
        if (array_diff(array_keys($args), array_keys($rules)) !== []) {
            throw new ChatToolException('invalid_arguments');
        }
        $validator = Validator::make($args, $rules);
        if ($validator->fails()) {
            throw new ChatToolException('invalid_arguments');
        }

        return $validator->validated();
    }
}
