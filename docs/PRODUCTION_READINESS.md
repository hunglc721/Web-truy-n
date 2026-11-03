# P10 — Production Readiness Verification

Date: 2026-11-02
Branch: `feat/chatbot-bounded-agent` (base commit `f8d60f4`)
Scope: verify migrations, authorization/publication filtering, validation/rate limiting/error handling, logging/cache/production configuration, regression tests, and E2E before preparing the review PR.

P11 addendum (2026-11-03): trusted reverse-proxy hardening documented in §5.4. `TRUSTED_PROXIES` is now configurable; the Render manifest uses `REMOTE_ADDR` instead of a wildcard.

## 1. Environment used for verification

| Component | Version / setting |
|---|---|
| PHP | 8.3.33 |
| Laravel | 11.55.1 |
| MySQL (migration check) | 8.4.3, local `web_truyen` and scratch DB `comicx_p10` |
| SQLite (tests / E2E) | `laravel-blade/database/database.sqlite`, `database/e2e.sqlite` |
| Cache during benchmark runs | `array` (switched temporarily by the harness) |
| Cache in the Render manifest | `database` |
| Queue in the Render manifest | `sync` |

`APP_DEBUG` in `render.yaml` is now `false`. The production-only `upgrade-insecure-requests` CSP was moved behind `@production` so the HTTP E2E server is not forced to HTTPS.

## 2. Migration verification (MySQL)

Migration: `laravel-blade/database/migrations/2026_10_26_000001_add_description_normalized_to_comics.php`

Properties verified from code:

- Idempotent `up()`: only adds the column when `Schema::hasColumn` is false.
- Backfills existing non-null descriptions with `chunkById(500)`.
- `down()` drops the column only when present.
- No destructive change to comic images, chapters, or existing columns.

Verification steps and results:

```bash
# 1. Apply to the existing development database
cd laravel-blade
php artisan migrate --force

# 2. Verify a fresh install from scratch
mysql -u root -e "CREATE DATABASE comicx_p10 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate:fresh --seed --force
```

Results:

```text
Existing web_truyen:        migration applied successfully (~4s)
Fresh comicx_p10:           migrate:fresh --seed --force succeeded
Seeded comics:              30
description_normalized set: 30 / 30
Column type:                text, nullable, default NULL
```

Rollback/re-apply check:

```bash
php artisan migrate:rollback --step=1 --force
# column absent
php artisan migrate --force
# column present, normalized count back to 30 / 30
```

The migration therefore works on both MySQL and the SQLite test database. It does not require a database-specific feature.

## 3. Authorization and publication filtering

### 3.1 Public reading stays free

- Guest access to home, comic detail, chapter reader, search, schedule, and recommendations remains unauthenticated.
- `tests/Feature/GuestReaderAccessTest.php` and `tests/Feature/WalletAndLedgerTest.php` assert that a published chapter is readable with no wallet, VIP, or unlock step.
- Wallet/deposit/unlock endpoints are tombstones (`WalletAndLedgerTest` expects them disabled). No new monetization logic was added.

### 3.2 Draft and scheduled comics are not public

Before P10, a guest could request a draft/scheduled comic by slug and could read a published chapter whose parent comic was not public.

Fixed in:

- `app/Http/Controllers/ComicController.php` — non-admin detail lookup uses `Comic::published()`.
- `app/Http/Controllers/ChapterController.php` — non-admin reader lookup uses `Comic::published()`.
- Related comics on the detail page also use `->published()`.

Admin preview is preserved because the admin branch bypasses the public scope.

Regression coverage: `tests/Feature/PublicationVisibilityTest.php`

```text
✓ guest cannot open a draft or scheduled comic
✓ guest can open a published comic
✓ admin can still preview a draft comic
✓ guest cannot read a published chapter of a draft comic
✓ admin can still preview a chapter of a draft comic
```

### 3.3 Admin authorization remains server-side

- Admin routes are wrapped in `auth`, `2fa`, `AdminMiddleware`, and per-route `permission:*` middleware (`routes/web.php`).
- UI visibility is not treated as authorization.
- Existing role/permission tests still pass (`tests/Feature/PermissionMatrixTest.php`, admin middleware coverage).

## 4. Validation, rate limiting, and error handling

### 4.1 Validation

- Non-trivial input goes through Form Requests and controller validation.
- Invalid input returns `422` with a consistent JSON envelope (`status`, `message`, `errors`, `code`) for API requests.
- `RecommendationChatRequest` bounds message size and validates optional fields.

### 4.2 Rate limiting

Defined in `app/Providers/AppServiceProvider.php` and applied per route:

| Limiter | Limit | Key |
|---|---:|---|
| `comments` | 5 / minute | user id or IP |
| `library-toggle` | 30 / minute | user id or IP |
| `like-toggle` | 30 / minute | user id or IP |
| `history-save` | 60 / minute | user id or IP |
| `api` | 120 / minute | user id or IP |

Additional coverage:

- Email verification endpoints use `throttle:6,1`.
- Login attempts are rate limited (`tests/Feature/LoginRateLimitTest.php`).
- The chatbot request path enforces its own quota scopes (session + IP), independently of the transport throttle.

Regression coverage: `tests/Feature/RateLimitingTest.php` (comment 5/min, 6th blocked, per-user isolation, library toggle 30/min, unauthenticated comment returns 401 not 429).

### 4.3 Error handling

`bootstrap/app.php` normalizes:

- `404` for missing routes/models, with Blade error page or JSON envelope.
- `403` for authorization failures.
- `401` for unauthenticated JSON requests; browser requests redirect to login.
- `405` for wrong methods.
- `429` with `Retry-After` for throttled requests.
- `422` for validation failures.
- `500` with a generic message for JSON and `errors.500` for HTML when production and debug are off.

Chatbot failures are logged as metadata only (exception class, no SQL/bindings/provider payload) and return the deterministic fallback. `RecommendationChatTest` asserts internal details never leak even with debug enabled.

## 5. Logging, cache, and production configuration

### 5.1 Logging

- `render.yaml` sets `LOG_CHANNEL=stderr`, so container logs are captured by the platform.
- Local `stack` channel writes daily logs plus a separate security channel; daily logs rotate with 14-day retention (`config/logging.php`).
- Unhandled exceptions log class, message, file, line, URL, method, user id, IP, and user agent.
- Authentication/validation/404 events are excluded from noisy reporting via `dontReport`.

### 5.2 Cache

- Live search uses a 300-second cache (`SearchService::TTL_LIVE_SEARCH`); hot keywords have their own TTL.
- Recommendation results are cached and invalidated when a user saves reading history (`RecommendationTest`).
- Search benchmark confirmed a warm request is **0 queries / ~0.02 ms**.
- Known trade-off: live search is TTL-only, so an edited title can remain stale for up to five minutes. Documented in `docs/PERFORMANCE_BENCHMARKS.md`; no correctness change was made.

### 5.3 Production configuration review

| Setting | State | Assessment |
|---|---|---|
| `APP_ENV` | `production` in `render.yaml` | Correct |
| `APP_DEBUG` | `false` in `render.yaml` | Fixed in P10; previously `true` |
| `APP_URL` / `ASSET_URL` | HTTPS Render host | Correct |
| HTTPS upgrade CSP | Emitted only under `@production` | Fixed in P10; keeps local/E2E HTTP working |
| `LOG_CHANNEL` | `stderr` | Correct for container logs |
| `DB_CONNECTION` | `sqlite` on Render | Acceptable for the current small deployment; see residual risks |
| `SESSION_DRIVER` / `CACHE_STORE` | `database` | Works, but shares the SQLite file |
| `QUEUE_CONNECTION` | `sync` | Acceptable for current small jobs; see residual risks |
| `APP_KEY` | Hardcoded in `render.yaml` | **Residual risk**; should move to a platform secret |

### 5.4 Trusted reverse proxies (P11)

#### Verified deployment topology

- `render.yaml` declares a Render **web service** using the Docker runtime.
- `Dockerfile` serves the app with Apache and exposes port 80; `docker-entrypoint.sh` rebinds Apache to Render's `$PORT`.
- Render documents that its load balancer terminates TLS and forwards requests to the service over HTTP, and that the bound service port is **not directly reachable from the public internet** (`https://render.com/docs/web-services#port-binding`).
- Therefore the immediate TCP peer (`REMOTE_ADDR`) is Render's ingress proxy. Render does **not** publish a stable list of ingress proxy IPs/CIDRs, so no explicit proxy range can be derived from the repository or the platform documentation. Do not invent one.
- `TRUSTED_PROXIES=REMOTE_ADDR` in `render.yaml` therefore trusts only the immediate caller, which is the least-privilege setting for this topology.

`app/Http/Middleware/TrustProxies.php` complements Symfony rather than
replacing its resolution rules:

- Symfony resolves the client IP from the **right-most untrusted**
  `X-Forwarded-For` entry once the immediate peer is a trusted proxy.
- The middleware first keeps only the right-most `TRUSTED_PROXY_HOPS` entries.
  When the hop count matches the real chain, caller-supplied left-most values
  never enter Symfony's walk.
- For `X-Forwarded-Proto`, `X-Forwarded-Host`, and `X-Forwarded-Port`, Symfony
  reads the **left-most** value. The middleware keeps only the right-most
  value for those headers, i.e. the value written by the nearest trusted proxy.
- It also drops `Forwarded`, `X-Forwarded-Prefix`, `X-Original-URL`, and
  `X-Rewrite-URL`.

This assumes the standard proxy contract: append the peer address to
`X-Forwarded-For`, and set/append the scheme, host, and port actually served.
It prevents a caller-prepended value from rotating rate-limit buckets, but it
is **defence in depth, not a substitute for firewall isolation**. If the proxy
passes a caller-controlled header through unchanged, no application code can
recover the real client IP.

#### Configuration

```dotenv
# Local development, tests, or a directly exposed app: trust nobody.
TRUSTED_PROXIES=

# Single reverse proxy whose address is not stable (trust the TCP peer only).
TRUSTED_PROXIES=REMOTE_ADDR

# Explicit proxy list. Replace these documentation-only example ranges with
# the addresses/CIDRs published by your platform or network team.
TRUSTED_PROXIES=192.0.2.10,198.51.100.0/24,2001:db8::/32

# X-Forwarded-For entries added by the local chain (client + inner proxies).
TRUSTED_PROXY_HOPS=1
```

- Entries are comma/whitespace separated and may be IPv4/IPv6 addresses or CIDR ranges.
- `REMOTE_ADDR`, and Laravel's legacy `*`/`**`, mean “trust only the peer that opened the TCP connection”.
- Invalid entries are ignored. If nothing valid remains, no proxy is trusted and forwarded headers are ignored (fail closed).
- `TRUSTED_PROXY_HOPS` must equal the number of `X-Forwarded-For` entries added by your local proxy chain: the client entry written by the first proxy, plus one entry per inner proxy. A single load balancer uses `1`.
- When `REMOTE_ADDR` is the only configured proxy, the middleware always uses exactly one hop and ignores a larger `TRUSTED_PROXY_HOPS` value.
- Too few hops discards the real client and resolves to the nearest proxy, so all clients behind that proxy can share a rate-limit bucket. Too many hops leaves caller-supplied entries in the chain and can expose a forged value when the real client IP also falls inside a trusted range.
- Never put client-controlled ranges in `TRUSTED_PROXIES`. Prefer the narrowest proxy addresses/CIDRs the platform or network team can provide.

#### How to determine the proxy IP/CIDR

1. Read the platform's own documentation/dashboard first (for Render, see the link above). If it does not publish a stable ingress range, keep `REMOTE_ADDR`.
2. For self-managed deployments, take the address from the load balancer/ingress configuration (Nginx `proxy_pass`, HAProxy, Kubernetes ingress, cloud LB), not from request headers.
3. Confirm the application port is firewalled so only that proxy can reach it. If clients can reach the app directly, even `REMOTE_ADDR` is spoofable: the attacker becomes the immediate caller.
4. Re-check the list after any platform migration or proxy scaling change.

#### Warning: direct exposure

If the container port is published directly (`docker run -p ...`), the host
firewall allows direct access, or a second ingress path bypasses the proxy,
`TRUSTED_PROXIES=REMOTE_ADDR` (or `*`) lets any direct client set its own
`X-Forwarded-For`, `X-Forwarded-Proto`, and `X-Forwarded-Host`. In that case:

- set `TRUSTED_PROXIES` to the real proxy list and block direct access at the firewall; **or**
- set `TRUSTED_PROXIES=` so forwarded headers are ignored until the network path is fixed.

Never rely on the proxy to strip client-supplied forwarded headers.

#### Post-deploy verification

> The repository checks below verify the middleware and tests only. They do
> **not** prove what the live Render ingress actually sends. Runtime
> verification must be repeated after every deploy that changes the proxy,
> platform, or `TRUSTED_PROXIES` setting.

| Check | Expected result |
|---|---|
| HTTPS | Public URL serves HTTPS; `APP_URL`/asset URLs are `https://`; no redirect loop |
| Client IP | `auth.login_failed.ip` / activity log shows the real client IP, not a Render-internal address |
| Forged `X-Forwarded-For` | A caller-supplied value never appears as the logged client IP |
| Rate limit | Same client with different forged headers shares one bucket; different clients get separate buckets |
| Direct access | App port is not reachable except through the Render ingress |

1. Confirm the deployed configuration is the intended one:

   ```bash
   php artisan tinker --execute="dump(config('trustedproxy.proxies'), config('trustedproxy.hops'));"
   ```

2. Trigger a failed login through the public URL and inspect the `auth.login_failed` log entry. Its `ip` must be the real client IP, not Render's internal address and not a forged value:

   ```bash
   curl -s -o /dev/null -X POST https://<app-host>/login \
     -H 'X-Forwarded-For: 203.0.113.99' \
     --data 'email=probe@example.invalid&password=wrong'
   ```

   Then check `stderr`/security logs. If the logged `ip` equals `203.0.113.99` (the forged value), forwarded headers are being trusted from an untrusted peer; stop and fix the topology/config.

3. Repeat the same request without the forged header. The logged IP must stay the client's real address.
4. Verify HTTPS URL generation (`APP_URL` starts with `https://`) and that the app does not issue an HTTP redirect loop behind the proxy.
5. Send two requests from different real clients and confirm they receive separate rate-limit buckets; send two requests from the same client with different forged `X-Forwarded-For` values and confirm they share one bucket.

## 6. Regression evidence

### 6.1 Laravel suite

```text
cd laravel-blade
php artisan test

Tests:    623 passed (3329 assertions)
Duration: ~35s
```

This includes the new P7–P11 coverage:

- `tests/Feature/BenchmarkCommandsTest.php`
- `tests/Feature/PublicationVisibilityTest.php`
- `tests/Feature/ComicReleaseMetaTest.php`
- new cases in `tests/Feature/SearchApiTest.php` and `tests/Feature/PwaBehaviorTest.php`
- `tests/Feature/TrustedProxySecurityTest.php` (13 cases, including hop/ordering review)

### 6.2 Playwright E2E

Environment: clean SQLite fixture, freshly seeded, single worker against the local `artisan serve` instance.

```text
cd e2e
npx playwright test --workers=1
```

Result recorded for this review:

```text
63 passed, 37 skipped, 0 failed
```

A later rerun produced one transient failure in `admin-comic-cover.spec.js` (admin login `page.waitForURL` timeout). Isolated confirmation:

```bash
npx playwright test tests/admin-comic-cover.spec.js --workers=1 --project=desktop-chromium
# 1 passed (10.9s)
```

The failure was environmental (server/login timing), not a product regression. Mobile-only skips in the suite are intentional and documented in the specs; desktop admin flows that require `webkitdirectory` are skipped on mobile.

## 7. Deployment runbook

1. **Secrets**
   - Move `APP_KEY` to the platform secret store. Never commit a new key.
   - Set `APP_ENV=production`, `APP_URL`/`ASSET_URL` to the HTTPS host, `APP_DEBUG=false`.

2. **Database**
   - Current Render manifest uses SQLite at `/var/www/html/laravel-blade/database/database.sqlite`.
   - The entrypoint creates the file, runs `php artisan migrate --force`, seeds defaults, then caches config/routes/views.
   - For a durable production deployment, move to managed MySQL/Postgres and set `DB_*`, `SESSION_DRIVER`, and `CACHE_STORE` accordingly.

3. **Migration**
   - Deploy the code containing `2026_10_26_000001_add_description_normalized_to_comics`.
   - The entrypoint applies it automatically. For manual deploys:

   ```bash
   php artisan migrate --force
   ```

   - Backfill happens in chunks; on a very large table, run it during a maintenance window or monitor it separately.

4. **Queues and scheduler**
   - `QUEUE_CONNECTION=sync` executes jobs inline. If chapter-image processing or notifications become heavy, set `QUEUE_CONNECTION=database`/Redis and run a worker:

   ```bash
   php artisan queue:work --queue=notifications,chapter-images,default
   php artisan schedule:work
   ```

5. **Storage**
   - Run `php artisan storage:link` after deploy.
   - Ensure `storage/` and `bootstrap/cache/` are writable by the web user.

6. **Post-deploy smoke checks**
   - Guest opens `/`, a published comic detail, and a reader page.
   - A draft comic returns 404 for a guest and 200 for an admin preview.
   - `/api/search/live?q=...` returns JSON and is throttled.
   - `/up` returns healthy.
   - Logs show `stderr` output and no debug stack traces.

## 8. Residual risks and follow-ups

| Risk | Impact | Recommendation |
|---|---|---|
| `APP_KEY` is hardcoded in `render.yaml` | Key rotation and repo exposure risk | Move to platform secret; rotate if the repo was ever public |
| Trusted proxies | Render does not publish a stable ingress CIDR; `REMOTE_ADDR` trusts the immediate peer. If the origin becomes directly reachable, forwarded headers can be spoofed | Keep the origin firewalled. On other platforms, list the real proxy IPs/CIDRs in `TRUSTED_PROXIES`; leave it empty for direct access. See §5.4 |
| SQLite on Render | Concurrency/write contention; single instance only | Move to managed MySQL/Postgres before traffic grows |
| `QUEUE_CONNECTION=sync` | Slow requests when image/notification jobs run | Enable a queue worker for image and notification jobs |
| Live search TTL-only | New/edited comics stale for up to 5 minutes | Add catalogue-version cache keys or shorten TTL |
| Keyword search is a leading-wildcard scan | ~330 ms at 5,000 rows; grows linearly | Move to FULLTEXT/search engine when the catalogue or p95 budget requires it |
| Preference ranking scales with conditions | ~460–875 ms at 5,000 candidates for multi-condition requests | Cap conditions or cache by preference hash + catalogue version |
| `comics.views` and `views_count` both exist | Writers must keep them aligned | Keep both updated as today; consider consolidation in a future schema cleanup |
| Large committed NLP report | Review/clone noise | Keep for this PR; commit summaries for future runs |

None of these are blockers for the P1–P6 review PR. They are operational hardening items with owners/reproduction steps documented in `docs/PERFORMANCE_BENCHMARKS.md` and `docs/BACKEND_QUALITY_P7_REVIEW.md`.

## 9. Release decision

- Product rules preserved: free reading, no paywall, original image bytes unchanged, server-side authorization, no test weakening.
- Migration verified on MySQL and SQLite.
- Publication filtering closes the draft/scheduled leak.
- Full Laravel suite and E2E are green.
- Ready for review PR; merge after CI is green and the deployment secrets above are addressed.
