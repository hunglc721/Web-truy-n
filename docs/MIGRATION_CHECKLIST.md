# Prototype → Laravel Blade Migration Checklist

Branch: `feat/merge-prototype-into-laravel-blade`

## Global rules

- `laravel-blade` is the only application that runs as the real product.
- `prototype` is UI/UX reference only.
- Keep existing Laravel backend logic when a feature already exists.
- Guest can browse/read without signing in.
- Member login is required for comment, reply, rating, like, library, reading history and personal statistics.
- Only `is_admin = true` users can enter `/admin`.
- Never create duplicate auth, route, model or controller flows just to copy prototype mock code.
- Prototype mock/localStorage state must not be runtime product state.
- No real application dependency on `localhost:3000`.

## Status

| Area | Status | Notes |
|---|---|---|
| Auth / role model | ✅ Done | Existing Laravel auth + `is_admin` retained. |
| Admin middleware | ✅ Done | `/admin/*` remains behind `auth` + `AdminMiddleware`. |
| Guest public reading | ✅ Done | Public reading routes remain outside `auth`. |
| Member auth boundary | ✅ Done | Protected actions remain under Laravel `auth`. |
| Shared header role state | ✅ Done | Blade renders Guest / Member / Admin states directly from the session. |
| Header JS auth probing | ✅ Removed | JS no longer probes a protected statistics endpoint to guess auth state. |
| Single runtime / one origin | ✅ Done | Standalone `server.js` removed; README documents Laravel only. |
| Prototype folder | ✅ Reference-only | `prototype/README.md` explicitly marks it as non-runtime. |
| Home | ✅ Existing Blade kept | Dynamic DB-backed sections retained. |
| Genres | ✅ Existing Blade kept | Multi-genre/status/sort/pagination retained. |
| Schedule | 🟡 Visual review | Backend/Blade exists; compare remaining visual details. |
| Originals | 🟡 Visual review | Backend/Blade exists; compare remaining visual details. |
| Comic detail | ✅ Existing Blade kept | Guest reading + authenticated interaction states already present. |
| Reader | 🟡 Visual review | Preserve publish gate/history/reporting while matching prototype presentation. |
| Member Library | 🟡 Visual review | Backend exists; remaining work is presentation parity. |
| Comments | ✅ Backend retained | Policy/filter/rate-limit behavior remains Laravel-owned. |
| Ratings / Likes | ✅ Backend retained | Existing endpoints and DB state remain source of truth. |
| Admin base layout | ✅ Fixed | Restored missing `<aside class="admin-sidebar">`; common sidebar/topbar is Blade. |
| Admin dashboard | ✅ Existing Blade kept | Real DB metrics/activity retained. |
| Admin Analytics | ✅ Migrated | Prototype localStorage analytics replaced by DB-backed controller/view at `/admin/analytics`. |
| Admin Permissions | ✅ Migrated | Missing Blade view added; reflects actual Member/Admin model instead of fake prototype roles. |
| Admin Settings | ✅ Migrated | Settings table/model/controller/view added with persistent DB state. |
| Maintenance mode | ✅ Migrated | Admin setting now enforces real 503 maintenance middleware with admin/login bypass. |
| Admin CRUD screens | 🟡 Visual review | Controllers/views exist for Comics, Chapters, Genres, Tags, Authors, Users, Comments, Reports, Schedules, Banners and Logs. |
| Prototype mock API | ✅ Removed from runtime | Laravel JS uses same-origin Laravel endpoints only. |
| Node prototype server | ✅ Removed | No second local web server entrypoint remains on this branch. |
| Public site settings | ✅ Wired | Site name/tagline/default SEO metadata read from persistent settings. |
| Automated regression tests | 🟡 Added, not executed here | New tests cover guest boundaries, header roles, settings/maintenance and analytics; full suite still needs execution in a PHP environment. |

## Acceptance checks

1. Guest can open Home, Genres, Schedule, Originals, comic detail and reader.
2. Guest can view public comments/ratings but cannot submit protected actions.
3. Member can comment, reply, rate, like, save to Library and use history/statistics.
4. Non-admin authenticated user cannot access `/admin`.
5. Admin can access all admin management routes.
6. Real application runs from the Laravel origin only.
7. Prototype mock/localStorage data is never the source of truth for Blade runtime pages.
8. Settings persist in DB and maintenance mode can be disabled by Admin without losing admin/login access.
9. Full Laravel test suite passes before merge to `main`.
