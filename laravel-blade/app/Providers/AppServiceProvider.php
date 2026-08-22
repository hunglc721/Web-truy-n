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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Comment::class, CommentPolicy::class);
        Chapter::observe(ChapterObserver::class);
        Comic::observe(ComicObserver::class);
        Comment::observe(CommentObserver::class);
        ComicLike::observe(ComicLikeObserver::class);
        Rating::observe(RatingObserver::class);
        Banner::observe(BannerObserver::class);
        Schedule::observe(ScheduleObserver::class);
        Event::listen(CommentCreated::class, LogCommentCreated::class);

        // Public layout consumes the persistent settings created by the admin UI.
        // The fallback keeps fresh installs usable before the settings migration runs.
        View::composer('layouts.main', function ($view) {
            $defaults = [
                'site_name' => 'WebComics',
                'tagline' => 'Đọc Manga, Manhwa & Manhua Online',
                'meta_description' => 'Nền tảng đọc truyện tranh trực tuyến WebComics.',
                'seo_keywords' => 'đọc truyện,manga,manhwa,manhua,webtoon',
            ];

            try {
                $siteSettings = [];
                foreach ($defaults as $key => $default) {
                    $siteSettings[$key] = Setting::valueOf($key, $default);
                }
            } catch (\Throwable) {
                $siteSettings = $defaults;
            }

            $view->with('siteSettings', $siteSettings);
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
    }
}
