# AGENTS.md

## 1. Project identity

This repository is **Comicx**, a personal comic-reading platform built with **Laravel 11 + Blade**.

Primary application:

```text
laravel-blade/
```

Browser E2E tests:

```text
e2e/
```

The product is a **100% free comic-reading website**. Published chapters must remain readable without coins, wallet balance, VIP, subscription, or paid unlocks.

Before changing code, read this file and `README.md`, then inspect the relevant routes, controllers, models, services, requests, jobs, migrations, Blade views, JavaScript, and tests.

---

## 2. Non-negotiable product rules

1. **Do not add monetized chapter unlocking.**
   - Do not introduce Coin, Wallet, VIP, subscription, paid chapter unlock, or paywall logic.
   - Legacy wallet/unlock endpoints may exist only as disabled/tombstone behavior for backward compatibility.

2. **Published chapters are free to read.**
   - Do not accidentally gate normal reader access behind authentication or payment.
   - Future/scheduled chapters must not become publicly readable before `published_at`.

3. **Do not damage comic images.**
   - Never resize, recompress, lower quality, or silently convert original chapter images unless a task explicitly asks for image transformation.
   - Preserve original bytes for the large-folder uploader.
   - Any upload refactor must keep file validation and integrity checks.

4. **Do not weaken backend authorization.**
   - UI visibility is not authorization.
   - Admin/staff actions must stay protected by backend middleware, permissions, policies, or equivalent server-side checks.

5. **Do not bypass tests to make CI green.**
   - Fix the underlying bug instead of deleting, skipping, or weakening relevant regression tests.

---

## 3. Git workflow

Do not develop directly on `main`.

For every implementation task:

```text
main
  ↓
new feature/fix branch
  ↓
implementation
  ↓
tests
  ↓
Pull Request
  ↓
CI green
  ↓
merge
```

Rules:

- Start from the latest `main`.
- Use a focused branch such as `feat/...`, `fix/...`, `test/...`, or `docs/...`.
- Keep changes scoped to the requested task.
- Avoid unrelated cleanup in the same PR unless it is necessary for correctness.
- Before merge, verify the PR is mergeable and the CI pipeline is green.

---

## 4. Backend architecture

The main Laravel application follows this flow:

```text
Browser
  ↓
Blade + JavaScript
  ↓
Routes
  ↓
Controllers
  ↓
Services / Policies / Jobs
  ↓
Eloquent Models
  ↓
MySQL / SQLite
```

Prefer:

- Form Requests for non-trivial validation.
- Services for reusable business logic.
- Jobs/queues for expensive or asynchronous work.
- Eager loading and batch queries to avoid N+1.
- Database transactions for multi-step writes that must succeed or fail together.
- Existing conventions before inventing a new abstraction.

Do not move business rules into Blade or client-side JavaScript when they must be enforced by the server.

---

## 5. Current core features that must be preserved

### Authentication and security

The project includes authentication/security features such as:

- register/login/logout;
- email verification;
- password reset;
- 2FA and recovery code;
- session/device management;
- banned-user enforcement;
- RBAC and permission middleware;
- validation and rate limiting;
- anti-spam/honeypot;
- secure image upload;
- anti-hotlink protection.

Changes to auth or admin areas require regression coverage.

### Library and reading progress

Current behavior includes:

- personal comic library;
- reading history;
- unread chapter count;
- next unread chapter;
- continue reading;
- scroll percentage restore;
- reading an old chapter must not regress progress;
- future chapters must not count as unread.

Do not change this logic casually. Inspect `LibraryService`, reader/history code, and existing tests before editing it.

### Search and recommendations

Search supports partial/contains matching and Vietnamese accent-insensitive behavior. Recommendation logic uses reading/library signals and caching.

Do not regress search UX to exact-prefix-only matching just to simplify SQL.

### Realtime notifications

Notifications currently use **Server-Sent Events (SSE)** for one-way realtime updates.

Preserve:

- notification badge updates without reload;
- dropdown refresh;
- realtime toast;
- reconnect behavior;
- fallback JSON polling when SSE repeatedly fails;
- the existing authenticated notification endpoint contract.

Do not replace SSE with a new realtime stack unless the task explicitly asks for an architectural migration such as Laravel Reverb/WebSockets.

---

## 6. Admin chapter upload rules

Comicx supports multiple chapter upload flows. Do not break any of them when modifying the uploader.

### Existing upload modes

- ZIP upload for one chapter.
- Multiple loose image upload.
- URL list upload.
- Large root-folder upload containing many chapter folders.

### Large multi-chapter folder uploader

This workflow is designed for folders that may be several GB in total.

Required behavior:

- detect chapter folders such as `Vol.16 Ch.0140 - Title`;
- extract chapter number/title when possible;
- natural-sort page filenames, e.g. `1`, `2`, `10`;
- upload in small batches rather than one multi-GB request;
- retry transient failures;
- adapt when the server returns `413` by splitting batches;
- compute SHA-256 in the browser;
- verify checksum on the server;
- preserve original image bytes;
- stage files before finalizing a chapter;
- only finalize when the chapter has every expected page;
- rollback incomplete/failed chapter finalization;
- bind an upload session to the correct admin and comic;
- keep upload limits and safety checks in place.

### Pre-upload inspection/QC

Before the bulk upload button becomes available, every detected chapter must pass preflight inspection.

Current expected UX:

- show a list/table of detected chapters;
- allow Admin to click a chapter and inspect it;
- show page previews in natural sort order;
- verify each image can be decoded and has valid dimensions;
- flag corrupt/invalid pages clearly;
- allow `Check all chapters`;
- keep the upload button disabled until all chapters pass;
- avoid loading the entire multi-GB folder into memory at once;
- release preview Object URLs when no longer needed.

When changing this flow, update Playwright coverage for both the successful QC path and corrupt-image blocking path.

---

## 7. Testing requirements

### Laravel tests

From repository root:

```bash
cd laravel-blade
php artisan test
```

For critical smoke tests:

```bash
cd laravel-blade
php artisan test tests/Feature/CriticalUserJourneyTest.php --stop-on-failure
```

When changing a specific subsystem, run its focused tests first, then the full suite.

Examples of areas that require dedicated regression tests:

- auth/security;
- admin permissions;
- chapter publishing/access;
- reader/history/library;
- search/recommendation;
- notifications/SSE;
- chapter upload/integrity;
- comments/rating/reporting.

### Playwright E2E

Install and run from repository root:

```bash
cd e2e
npm install
npx playwright install chromium
npm test
```

Browser tests cover desktop and mobile journeys where relevant.

For desktop-only admin workflows such as selecting a local directory with `webkitdirectory`, an explicit mobile skip is acceptable when documented and intentional.

### CI

Workflow:

```text
.github/workflows/laravel-tests.yml
```

A code task is not complete until relevant tests pass. For changes that affect browser behavior, the Playwright job must pass too.

---

## 8. Development setup

Backend setup:

```bash
cd laravel-blade
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Queue worker:

```bash
php artisan queue:work --queue=notifications,chapter-images,default
```

Scheduler:

```bash
php artisan schedule:work
```

Seed accounts commonly used in development/E2E:

```text
Admin:  admin@webcomics.com / 12345678
Member: user@webcomics.com  / 12345678
```

Do not hardcode these credentials into production logic.

---

## 9. Database and migration rules

- Treat migrations as append-only history unless a task explicitly concerns unreleased migration cleanup.
- Use foreign keys/indexes consistently with existing schema conventions.
- Add indexes when introducing query patterns that need them.
- Avoid schema changes when a code-only fix is sufficient.
- For destructive operations, use transactions and make rollback behavior explicit.
- Ensure migrations work with the project test environment using SQLite unless a feature truly requires a database-specific capability.

---

## 10. Frontend/Blade rules

- Keep Blade views focused on rendering and lightweight presentation logic.
- Use safe DOM APIs / `textContent` for untrusted text where JavaScript builds HTML dynamically.
- Preserve responsive behavior for desktop/tablet/mobile.
- Do not introduce horizontal overflow in public pages or reader layouts.
- Reuse existing CSS/JS structure when possible instead of adding a second competing implementation.
- Any admin uploader UI change must remain practical with hundreds of pages and many chapters.

---

## 11. Performance rules

Before adding queries inside loops, check for N+1.

Prefer:

- eager loading;
- batch queries;
- cache for read-heavy stable data;
- queues for expensive background work;
- pagination for large lists;
- chunked/batched processing for large uploads.

Do not optimize by changing product behavior or reducing correctness guarantees.

---

## 12. How to approach a new task

Before coding:

1. Read `AGENTS.md` and `README.md`.
2. Pull the latest `main` and create a new branch.
3. Locate the relevant route/controller/service/model/view/JS/tests.
4. Understand existing behavior from code and tests, not assumptions.
5. Identify backward-compatibility risks.

During implementation:

1. Make the smallest coherent change that solves the task.
2. Add or update regression tests.
3. Preserve free-reading, authorization, image integrity, and existing API contracts.
4. Run focused tests.
5. Run the full relevant suite.

Before finishing:

1. Review the diff for unrelated changes.
2. Confirm no debug code, secrets, temporary files, or generated artifacts were committed.
3. Confirm CI passes.
4. Create/update the PR with a concise summary and test evidence.

---

## 13. When auditing instead of coding

If asked to audit the project, do not immediately refactor everything.

Return findings grouped by priority:

```text
Critical
High
Medium
Low
```

For each finding include:

- affected file(s);
- concrete problem;
- impact;
- recommended fix;
- test/verification plan.

Prefer evidence from current code and tests over generic Laravel advice.

---

## 14. Definition of done

A task is done only when:

- requested behavior works;
- existing relevant behavior still works;
- validation/authorization is enforced server-side;
- required tests are added or updated;
- focused tests pass;
- full relevant regression suite passes;
- browser tests pass when UI/browser behavior changed;
- no unrelated changes are included;
- PR is ready to merge with clear test evidence.
