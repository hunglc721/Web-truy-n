<?php

namespace App\Providers;

use App\Events\CommentCreated;
use App\Listeners\LogCommentCreated;
use App\Models\Banner;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\ComicLike;
use App\Models\Comment;
use App\Models\Rating;
use App\Models\Schedule;
use App\Models\Setting;
use App\Observers\BannerObserver;
use App\Observers\ChapterObserver;
use App\Observers\ComicLikeObserver;
use App\Observers\ComicObserver;
use App\Observers\CommentObserver;
use App\Observers\RatingObserver;
use App\Observers\ScheduleObserver;
use App\Policies\CommentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Services\AI\AIClientInterface::class, \App\Services\AI\OpenAICompatibleClient::class);

        config(['queue.connections.reader-images' => [
            'driver' => 'database',
            'connection' => null,
            'table' => 'reader_image_jobs',
            'queue' => 'reader-images',
            'retry_after' => 660,
            'after_commit' => true,
        ]]);
    }

    public function boot(): void
    {
        if ($this->app->environment('production') || request()->header('X-Forwarded-Proto') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        if ($this->app->environment('local')) {
            \Illuminate\Support\Facades\DB::listen(function (\Illuminate\Database\Events\QueryExecuted $query) {
                \App\Http\Middleware\LocalNavigationProfile::record(request(), $query);
            });
        }
        Paginator::defaultView('vendor.pagination.custom');
        Paginator::defaultSimpleView('vendor.pagination.custom');

        Gate::policy(Comment::class, CommentPolicy::class);
        Chapter::observe(ChapterObserver::class);
        Comic::observe(ComicObserver::class);
        Comment::observe(CommentObserver::class);
        ComicLike::observe(ComicLikeObserver::class);
        Rating::observe(RatingObserver::class);
        Banner::observe(BannerObserver::class);
        Schedule::observe(ScheduleObserver::class);
        Event::listen(CommentCreated::class, LogCommentCreated::class);

        // Keep feature routes isolated from the already large web.php file.
        $roadmapRoutes = base_path('routes/roadmap.php');
        if (is_file($roadmapRoutes)) {
            Route::middleware('web')->group($roadmapRoutes);
        }

        // Both layouts consume the same cached settings created by the admin UI.
        // The fallback keeps fresh installs usable before the settings migration runs.
        View::composer(['layouts.main', 'layouts.admin'], function ($view) {
            $defaults = [
                'site_name' => 'WebComics',
                'tagline' => 'Đọc Manga, Manhwa & Manhua Online',
                'meta_description' => 'Nền tảng đọc truyện tranh trực tuyến WebComics.',
                'seo_keywords' => 'đọc truyện,manga,manhwa,manhua,webtoon',
                'site_logo' => null,
                'site_favicon' => null,
            ];

            try {
                $siteSettings = [];
                foreach ($defaults as $key => $default) {
                    $siteSettings[$key] = Setting::valueOf($key, $default);
                }
            } catch (\Throwable) {
                $siteSettings = $defaults;
            }

            $view->with('siteSettings', app(\App\Services\BrandingService::class)->urls($siteSettings));
        });

        RateLimiter::for('comments', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->user()?->id ?? $request->ip())
                ->response(fn() => response()->json([
                    'status'  => 'error',
                    'message' => 'Bạn đăng bình luận quá nhanh. Vui lòng thử lại sau.',
                ], 429));
        });

        RateLimiter::for('library-toggle', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?? $request->ip());
        });

        RateLimiter::for('like-toggle', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?? $request->ip());
        });

        RateLimiter::for('history-save', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?? $request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?? $request->ip());
        });

        RateLimiter::for('register', fn (Request $r) => Limit::perHour(5)->by('ip:'.$r->ip()));

        // Chống thử mật khẩu hàng loạt bằng nhiều email khác nhau từ cùng một IP
        RateLimiter::for('login-ip', fn (Request $r) => Limit::perMinute(20)->by('ip:'.$r->ip()));

        RateLimiter::for('password-reset', fn (Request $r) => [
            Limit::perMinute(3)->by('ip:'.$r->ip()),
            Limit::perHour(5)->by('email:'.strtolower((string) $r->input('email'))),
        ]);

        RateLimiter::for('search', fn (Request $r) => Limit::perMinute(30)->by($r->user()?->id ?? 'ip:'.$r->ip()));
    }
}
