# Reader image pipeline

Original chapter files are immutable. Loose uploads now preserve their exact bytes (including metadata) and verify SHA-256 after writing; the previous JPEG/PNG re-encoding has been removed. Folder upload validation, staging, SHA-256 verification, atomic move/stream fallback and rollback are unchanged. ZIP extraction retains original bytes and its existing validation and limits.

After successful ZIP/loose processing, folder finalization or chapter editing, `ReaderImageService::enqueue()` queues `GenerateChapterReaderVariants`. A dispatch/generation failure does not change chapter status, delete originals, or roll back a successful upload. The job retries three times with backoff and logs failures by chapter/page. Use the backfill command to recover failed/missed dispatches.

## Deployment

```sh
cd laravel-blade
php artisan migrate --force
php artisan storage:link
php artisan queue:work reader-images --queue=reader-images --timeout=600 --tries=3
# Queue existing chapters, or one chapter by ID:
php artisan reader:variants
php artisan reader:variants 123
```

Run the worker under a process supervisor. The dedicated `reader-images` database connection uses the new `reader_image_jobs` table, with `retry_after=660` exceeding the 600-second job timeout. It remains asynchronous even if the upload connection is `sync`. Existing chapter tables and queue tables are unchanged. Optional `READER_QUEUE_CONNECTION` can select an existing asynchronous connection (e.g. Redis); configure its retry timeout above the job timeout too. `READER_VARIANTS_ENABLED=false` disables generation. Shared local public storage must be accessible to both web and worker processes; remote/S3 disks safely retain original-only behavior.

Install PHP GD with WebP encoding/decoding support. JPEG/PNG originals generate 480, 800 and 1200px-wide WebP copies at quality 88, preserving aspect ratio/transparency and never upscaling. GIF, APNG, AVIF and existing WebP are retained as originals to avoid flattening animation. JPEGs with rotated EXIF orientation (or unreadable EXIF metadata) retain originals to preserve orientation. Unsupported GD, oversized/decode failures and missing files retain original fallback. Generation processes one original and one variant at a time with pixel/memory guards; extremely tall images may require a larger worker memory budget. Failed pages retry without regenerating existing valid variants.

Files use `dirname(original)/reader/v1-q88/<original-sha256>/<width>/<original-name>.webp`. A per-chapter manifest under public storage `.reader-manifests/` records paths/dimensions and original size/mtime; no chapter metadata migration is needed. Block serving dot directories in Nginx/CDN (only application/worker needs the manifest). The read path loads one manifest, checks original identity and variant existence without hashing or decoding every original on each request. Changed source content generates a new immutable URL on the next job. Original paths must not be overwritten in place; upload a new page path when replacing content.

Serve `/storage/**/reader/**/*.webp` as static `image/webp` files with `Cache-Control: public, max-age=31536000, immutable`. Keep existing anti-hotlink rules, use correct MIME types, and disable script execution for uploaded files. The variant URLs have no session tokens or expiring query strings. A configured public storage/CDN base URL is supported; arbitrary external page URLs are never downloaded or transformed. Existing `/storage/...` and absolute public-storage URLs from ZIP chapters are resolved locally when they match the configured storage base.

## Browser behavior

The first image is eager/high priority; page two is eager. Remaining images retain lazy attributes with deferred sources, activated by a two-request loader for the visible page/spread plus up to three next pages (one with Save-Data). It activates the actual DOM image, without duplicate prefetch links or separate `Image()` objects. Page jumps replace pending work; single/double/vertical layouts share the loader. No next-chapter original is downloaded speculatively.

`srcset` contains only available reader copies; the original is used if no copy exists or a copy fails. Width/height/aspect-ratio preserve space before loading. At widths up to 480 CSS pixels, a picture source intentionally caps selection at the smallest generated variant (normally 480px), including high-DPR phones. This trades retina pixel density for lower transfer/decode cost. Wider screens select among 480/800/1200 using actual image display width. The original is not offered as a high-DPR candidate. No-JavaScript readers retain lazy original images via `noscript`.

The dark modal chapter picker supports search, current-chapter highlighting, keyboard focus/Escape and a bounded scrolling list. One bottom dock owns page navigation, preventing overlap with the former duplicate page navigator.

## Verification

```sh
php artisan test
php artisan test tests/Feature/CriticalUserJourneyTest.php --stop-on-failure
# On an isolated testing database, after the normal seed:
php artisan db:seed --class=ReaderE2ESeeder
cd ../e2e
npm install
npx playwright install chromium
npm test
```

`ReaderE2ESeeder` refuses non-testing environments. The browser fixtures use real local originals and generated WebPs. Tests check 360/390/400/430px at DPR 3, network source selection/request counts, first-page priority, deferred later pages, scrolling, mode changes, chapter picker, dock geometry and original fallback after a variant fails.
