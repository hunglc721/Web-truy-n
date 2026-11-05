# P7 — P1–P6 Diff Review and Commit Preparation

Date: 2026-11-02
Branch: `feat/chatbot-bounded-agent`
Committed head reviewed: `f8d60f4` (remote head is the same)
Base: `main`

## 1. Scope and method

This review covers the complete P1–P6 work already committed on this branch:

| Commit | Focus | Size |
|---|---|---|
| `1b23888` | Bounded, read-only AI agent | 20 files, +1,872 / −5 |
| `f8d60f4` | Vietnamese NLP evaluation harness, search/recommendation refinements, responsive UI | 39 files, +23,802 / −167 |

The combined committed diff is **56 files, +25,646 / −144**. I inspected routes, controllers, form requests, services, models, the migration, Blade/JS/CSS, E2E coverage, tests, and the CI workflow. I then ran the full Laravel suite before adding any P7–P10 change.

Baseline before new work:

```text
php artisan test
Tests: 597 passed (3,239 assertions)
```

The existing commits are already pushed. **History is not rewritten or force-pushed.** P7–P10 changes are prepared as focused follow-up commits instead.

## 2. File classification

### Backend — bounded AI agent (`1b23888`)

- `app/Services/AI/Agent/*` — policy, protocol, prompt/grounding validation, fact sheet, card builder, tool registry, agent loop.
- `app/Services/AI/AIClientInterface.php`, `OpenAICompatibleClient.php` — provider contract and safer transport behavior.
- `app/Services/RecommendationChatService.php`, `app/Http/Requests/RecommendationChatRequest.php` — opt-in agent integration and request validation.
- `config/ai.php`, `.env.example` — agent limits, default-off switch.
- `routes/web.php` (existing chat route) — public POST endpoint with `throttle:api`; server-derived quota scopes.
- `tests/Feature/ChatAgentTest.php`, `ChatToolRegistryTest.php` — tool loop, grounding, injection, quota, published-only scope, per-user data.

Security properties confirmed:

- Tools are read-only; no write tool, SQL, filesystem, or internet access is exposed to the model.
- `get_my_library` / `get_my_history` take `user_id` from auth, never from tool arguments.
- Tool queries enforce `published_at IS NOT NULL AND published_at <= now()`.
- Tool calls and rounds are capped; provider/protocol/grounding failures fall back to the deterministic responder.
- Agent is disabled by default (`AI_AGENT_ENABLED=false`) and requires an HTTPS provider URL without credentials/query.

### NLP evaluation harness (`f8d60f4`)

- `app/Console/Commands/EvaluateNlpCommand.php` — rolled-back, provider-free evaluation command with production guard.
- `app/Services/Evaluation/*` — dataset loader, metrics, corpus, report, baseline loader, evaluator.
- `tests/Feature/NlpEvaluationHarnessTest.php`, `tests/Fixtures/nlp/evaluation_dataset.json`.
- `tests/Unit/EvaluationMetricsTest.php`, `NlpEvaluationDatasetTest.php`, `NlpEvaluationReportTest.php`.
- `docs/NLP_EVALUATION.md`, `docs/nlp-evaluation-report.json`.

The command reuses the existing transaction-rollback and JSON-report conventions. The generated report is intentional evidence, not runtime data.

### Recommendation and search (`f8d60f4`)

- `app/Services/RecommendationService.php` — publication filtering across user/comic/guest fallbacks.
- `app/Services/PreferenceRecommendationService.php` — hard status/publication/exclusion constraints, full-match-first ranking, partial fallback, per-value `EXISTS` flags.
- `app/Services/SearchService.php` — public scope, accent-insensitive description search, author-slug matching, SQLite-compatible `min_chapters`.
- `app/Models/Comic.php` — `description_normalized` fillable + normalization hook + `published()` scope.
- `tests/Feature/PreferenceRecommendationTest.php`, `SearchApiTest.php`, `VietnamesePreferenceNlpTest.php`, `RecommendationAuditRegressionTest.php`, `RecommendationChatTest.php`.
- `tests/Unit/RecommendationServiceTest.php`, `SearchServiceTest.php`.

### Database migration (`f8d60f4`)

- `database/migrations/2026_10_26_000001_add_description_normalized_to_comics.php`
  - Adds nullable `description_normalized` after `description`.
  - Backfills existing non-null descriptions in `chunkById(500)`.
  - Down migration drops the column.
  - Verified on MySQL in P10; see `docs/PRODUCTION_READINESS.md`.

### Frontend / Blade / E2E (`f8d60f4`)

- `public/css/responsive.css`, `public/css/style.css` — tablet breakpoint, header actions, overflow fixes.
- `public/js/app.js`, `recommendation-chat-widget.js`, `roadmap.js` — chat widget and navigation behavior.
- `resources/views/layouts/main.blade.php` — layout wiring.
- `e2e/tests/core.spec.js` — tablet header/menu regression test.
- `e2e/README-chatbot.md`, `README.md` — agent and E2E documentation.

The tablet test is desktop/tablet-oriented by design; it does not change the existing mobile journey coverage.

### Generated / supporting files

- `docs/nlp-evaluation-report.json` — 18,297-line generated JSON report committed as evidence.
- `docs/NLP_EVALUATION.md` — methodology and results narrative.
- `phpunit.xml` — adds `AI_AGENT_ENABLED=false` for deterministic tests.

## 3. Artifact and data audit

`git status --ignored` contains only expected local artifacts:

```text
vendor/, .env, bootstrap/cache/*, storage/*, database/e2e.sqlite,
e2e/node_modules/, e2e/playwright-report/, e2e/test-results/
```

No secrets, database dumps, user uploads, temporary benchmark databases, or editor files are tracked. The new benchmark JSON reports under `docs/benchmarks/` are intentional, reproducible deliverables.

`docs/nlp-evaluation-report.json` is large but deterministic and already part of the pushed history. Keep it for now; if the report grows again, regenerate a trimmed summary instead of adding another multi-megabyte file.

## 4. Findings

### Critical

None outstanding after the fixes below.

### High

1. **Keyword search is a table scan and scales linearly**
   - Files: `app/Services/SearchService.php`; evidence in `docs/benchmarks/search-baseline.json`.
   - Problem: advanced keyword search uses leading-wildcard `LIKE '%term%'` across title, alt title, description, author, genre, and tag relations. `EXPLAIN` shows `type=ALL` / ~5,031 rows for the predicate set; measured median is ~12 ms at 100 rows, ~49 ms at 500, ~88 ms at 1,200, and ~330 ms at 5,000.
   - Impact: search latency grows with catalogue size; at 50k+ rows it becomes a user-facing latency and DB-CPU risk.
   - Fix: keep the current behavior for now; when the target catalogue or p95 budget is exceeded, move keyword retrieval to MySQL FULLTEXT or a dedicated search engine rather than adding a B-tree index (a leading wildcard cannot use one).
   - Verification: rerun `benchmark:search` at the target size and compare p95; add an EXPLAIN assertion or load test before choosing the engine.

2. **Preference ranking cost grows with every requested value**
   - Files: `app/Services/PreferenceRecommendationService.php`; evidence in `docs/benchmarks/recommendation-baseline.json`.
   - Problem: each requested genre/tag adds a correlated `EXISTS`; 12 conditions produce 17 `EXISTS` expressions. Median at 5,000 candidates was ~460 ms for the 12-condition case and ~875 ms for the 4-genre/6-tag case in this run.
   - Impact: multi-condition chatbot requests can become expensive; results are not cached.
   - Fix (recommended next step): cap positive conditions, or cache by normalized preference hash + catalogue version. A denormalized match table is only justified after profiling a larger real dataset.
   - Verification: benchmark with `--sizes=10000,50000` on a production-like snapshot; assert the chosen cap/cache keeps p95 within budget.

3. **Trusted proxies are wildcarded**
   - File: `bootstrap/app.php`.
   - Problem: `trustProxies(at: '*')` trusts `X-Forwarded-*` from any peer.
   - Impact: if the app is reachable directly (not only through the intended reverse proxy), a client can spoof IP/scheme, weakening IP-based rate limits, logs, and absolute URL generation.
   - Fix: make trusted proxies configurable (`TRUSTED_PROXIES`) and default to the deployment proxy; keep wildcard only when the origin is firewalled.
   - Verification: feature test with a forwarded header from an untrusted peer; deployment note in `docs/PRODUCTION_READINESS.md`.

### Medium

4. **Live search cache is TTL-only**
   - File: `app/Services/SearchService.php` (`TTL_LIVE_SEARCH = 300`).
   - Evidence: the benchmark proves a title update is still returned from cache until flush; the refreshed value appears only after `Cache::flush()`.
   - Impact: edited/newly published comics can be stale in autocomplete for up to five minutes.
   - Fix: add a catalogue version to the cache key (bumped on comic save/publish) or shorten the TTL for live search. The current behavior is documented and not a correctness bug.
   - Verification: extend the benchmark diagnostic to assert invalidation after a version bump.

5. **Every advanced keyword search writes a search keyword row**
   - File: `app/Services/SearchService.php` → `SearchKeyword::record(...)`.
   - Evidence: one write per `q` request in every advanced-search benchmark case.
   - Impact: storage growth and write amplification under scraping or heavy search traffic.
   - Fix: keep the throttle, but consider sampling, hourly aggregation, or a retention job for `search_keywords`.
   - Verification: add a retention/aggregation test and monitor table growth.

6. **Large generated report in the repository**
   - File: `docs/nlp-evaluation-report.json` (18,297 lines).
   - Impact: review noise and clone size; no runtime dependency.
   - Fix: keep for this PR, but future runs should commit a summary or compress the artifact.

### Low

7. `comics` has both `views` and `views_count`; writers must keep them aligned. The benchmark fixture sets both. Existing code already treats this as legacy compatibility.
8. `description_normalized` is intentionally not indexed; leading wildcard `LIKE` cannot use a B-tree index. It exists for accent-insensitive correctness, not speed.

## 5. Fixes added during this review

### 5.1 Stale normalized search text

- File: `app/Models/Comic.php`.
- Before: `title_normalized`, `alt_titles_normalized`, and `description_normalized` were only overwritten when the source value was non-empty. Clearing a description or alt title left stale searchable text.
- After: the saving hook always writes the normalized value, including `null` for cleared fields.
- Regression tests: `tests/Feature/SearchApiTest.php` — clearing description/alt titles clears normalized text and stops matching search.

### 5.2 Draft/scheduled comic exposure through direct URLs

- Files: `app/Http/Controllers/ComicController.php`, `app/Http/Controllers/ChapterController.php`.
- Before: guests could open `/truyen/{slug}` for a comic with `published_at = null`/future, and could read a published chapter whose parent comic was not public.
- After: non-admin comic detail and reader lookups require `Comic::published()`. Admin preview still works because the admin branch bypasses the scope.
- Related comics now also require `published()`.
- Regression tests: `tests/Feature/PublicationVisibilityTest.php` (guest 404, published 200, admin preview 200).

## 6. Test evidence

```text
cd laravel-blade
php artisan test
Tests: 610 passed (3,289 assertions)
Duration: ~31.2s
```

Focused coverage added/changed:

- `tests/Feature/BenchmarkCommandsTest.php`
- `tests/Feature/SearchApiTest.php`
- `tests/Feature/PublicationVisibilityTest.php`
- `tests/Feature/ComicReleaseMetaTest.php`
- `tests/Feature/PwaBehaviorTest.php`

Playwright E2E (clean SQLite fixture, single worker):

```text
63 passed, 37 skipped, 0 failed
```

## 7. Commit plan

Do not push or merge until the PR review is complete. Suggested focused commits:

1. `test(perf): add recommendation and search benchmark harness`
   - `app/Services/Benchmark/*`, `app/Console/Commands/Benchmark*`, `tests/Feature/BenchmarkCommandsTest.php`.
2. `fix(search): clear normalized fields when source text is removed`
   - `app/Models/Comic.php` (normalization hook), `tests/Feature/SearchApiTest.php`.
3. `fix(publication): hide draft comics from public detail and reader`
   - `ComicController`, `ChapterController`, `tests/Feature/PublicationVisibilityTest.php`.
4. `fix(runtime): make release meta and production CSP SQLite/production safe`
   - `Comic::latestChapter()`, layouts `@production` guard, `render.yaml`,
     `tests/Feature/ComicReleaseMetaTest.php`, `tests/Feature/PwaBehaviorTest.php`.
5. `docs(quality): add P7–P10 review, benchmarks and production readiness`
   - `docs/BACKEND_QUALITY_P7_REVIEW.md`, `docs/PERFORMANCE_BENCHMARKS.md`,
     `docs/PRODUCTION_READINESS.md`, `docs/benchmarks/*.json`, status/doc updates.

Generated benchmark JSON must be committed together with the command that produced it so reviewers can reproduce the exact numbers.

## 8. Review conclusion

The P1–P6 implementation is coherent and consistent with the free-reading product rules. The two correctness fixes above close real publication/search gaps. The remaining risks are performance and operational hardening items, not blockers for a review PR; they are documented with reproduction commands and recommended next steps in `docs/PERFORMANCE_BENCHMARKS.md` and `docs/PRODUCTION_READINESS.md`.
