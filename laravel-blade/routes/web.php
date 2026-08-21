<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\OriginalsController;
use App\Http\Controllers\ComicController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\ComicActionController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\UserStatisticsController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\Admin\AdminComicController;
use App\Http\Controllers\Admin\AdminGenreController;
use App\Http\Controllers\Admin\AdminTagController;
use App\Http\Controllers\Admin\AdminAuthorController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminChapterController;
use App\Http\Controllers\Admin\AdminCommentController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminScheduleController;
use App\Http\Controllers\Admin\AdminBannerController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Middleware\AdminMiddleware;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/genres', [GenreController::class, 'index'])->name('genres');
Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');
Route::get('/originals', [OriginalsController::class, 'index'])->name('originals');
Route::get('/truyen/{slug}', [ComicController::class, 'show'])->name('comics.show');
Route::get('/truyen/{comicSlug}/{chapterSlug}', [ChapterController::class, 'show'])->name('chapters.show');
Route::get('/banners/{banner}/click', [BannerController::class, 'click'])->name('banners.click');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/user', [UserDashboardController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/user/history', [UserDashboardController::class, 'history'])->name('user.history');
    Route::get('/user/likes', [UserDashboardController::class, 'likes'])->name('user.likes');
    Route::get('/user/comments', [UserDashboardController::class, 'comments'])->name('user.comments');
    Route::get('/user/ratings', [UserDashboardController::class, 'ratings'])->name('user.ratings');
    Route::get('/user/library', [LibraryController::class, 'index'])->name('user.library');
    Route::post('/user/library/toggle/{comic}', [LibraryController::class, 'toggle'])->name('library.toggle');
    Route::delete('/user/history/clear', [LibraryController::class, 'clearHistory'])->name('history.clear');

    Route::post('/api/reading-history', [ChapterController::class, 'saveHistory'])->middleware('throttle:history-save')->name('history.save');
    Route::post('/api/comments', [CommentController::class, 'store'])->middleware('throttle:comments')->name('comments.store');
    Route::post('/api/comments/{comment}/toggle-like', [CommentController::class, 'toggleLike'])->middleware('throttle:like-toggle')->name('comments.toggleLike');
    Route::patch('/api/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/api/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/api/comics/{comicId}/toggle-library', [ComicActionController::class, 'toggleLibrary'])->middleware('throttle:library-toggle')->name('comics.toggleLibrary');
    Route::post('/api/comics/{comicId}/toggle-like', [ComicActionController::class, 'toggleLike'])->middleware('throttle:like-toggle')->name('comics.toggleLike');
    Route::post('/api/comics/{comicId}/ratings', [RatingController::class, 'store'])->middleware('throttle:like-toggle')->name('comics.ratings.store');
    Route::delete('/api/comics/{comicId}/ratings', [RatingController::class, 'destroy'])->middleware('throttle:like-toggle')->name('comics.ratings.destroy');
    Route::get('/api/comics/{comicId}/my-rating', [RatingController::class, 'userRating'])->name('comics.ratings.user');
    Route::get('/api/user/statistics/overview', [UserStatisticsController::class, 'overview'])->name('user.statistics.overview');
    Route::get('/api/user/statistics/genres', [UserStatisticsController::class, 'genres'])->name('user.statistics.genres');
    Route::get('/api/user/statistics/badges', [UserStatisticsController::class, 'badges'])->name('user.statistics.badges');
    Route::get('/api/user/statistics/weekly', [UserStatisticsController::class, 'weekly'])->name('user.statistics.weekly');
    Route::get('/api/user/statistics/export', [UserStatisticsController::class, 'export'])->name('user.statistics.export');
});

Route::get('/api/comics/{comicId}/ratings/summary', [RatingController::class, 'summary'])->middleware('throttle:api')->name('comics.ratings.summary');
Route::get('/api/comics/{comicId}/ratings/reviews', [RatingController::class, 'reviews'])->middleware('throttle:api')->name('comics.ratings.reviews');
Route::get('/api/search/live', [SearchController::class, 'live'])->middleware('throttle:api')->name('search.live');
Route::get('/api/search/advanced', [SearchController::class, 'advanced'])->middleware('throttle:api')->name('search.advanced');
Route::get('/api/comments', [CommentController::class, 'index'])->middleware('throttle:api')->name('comments.index');
Route::get('/api/recommendations', [RecommendationController::class, 'index'])->middleware('throttle:api')->name('recommendations.index');
Route::post('/api/reports', [\App\Http\Controllers\ReportController::class, 'store'])->middleware('throttle:api')->name('reports.store');

Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');

    Route::get('/comics', [AdminComicController::class, 'index'])->name('comics.index');
    Route::get('/comics/create', [AdminComicController::class, 'create'])->name('comics.create');
    Route::post('/comics', [AdminComicController::class, 'store'])->name('comics.store');
    Route::get('/comics/{id}/edit', [AdminComicController::class, 'edit'])->name('comics.edit');
    Route::put('/comics/{id}', [AdminComicController::class, 'update'])->name('comics.update');
    Route::delete('/comics/{id}', [AdminComicController::class, 'destroy'])->name('comics.destroy');

    Route::prefix('/comics/{comic}/chapters')->name('comics.chapters.')->scopeBindings()->group(function () {
        Route::get('/', [AdminChapterController::class, 'index'])->name('index');
        Route::get('/create', [AdminChapterController::class, 'create'])->name('create');
        Route::post('/', [AdminChapterController::class, 'store'])->name('store');
        Route::get('/{chapter}/edit', [AdminChapterController::class, 'edit'])->name('edit');
        Route::put('/{chapter}', [AdminChapterController::class, 'update'])->name('update');
        Route::delete('/{chapter}', [AdminChapterController::class, 'destroy'])->name('destroy');
    });

    Route::get('/genres', [AdminGenreController::class, 'index'])->name('genres.index');
    Route::get('/genres/create', [AdminGenreController::class, 'create'])->name('genres.create');
    Route::post('/genres', [AdminGenreController::class, 'store'])->name('genres.store');
    Route::delete('/genres/bulk', [AdminGenreController::class, 'bulkDestroy'])->name('genres.bulkDestroy');
    Route::get('/genres/{genre}/edit', [AdminGenreController::class, 'edit'])->name('genres.edit');
    Route::put('/genres/{genre}', [AdminGenreController::class, 'update'])->name('genres.update');
    Route::delete('/genres/{genre}', [AdminGenreController::class, 'destroy'])->name('genres.destroy');

    Route::get('/tags', [AdminTagController::class, 'index'])->name('tags.index');
    Route::get('/tags/create', [AdminTagController::class, 'create'])->name('tags.create');
    Route::post('/tags', [AdminTagController::class, 'store'])->name('tags.store');
    Route::delete('/tags/bulk', [AdminTagController::class, 'bulkDestroy'])->name('tags.bulkDestroy');
    Route::get('/tags/{tag}/edit', [AdminTagController::class, 'edit'])->name('tags.edit');
    Route::put('/tags/{tag}', [AdminTagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [AdminTagController::class, 'destroy'])->name('tags.destroy');

    Route::get('/authors', [AdminAuthorController::class, 'index'])->name('authors.index');
    Route::get('/authors/create', [AdminAuthorController::class, 'create'])->name('authors.create');
    Route::post('/authors', [AdminAuthorController::class, 'store'])->name('authors.store');
    Route::get('/authors/{author}/edit', [AdminAuthorController::class, 'edit'])->name('authors.edit');
    Route::put('/authors/{author}', [AdminAuthorController::class, 'update'])->name('authors.update');
    Route::delete('/authors/{author}', [AdminAuthorController::class, 'destroy'])->name('authors.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/toggle-role', [AdminUserController::class, 'toggleRole'])->name('users.toggleRole');
    Route::patch('/users/{user}/toggle-ban', [AdminUserController::class, 'toggleBan'])->name('users.toggleBan');

    Route::get('/comments', [AdminCommentController::class, 'index'])->name('comments.index');
    Route::post('/comments/bulk', [AdminCommentController::class, 'bulk'])->name('comments.bulk');
    Route::patch('/comments/{comment}/approve', [AdminCommentController::class, 'approve'])->name('comments.approve');
    Route::patch('/comments/{comment}/hide', [AdminCommentController::class, 'hide'])->name('comments.hide');
    Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/{id}/restore', [AdminCommentController::class, 'restore'])->name('comments.restore');
    Route::post('/comments/{comment}/ban-user', [AdminCommentController::class, 'banUser'])->name('comments.banUser');

    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::patch('/reports/{report}/status', [AdminReportController::class, 'updateStatus'])->name('reports.updateStatus');
    Route::delete('/reports/{report}', [AdminReportController::class, 'destroy'])->name('reports.destroy');

    Route::get('/schedules', [AdminScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules', [AdminScheduleController::class, 'store'])->name('schedules.store');
    Route::delete('/schedules/{schedule}', [AdminScheduleController::class, 'destroy'])->name('schedules.destroy');

    Route::get('/banners', [AdminBannerController::class, 'index'])->name('banners.index');
    Route::post('/banners', [AdminBannerController::class, 'store'])->name('banners.store');
    Route::put('/banners/{banner}', [AdminBannerController::class, 'update'])->name('banners.update');
    Route::patch('/banners/{banner}/toggle-active', [AdminBannerController::class, 'toggleActive'])->name('banners.toggleActive');
    Route::delete('/banners/{banner}', [AdminBannerController::class, 'destroy'])->name('banners.destroy');

    Route::get('/logs', [AdminAuditLogController::class, 'index'])->name('logs.index');
    Route::delete('/logs/clear', [AdminAuditLogController::class, 'clear'])->name('logs.clear');
    Route::get('/permissions', fn () => view('admin.permissions.index'))->name('permissions.index');
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
});
