# P8/P9 — Recommendation and Search Benchmarks

Date: 2026-11-02
Branch: `feat/chatbot-bounded-agent` (base commit `f8d60f4`)
Machine: local Windows
PHP: 8.3.33
Laravel: 11.55.1
Database: MySQL 8.4.3 (`web_truyen`)
Cache driver during the run: `array`
Raw reports:

- `docs/benchmarks/recommendation-baseline.json`
- `docs/benchmarks/search-baseline.json`

## 1. Harness

Both benchmarks are Artisan commands so reviewers can reproduce the exact runs:

```bash
cd laravel-blade

php artisan benchmark:recommendation \
  --sizes=100,500,1200,5000 \
  --iterations=5 \
  --format=json \
  --output=../docs/benchmarks/recommendation-baseline.json

php artisan benchmark:search \
  --sizes=100,500,1200,5000 \
  --iterations=5 \
  --format=json \
  --explain \
  --output=../docs/benchmarks/search-baseline.json
```

Design:

- `BenchmarkFixture` creates prefix-scoped synthetic comics, chapters, genres, tags, authors, libraries, histories, and recommendation edges inside a transaction.
- The transaction is always rolled back; no benchmark rows survive.
- 10% of synthetic comics are hidden (`published_at` null or future) to exercise publication filtering.
- `BenchmarkMeasurement` times the production service call and captures SQL diagnostics in a separate pass, so query logging does not distort wall-clock samples.
- Cold cases flush the cache before every sample; warm cases reuse the cache. The default cache driver is temporarily switched to `array`.
- `--explain` runs MySQL `EXPLAIN` **inside** the fixture transaction at the largest size, so the plan is measured against the seeded table rather than an empty one.

Caveats:

- Synthetic data is deterministic but not a production snapshot. Absolute timings are useful for same-machine comparisons, not as SLA promises.
- The catalogue has no real long-tail tag distribution; multi-condition cases deliberately include a deterministic full-match population.
- The benchmark reports a single host/run. Numbers varied between runs (for example, the 5,000-row keyword median was ~330 ms in the final run and ~220 ms in an earlier run), so compare medians across repeated runs on the same machine.

## 2. P8 — RecommendationService and PreferenceRecommendationService

### 2.1 Key results

`candidates` is the number of synthetic comics. `q` is the query count, `exists` is the number of `EXISTS` expressions observed in the SQL log, and `selects` is the number of queries selecting from `comics`.

| Candidates | Case | Cache | Median ms | p95 ms | q | exists | selects | results |
|---:|---|---|---:|---:|---:|---:|---:|---:|
| 100 | `forUser` | cold | 10.98 | 12.90 | 9 | 1 | 1 | 6 |
| 100 | `forUser` | warm | 0.02 | 0.04 | 0 | 0 | 0 | 6 |
| 100 | `forComic` | cold | 12.90 | 14.70 | 11 | 2 | 1 | 6 |
| 100 | `forGuest` | cold | 6.72 | 9.52 | 5 | 0 | 1 | 6 |
| 100 | `preference:1_genre` | none | 8.30 | 9.11 | 4 | 2 | 1 | 8 |
| 100 | `preference:4_genres_6_tags` | none | 20.87 | 22.98 | 4 | 15 | 1 | 2 |
| 100 | `preference:12_conditions` | none | 24.86 | 26.47 | 4 | 17 | 1 | 2 |
| 500 | `forUser` | cold | 12.54 | 13.85 | 9 | 1 | 1 | 6 |
| 500 | `preference:12_conditions` | none | 105.16 | 107.31 | 4 | 17 | 1 | 8 |
| 1,200 | `forUser` | cold | 18.69 | 23.32 | 9 | 1 | 1 | 6 |
| 1,200 | `preference:12_conditions` | none | 278.38 | 284.41 | 4 | 17 | 1 | 8 |
| 5,000 | `forUser` | cold | 49.79 | 56.88 | 9 | 1 | 1 | 6 |
| 5,000 | `forComic` | cold | 27.88 | 29.59 | 11 | 2 | 1 | 6 |
| 5,000 | `forGuest` | cold | 6.15 | 7.10 | 5 | 0 | 1 | 6 |
| 5,000 | `preference:1_genre` | none | 88.42 | 90.76 | 4 | 2 | 1 | 8 |
| 5,000 | `preference:4_genres_6_tags` | none | 874.81 | 897.65 | 4 | 15 | 1 | 8 |
| 5,000 | `preference:12_conditions` | none | 460.27 | 479.01 | 4 | 17 | 1 | 8 |
| 5,000 | `preference:8_genres_partial_fallback` | none | 178.14 | 199.61 | 4 | 9 | 1 | 8 |

### 2.2 What the numbers mean

**Caching works.** Every warm case measured **0 queries** and ~0.02 ms. `forUser`, `forComic`, and `forGuest` all have the expected cache key/TTL behavior. A warm recommendation request is effectively free.

**Cold query counts are stable and reasonable.** `forUser` uses 9 queries, `forComic` 11, and `forGuest` 5. There is no N+1 growth with candidate count; the services eager-load genres, tags, authors, and the latest chapter.

**Hydration is bounded.** Every recommendation case performs exactly **one query selecting from `comics`**, plus the related eager-load queries. The SQL ranks candidate IDs first and hydrates only the requested limit; it does not hydrate the whole candidate set.

**Preference ranking cost grows with conditions.** The service emits one correlated `EXISTS` per requested value:

- 1 genre → 2 observed `EXISTS`
- 4 genres + 6 tags (10 conditions) → 15
- 12 conditions → 17

The SQL query count stays at 4, but the database work per query grows with the number of conditions. At 5,000 candidates the 12-condition case was ~460 ms and the 4-genre/6-tag case ~875 ms in this run. The difference also reflects how many rows survive the candidate filter and how many aliases the optimizer evaluates; treat both as “high hundreds of milliseconds” rather than a precise ordering.

**No SQL refactor was applied.** The benchmark justifies a **future** optimization (condition cap or preference-hash cache), not a speculative rewrite now. The existing service still satisfies the correctness rules (hard publication/status/exclusion constraints, full-match-first ranking). See `docs/BACKEND_QUALITY_P7_REVIEW.md` for the recommendation.

## 3. P9 — SearchService

### 3.1 Live search

| Candidates | Case | Cache | Median ms | p95 ms | q | writes | results |
|---:|---|---|---:|---:|---:|---:|---:|
| 100 | `live:title_accented` | cold | 4.01 | 5.25 | 3 | 0 | 1 |
| 500 | `live:title_accented` | cold | 12.48 | 12.84 | 3 | 0 | 8 |
| 1,200 | `live:title_accented` | cold | 9.86 | 10.06 | 3 | 0 | 8 |
| 5,000 | `live:title_accented` | cold | 9.99 | 11.19 | 3 | 0 | 8 |
| 5,000 | `live:title_accented` | warm | 0.02 | 0.05 | 0 | 0 | 8 |
| 5,000 | `live:description_unaccented` | cold | 62.33 | 63.34 | 1 | 0 | 0 |
| 5,000 | `live:author_unaccented` | cold | 6.05 | 7.14 | 3 | 0 | 8 |
| 5,000 | `live:genre` | cold | 6.24 | 6.61 | 3 | 0 | 8 |
| 5,000 | `live:short_input` | cold | 0.00 | 0.00 | 0 | 0 | 0 |

Observations:

- Live search is flat at 3 queries (main query + genres + latest chapter), regardless of result count.
- The cache-hit path is **0 queries / ~0.02 ms**; the 300-second TTL is effective for repeated autocomplete input.
- `liveSearch` intentionally does not search descriptions. The `live:description_unaccented` case returns 0 and still scans the title/alt-title/author/genre predicates; it is included to show the limitation, not as a supported feature.
- Short input returns immediately with 0 queries.

### 3.2 Advanced search

| Candidates | Case | Median ms | p95 ms | q | writes |
|---:|---|---:|---:|---:|---:|
| 100 | `advanced:keyword_accented` | 12.75 | 467.20* | 7 | 1 |
| 500 | `advanced:keyword_accented` | 48.78 | 52.87 | 7 | 1 |
| 1,200 | `advanced:keyword_accented` | 87.36 | 90.57 | 7 | 1 |
| 5,000 | `advanced:keyword_accented` | 329.57 | 341.27 | 7 | 1 |
| 5,000 | `advanced:keyword_unaccented` | 325.29 | 353.42 | 7 | 1 |
| 5,000 | `advanced:description_unaccented` | 23.31 | 23.76 | 7 | 1 |
| 5,000 | `advanced:author_unaccented` | 182.28 | 207.89 | 7 | 1 |
| 5,000 | `advanced:min_chapters` | 23.32 | 28.27 | 6 | 0 |
| 5,000 | `advanced:sort_latest` | 52.25 | 53.21 | 6 | 0 |
| 5,000 | `advanced:per_page_50` | 25.64 | 27.38 | 6 | 0 |

`*` One cold outlier at 100 rows; the median is the meaningful value there.

Observations:

- Advanced keyword search performs **7 queries and 1 write** (`SearchKeyword::record`). Filter/sort-only cases perform 6 queries and no writes.
- Query count is flat: 6–7 queries even when `per_page=50` returns 50 rows, so the result hydration is not N+1.
- Keyword search scales roughly linearly with the catalogue: ~13 ms → ~49 ms → ~87 ms → ~330 ms. This is the leading-wildcard scan, not query-count growth.
- Description search is faster than full keyword search because it does not add author/genre/tag `EXISTS` predicates and, in the benchmark, the candidate query is narrower. It is still a scan.

### 3.3 `description_normalized`

The isolated diagnostic runs the same published-only description predicate against both columns:

| Candidates | Accented `description` | Normalized `description_normalized` | Matches |
|---:|---:|---:|---:|
| 100 | 0.69 ms | 0.43 ms | 90 |
| 500 | 1.04 ms | 0.79 ms | 450 |
| 1,200 | 1.76 ms | 1.38 ms | 1,080 |
| 5,000 | 5.76 ms | 9.03 ms | 4,500 |

Both columns return the same match count. The normalized column exists for **accent-insensitive correctness** and consistent behavior across collations, not because it makes `LIKE '%...%'` fast. Do **not** add an index to `description_normalized` for this query shape; a leading wildcard cannot use a B-tree index.

### 3.4 Cache and invalidation

The benchmark diagnostic confirms:

- After a comic title update, `liveSearch` still returns the cached old title (`stale_after_update = true`).
- After `Cache::flush()`, the same query returns the new title (`refreshed_after_flush = true`).
- TTL is 300 seconds.

This is the current documented contract, not a hidden bug. If freshness matters for newly published/edited comics, add a catalogue version to the cache key or shorten the TTL. That is a product/freshness decision, so it is documented rather than changed in this benchmark pass.

### 3.5 EXPLAIN evidence

The search report includes `diagnostics.explain.plans` captured at 5,000 synthetic comics:

| Plan | Type | Key | Rows | Extra |
|---|---|---|---:|---|
| `keyword_or_accented` (with `ORDER BY views DESC LIMIT 18`) | `index` | `comics_views_index` | 18 | Using where; Backward index scan |
| `keyword_or_accented_without_order` | `ALL` | null | 5,031 | Using where |
| `description_normalized_only` | `ALL` | null | 5,031 | Using where |

The `ORDER BY views LIMIT 18` plan can appear cheap in `EXPLAIN` because MySQL walks the views index backwards and stops when it has 18 matches. The measured latency still grows with table size because the filter has to scan until enough matches are found. The no-order plans show the underlying full scan clearly.

## 4. Decisions

| Question | Decision | Reason |
|---|---|---|
| Add an index for keyword search? | **No** | Leading `%term%` cannot use a B-tree; measured scan is linear. Use FULLTEXT/search engine if the target size needs it. |
| Index `description_normalized`? | **No** | Same leading-wildcard limitation; it is a correctness column, not a performance column. |
| Add an index for `published_at`? | **Not now** | Current plans use the views index for ordered public queries; the synthetic hidden ratio is only 10%. Re-evaluate with a production snapshot if the hidden/scheduled fraction is material. |
| Cache preferences? | **Recommended next step** | Repeated identical multi-condition requests would become warm; needs a preference-hash + catalogue-version key and invalidation contract. |
| Keep live-search cache? | **Yes, with a freshness caveat** | 0-query warm path is a large win; TTL-only staleness is documented. |
| Refactor hydration? | **No** | One `comics` select and flat query counts; no N+1 observed. |

## 5. Reproduction checklist

1. Apply migrations to a disposable MySQL database.
2. Run both commands with `--format=json --output=...`.
3. Compare `environment.git_head`, `environment.db_server_version`, and the case medians.
4. Re-run at least three times before making an index/cache decision; single-run outliers are visible in `p95`/`max`.
5. Keep the generated JSON with the code that produced it.
