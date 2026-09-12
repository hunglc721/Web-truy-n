# Background chapter uploads

Admin folder selection, editing and preflight remain on the chapter-create page. Starting an upload opens the named same-origin window `webcomics-upload-worker` at `/admin/upload-worker`. Keep that window open; the main tab can navigate normally. Popup blocking is reported with an explicit retry button, with no page-bound fallback.

The main page passes its final edited `File` objects by structured clone. After acknowledgement it releases its file references. The worker performs SHA-256, bounded multipart batches, adaptive HTTP 413 splitting and finalization through the existing bulk service. No service worker, Redis or new dependency is involved.

`upload_tasks` stores owner, comic, manifest, session ID, counters, phase, error context and timestamps. One active task per admin is enforced under a user-row lock. A worker UUID lease, serialized task-row mutations and an eight-second heartbeat prevent two workers from uploading the same task. The legacy endpoint rejects task-owned sessions so it cannot bypass cancellation or the lease.

Progress comes from durable session receipts after successful batches/finalization, never client byte totals. Retries do not double count. Finalized chapter receipts remain until the existing 24-hour session TTL. Completion and the owner's database notification are committed together, with the task UUID as the unique notification ID.

Admin layouts, including the public layout used by the reader, fetch the active snapshot immediately and subscribe to `webcomics-upload` BroadcastChannel events. Snapshot versions reject delayed responses. If broadcasts stop, a five-second snapshot fallback detects stale state; no upload SSE is used. Guest/member pages load neither the widget nor its script.

Navigation, F5 and a new uploader tab reconnect to the same task without selecting a folder. Closing the worker releases the lease best-effort; after 30 seconds without heartbeat, a snapshot reports `waiting_for_client`. Reselect the same final folder contents to resume. The worker verifies staged checksums and skips received files and finalized chapters. Expired tasks must be cancelled and restarted. Cancel leaves successfully finalized chapters intact.

Validation:

```sh
cd laravel-blade
php artisan test tests/Feature/BackgroundUploadTaskTest.php tests/Feature/BulkChapterFolderUploadTest.php tests/Feature/DecimalChapterNumberTest.php tests/Feature/CriticalUserJourneyTest.php
php artisan test
cd ../e2e
npx playwright test tests/background-upload.spec.js tests/bulk-folder.spec.js --project=desktop-chromium --workers=1
npm test -- --workers=1
```

Use an isolated seeded testing database for browser tests. The background test delays real requests, then asserts server bytes increase **after** navigation to reports, genres, home, comic detail and reader. It also checks return/F5/new tab, completion notification, worker-close resume, final edited bytes and existing-chapter skip. `SERVER_PROGRESS_AFTER_NAVIGATION` in test output and its JSON attachment contain the measured counters.
