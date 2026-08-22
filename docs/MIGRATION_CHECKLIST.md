# Prototype → Laravel Blade Migration Checklist

Target: `main`

## Global rules

- `laravel-blade` is the only real application/runtime.
- `prototype` is UI/UX reference only.
- Laravel owns DB, auth, authorization, validation and business logic.
- Guest can browse/read without signing in.
- Member login is required for comment, reply, rating, like, library, history and personal statistics.
- Admin area uses database-backed roles/permissions; hiding UI never replaces backend authorization.
- Prototype mock/localStorage state is not runtime product state.
- No real application dependency on `localhost:3000`.

## Status

| Area | Status | Notes |
|---|---|---|
| Auth / roles | ✅ Done | Member/Admin/Moderator/Editor/Viewer supported; legacy `is_admin` remains compatible. |
| Admin authorization | ✅ Done | `AdminMiddleware` + granular `permission:*` middleware enforce backend access. |
| Guest public reading | ✅ Done | Home, Genres, Schedule, Originals, detail and reader remain public. |
| Member auth boundary | ✅ Done | Comment/reply/likes/library/rating/history remain authenticated actions. |
| Shared header | ✅ Done | Guest/Member/staff state rendered server-side. |
| Single runtime | ✅ Done | Laravel 11 Blade only; legacy prototype directory retired & removed. |
| Prototype folder | ✅ Sunset | Đã dọn dẹp hoàn toàn khỏi codebase; toàn bộ tính năng và giao diện đã nằm trong `laravel-blade`. |
| Home | ✅ Done | DB-backed banners/trending/latest and user shortcuts. |
| Genres | ✅ Done | DB-backed filtering/pagination; fake Active/Bulk actions removed/replaced. |
| Schedule public | ✅ Done | Reads active DB schedules. |
| Originals | ✅ Done | DB-backed originals and Library action. |
| Comic detail | ✅ Done | Chapters, Library, comic Like, Comment/Reply/Comment Like, Rating/Review, recommendations. |
| Reader | ✅ Retained/audited | Real reader remains stronger than prototype; publish gate/history/reporting retained. |
| User Hub | ✅ Done | Dashboard, Library, History, Likes, Comments, Ratings and statistics/export. |
| Admin Authors | ✅ Done | Search, pagination, upload/preview, validation, relation-safe delete, audit. |
| Admin Users | ✅ Done | Search/filter/stats/details, multi-role assignment, ban/unban and safety rules. |
| Admin Schedule | ✅ Done | Add/update/delete/active state and public DB synchronization. |
| Audit Log | ✅ Done | Search/filter/pagination, real IP/payload and retention cleanup. |
| Roles / Permissions | ✅ Done | DB-backed role/permission matrix with route enforcement. |
| Permission-aware sidebar | ✅ Done | Admin navigation rendered according to backend permissions. |
| Admin Analytics | ✅ Done | Real DB metrics. |
| Admin Settings | ✅ Done | Persistent DB settings. |
| Maintenance mode | ✅ Done | Real 503 maintenance behavior with admin/login bypass. |
| Admin Comments | ✅ Done | Real moderation + bulk actions. |
| Admin Reports | ✅ Done | Real report workflow/status/note handling. |
| Admin Banners | ✅ Done | CRUD/scheduling/toggle/upload + real click tracking. |
| Responsive Admin shell | ✅ Done | Off-canvas mobile sidebar, responsive cards/tables/modals. |
| Prototype mock API | ✅ Removed from runtime | Laravel uses same-origin endpoints. |
| localStorage business state | ✅ Removed from Laravel runtime | Presentation-only storage may remain where appropriate. |
| Coin Store | ⏸ Out of scope | Deferred intentionally. |
| Download App | ⏸ Out of scope | Fake app-download promo removed; real app deferred. |
| Creator Publishing | ⏸ Out of scope | Existing external creator link may remain. |
| Automated regression tests | 🟡 Added, not executed here | Added role/permission, authors, schedules and user-role tests in addition to existing suite. |

## Acceptance checks

1. Guest can browse/read and view comments/ratings without login.
2. Protected member actions require Laravel authentication.
3. Member cannot enter `/admin`.
4. Admin has full admin access.
5. Moderator/Editor/Viewer access is determined by DB permissions and route middleware.
6. Direct URL or write request without permission returns 403.
7. Schedule changes in Admin are read by the public schedule page from DB.
8. Audit logs use real request IP and cannot be wiped completely from the UI.
9. Laravel has no runtime dependency on prototype mock/localStorage/Node APIs.
10. Run `php artisan migrate`, `php artisan route:list` and `php artisan test` locally before declaring regression verification complete.
