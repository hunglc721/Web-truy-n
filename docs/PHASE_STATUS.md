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

## Remaining verification

Implementation work for the agreed migration scope is complete. The remaining step is environment verification:

```bash
cd laravel-blade
php artisan migrate
php artisan optimize:clear
php artisan route:list
php artisan test
```

The full suite has **not** been executed through the current GitHub-only editing environment, so regression status must remain unverified until those commands are run locally.

## Deferred intentionally

- Coin Store / payment / wallet
- Real mobile app download
- Internal Creator Publishing portal

These are product features, not unfinished prototype-to-Blade migration requirements for the current scope.
