# Current Migration Phase Status

## Completed on `main`

### Architecture / runtime
- Laravel Blade is the single real application.
- `prototype/` is UI/UX reference only.
- Root Node mock server is removed from runtime.
- Laravel DB/Auth/API/validation/business logic remain the source of truth.

### Public / Member
- Home, Genres, Schedule, Originals, Comic Detail and Reader are Laravel-backed.
- Guest reading stays public.
- Library, History, Comic Likes, Comments, Replies, Comment Likes, Ratings/Reviews and User Hub use Laravel data/auth.
- Prototype localStorage/mock business state is not used by Laravel runtime.

### Admin
- Comics, Chapters, Genres, Tags, Authors, Users, Comments, Reports, Schedules, Banners, Analytics, Audit Logs and Settings use real Laravel backend flows.
- Authors now include search, upload/preview, validation, safe delete and audit logging.
- Users now include Member/Admin/Moderator/Editor/Viewer roles, stats, filtering, role assignment and ban safety.
- Schedule now supports real add/update/delete/active-state interactions and feeds the public `/schedule` page.
- Audit Log now shows real actor/action/subject/IP/payload and only supports retention cleanup rather than destructive wipe-all.

### Roles / Permissions
- Added DB-backed `roles`, `permissions` and `permission_role` tables.
- Existing Admin accounts remain compatible through `is_admin`.
- Added granular `permission:*` route middleware.
- Admin always has full access.
- Moderator, Editor and Viewer are granted scoped permissions from DB.
- `/admin/permissions` edits the real permission matrix.
- Admin sidebar renders modules according to permissions while backend routes independently enforce authorization.

### Responsive / cleanup
- Admin shell now uses an off-canvas mobile sidebar and responsive shared components.
- Major Admin tables/modals have responsive overflow/layout handling.
- Fake Download App promo was removed because the app-download feature is deferred.
- Coin Store and internal Creator Publishing remain intentionally out of scope.
- Searches found no Laravel runtime references to `localhost:3000` or business `localStorage` state.

### Regression coverage added
- Existing Guest/Auth/Header/Settings/Analytics tests retained.
- Added role/permission enforcement coverage.
- Added Author management constraints/search coverage.
- Added Schedule CRUD/public synchronization coverage.
- Added User role/ban safety coverage.
- Updated AdminMiddleware coverage for DB-backed staff roles.

### Quality, performance, and production readiness (P7–P10)
- Reviewed the complete P1–P6 diff and added focused follow-up fixes instead of rewriting pushed history.
- Added reproducible benchmark commands for recommendation and search, with JSON reports under `docs/benchmarks/`.
- Verified the `description_normalized` migration on MySQL (existing DB, fresh DB, rollback, re-apply).
- Closed a draft/scheduled publication leak in comic detail and chapter reader for non-admin users.
- Cleared stale normalized search text when description/alt titles are removed.
- Fixed SQLite ambiguous-column failure in `Comic::latestChapter()`.
- Made the HTTPS-upgrade CSP production-only and set `APP_DEBUG=false` in the Render manifest.
- Full details: `docs/BACKEND_QUALITY_P7_REVIEW.md`, `docs/PERFORMANCE_BENCHMARKS.md`, `docs/PRODUCTION_READINESS.md`.

## Verification status

Environment verification completed:

```bash
cd laravel-blade
php artisan migrate --force
php artisan test
```

```text
Tests: 610 passed (3,289 assertions)
Duration: ~31.2s
```

Playwright E2E (clean SQLite fixture, single worker):

```text
63 passed, 37 skipped, 0 failed
```

The isolated `admin-comic-cover.spec.js` rerun is also green after one transient login-timing failure. Production deployment notes and residual risks are in `docs/PRODUCTION_READINESS.md`.

## Deferred intentionally

- Coin Store / payment / wallet
- Real mobile app download
- Internal Creator Publishing portal

These are product features, not unfinished prototype-to-Blade migration requirements for the current scope.
