# Prototype → Laravel Blade Migration Checklist

Branch: `feat/merge-prototype-into-laravel-blade`

## Global rules

- `laravel-blade` is the only application that runs as the real product.
- `prototype` is the UI/UX reference only.
- Keep existing Laravel backend logic when the feature already exists.
- Guest users can browse and read public comic pages without signing in.
- Authenticated members can use comment, reply, rating, like, library, reading history and personal statistics.
- Only users with `is_admin = true` can enter `/admin`.
- Never recreate an existing Laravel route, controller, model or database feature just to mirror prototype code.
- Replace prototype mock/localStorage state with Laravel routes/API/database state.
- Do not depend on `localhost:3000` in the real application.

## Status

| Area | Status | Notes |
|---|---|---|
| Auth / role model | Done | Existing Laravel auth + `is_admin` role retained. |
| Admin middleware | Done | Existing middleware protects `/admin`; covered by existing tests. |
| Guest public reading | Done | Public routes remain accessible without login. |
| Member auth boundary | Done | Auth-required actions remain behind `auth`. |
| Shared header auth state | Done | `public/js/app.js` adapts the prototype header for Guest/Member/Admin using Laravel auth state. |
| Home | Existing Blade kept | Dynamic Laravel Home already maps the main prototype sections to DB data. |
| Genres | Existing Blade kept | Blade version already adds multi-genre, status, sorting and pagination, so functionality is retained rather than rewritten. |
| Schedule | Review UI | Compare Blade visual treatment against prototype. |
| Originals | Review UI | Compare Blade visual treatment against prototype. |
| Comic detail | Existing Blade kept | Guest reading + authenticated interaction states already present. |
| Reader | Review UI | Preserve publish gate, history and reader behavior while matching prototype presentation. |
| Member Library | Existing Laravel feature | Keep backend and match prototype UI. |
| Comments | Existing Laravel feature | Keep policy/filter/rate-limit behavior and match prototype UI. |
| Ratings | Existing Laravel feature | Keep backend and match prototype UI. |
| Admin dashboard | Existing Blade kept | Real DB metrics and activity remain the source of truth. |
| Admin CRUD screens | Review UI | Match each prototype admin screen without replacing working controllers. |
| Prototype mock API | In progress | Real app JS must not use `localhost:3000`. |
| Node prototype server | Decommission from product flow | Keep prototype files as UI reference; do not use them for production/runtime. |

## Acceptance checks

1. Guest can open Home, Genres, Schedule, Originals, comic detail and reader.
2. Guest can view comments/ratings but cannot submit protected actions.
3. Member can comment, reply, rate, like, save to Library and use history/statistics.
4. Non-admin authenticated user cannot access `/admin`.
5. Admin can access `/admin` and all admin management routes.
6. Real application runs from the Laravel origin only.
7. Prototype mock/localStorage data is not used as the source of truth by Blade pages.
8. Existing Laravel tests continue to pass after each migration batch.
