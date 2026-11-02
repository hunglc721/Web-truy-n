# PR Note — P7–P10 Quality, Performance, and Production Readiness

Branch: `feat/chatbot-bounded-agent`
Base: `main`
Reviewed head before follow-ups: `f8d60f4`

## Summary

This PR reviews the already-pushed P1–P6 work and adds focused follow-up fixes plus reproducible evidence. It does **not** rewrite history and does **not** change the free-reading product model.

## Changes

### Benchmark harness (P8/P9)
- `app/Services/Benchmark/BenchmarkMeasurement.php`, `BenchmarkFixture.php`
- `app/Console/Commands/BenchmarkRecommendationCommand.php`
- `app/Console/Commands/BenchmarkSearchCommand.php`
- `tests/Feature/BenchmarkCommandsTest.php`
- Raw reports: `docs/benchmarks/recommendation-baseline.json`, `docs/benchmarks/search-baseline.json`

### Correctness fixes
- `Comic` always overwrites normalized search columns (including clearing them).
- Non-admin comic detail and chapter reader require `Comic::published()`; related comics are published-only.
- `Comic::latestChapter()` uses an ordered `hasOne` that works on SQLite and when eager-loaded.
- CSP `upgrade-insecure-requests` is emitted only in production.
- `render.yaml` sets `APP_DEBUG=false`.

### Tests
- Added `PublicationVisibilityTest`, `ComicReleaseMetaTest`, `BenchmarkCommandsTest`.
- Extended `SearchApiTest` and `PwaBehaviorTest`.

### Docs
- `docs/BACKEND_QUALITY_P7_REVIEW.md`
- `docs/PERFORMANCE_BENCHMARKS.md`
- `docs/PRODUCTION_READINESS.md`
- Updated `docs/PHASE_STATUS.md`, `docs/MIGRATION_CHECKLIST.md`.

## Test evidence

```text
cd laravel-blade
php artisan test
Tests: 610 passed (3,289 assertions)
```

```text
cd e2e
npx playwright test --workers=1
63 passed, 37 skipped, 0 failed
```

MySQL migration verified on an existing database, a fresh database, and rollback/re-apply.

## Product-rule checklist

- [x] No paid unlocking, coins, wallet, VIP, or subscription added.
- [x] Published chapters remain free to read.
- [x] Draft/scheduled chapters stay non-public before `published_at`.
- [x] Original chapter image bytes are untouched.
- [x] Admin/actions remain protected by server-side middleware/permissions.
- [x] No tests were deleted, skipped, or weakened to make CI pass.

## Known follow-ups (not blockers)

- Move `APP_KEY` to a platform secret.
- Replace `trustProxies(at: '*')` with a configured proxy list.
- Move off SQLite and `QUEUE_CONNECTION=sync` before traffic grows.
- Add catalogue-version invalidation for live search if freshness matters.
- Revisit FULLTEXT/search engine and preference caching when catalogue/load grows.

See `docs/PRODUCTION_READINESS.md` for the full risk table and deployment runbook.
