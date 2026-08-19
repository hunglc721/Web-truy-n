<?php

namespace App\Providers;

use App\Events\CommentCreated;
use App\Listeners\LogCommentCreated;
use App\Models\Chapter;
use App\Models\Comment;
use App\Observers\ChapterObserver;
use App\Policies\CommentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ── Task 9: Authorization Policy ─────────────────────────────────────
        Gate::policy(Comment::class, CommentPolicy::class);

        // ── Task 11: Cache Observers ──────────────────────────────────────────
        // ChapterObserver xóa cache 'chapters_list' và 'home.*' khi chapter thay đổi
        Chapter::observe(ChapterObserver::class);

        // ── Task 13: Event → Listener mapping ────────────────────────────────
        Event::listen(CommentCreated::class, LogCommentCreated::class);

        // ── Task 14: Named Rate Limiters ──────────────────────────────────────
        // Định nghĩa tại đây, áp dụng lên route bằng ->middleware('throttle:X')

        // POST /api/comments — 5 bình luận / phút / user
        RateLimiter::for('comments', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->user()?->id ?? $request->ip())
                ->response(fn() => response()->json([
                    'status'  => 'error',
                    'message' => 'Bạn đăng bình luận quá nhanh. Vui lòng thử lại sau.',
                ], 429));
        });

        // POST /api/comics/*/toggle-library — 30 req/phút / user
        RateLimiter::for('library-toggle', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?? $request->ip());
        });

        // POST /api/comics/*/toggle-like — 30 req/phút / user
        RateLimiter::for('like-toggle', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?? $request->ip());
        });

        // POST /api/reading-history — 60 req/phút / user (cuộn trang liên tục)
        RateLimiter::for('history-save', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?? $request->ip());
        });

        // Toàn bộ /api/* — 120 req/phút / user hoặc IP (guest)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)
                ->by($request->user()?->id ?? $request->ip());
        });
    }
}
