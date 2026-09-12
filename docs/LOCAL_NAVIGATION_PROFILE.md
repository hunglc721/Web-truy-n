# Local navigation diagnostics

Enabled only with `APP_ENV=local`, for GET `/`, `/truyen/{slug}`, `/truyen/{slug}/{chapter}`, and `/user/library`. Production does not register the query listener and the middleware does no profiling. Application queries are unchanged.

Logs use the existing Laravel log channel (`storage/logs/laravel-YYYY-MM-DD.log` with the current daily configuration):

- `local.navigation.slow_query`: queries taking at least 100 ms, including SQL, bindings, `time_ms`, connection, request ID and application call sites.
- `local.navigation.repeated_query`: a SELECT template executed at least five times with at least three different binding sets. This is an N+1 candidate to inspect, not proof by itself.
- `local.navigation.request`: route/action, status, total PHP elapsed time, middleware elapsed time, query count, total SQL time and the five query templates with the highest cumulative time. Requests at least 1,000 ms use warning level.

Match requests using the `X-Local-Request-Id` response header; `Server-Timing` exposes only app/SQL durations. SQL/bindings stay in local logs, not response headers. Total PHP time starts at `LARAVEL_START` when available and excludes the server queue before PHP starts, network, static assets, and browser rendering. Trace collection/logging adds some overhead. Queries during bootstrap before the profiling middleware are not included in SQL totals.

Reproduce with the affected local database and logged-in account, comparing first and subsequent visits. Compare browser TTFB with `Server-Timing` before attributing navigation delays to SQL. Small SQLite fixtures cannot establish performance on the real database.
