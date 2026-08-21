# Current Migration Phase Status

## Completed in `feat/merge-prototype-into-laravel-blade`

### Phase 1 — Architecture and authorization boundary
- Laravel is the single real application.
- Guest reading remains public.
- Member-only actions remain behind `auth`.
- Admin remains protected by `AdminMiddleware` + `is_admin`.
- Existing Laravel auth/models/services were preserved instead of rewritten.

### Phase 2 — Shared client integration
- Added same-origin `public/js/app.js`.
- Live search uses `/api/search/live`.
- Prototype carousel/banner interactions work without Node/mock API dependencies.
- Shared Blade header now renders Guest, Member and Admin states directly.
- JS no longer probes protected APIs to guess login state.

### Phase 3 — One runtime / one URL
- Removed root `server.js` standalone Node prototype runtime.
- Rewrote README to document only `laravel-blade` as the runtime.
- Added `prototype/README.md` marking prototype files as UI reference only.

### Phase 4 — Admin prototype gaps
- Fixed broken Admin layout structure by restoring the missing `.admin-sidebar` wrapper.
- Added `/admin/analytics` backed by real DB metrics.
- Added `/admin/permissions` Blade page mapped to the real 2-role Member/Admin model.
- Added `/admin/settings` as a real persistent module instead of localStorage.

### Phase 5 — Persistent site settings / maintenance
- Added `settings` table and `Setting` model.
- Added `AdminSettingController` and DB-backed settings form.
- Public layout consumes site name, tagline and default SEO metadata from settings.
- Added maintenance middleware and 503 page.
- Admin/login remains reachable while maintenance is active.

### Phase 6 — Regression coverage added
- `GuestReaderAccessTest`
- `HeaderRoleStateTest`
- `AdminSettingsTest`
- `AdminAnalyticsTest`
- Existing Admin/Comment/CRUD tests remain in the suite.

## Remaining work

- Visual parity review for Schedule and Originals.
- Visual parity review for Reader while preserving publish gate/history/reporting.
- Visual parity review for Member Library.
- Visual parity review for each existing Admin CRUD screen against prototype.
- Review any prototype-only decorative/client interactions that still make sense after Laravel integration.
- Run the complete Laravel test suite in a PHP/Composer environment and fix any failures.
- Final branch diff/review before merge into `main`.

`prototype/` itself does not need to be deleted: it remains useful as design documentation, but it is no longer executable product runtime.
