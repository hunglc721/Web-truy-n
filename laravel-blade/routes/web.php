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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AnnouncementController;
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
use App\Http\Controllers\Admin\AdminPermissionController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Middleware\AdminMiddleware;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/genres', [GenreController::class, 'index'])->name('genres');
Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');
Route::get('/originals', [OriginalsController::class, 'index'])->name('originals');
Route::get('/authors/{slug}', [\App\Http\Controllers\AuthorController::class, 'show'])->name('authors.show');
Route::get('/teams', [\App\Http\Controllers\TeamController::class, 'index'])->name('teams.index');
Route::get('/teams/{slug}', [\App\Http\Controllers\TeamController::class, 'show'])->name('teams.show');
Route::get('/lists', [\App\Http\Controllers\ReadingListController::class, 'index'])->name('lists.index');
Route::get('/lists/{slug}', [\App\Http\Controllers\ReadingListController::class, 'show'])->name('lists.show');
Route::get('/dmca', [\App\Http\Controllers\DmcaController::class, 'show'])->name('dmca.show');
Route::post('/dmca', [\App\Http\Controllers\DmcaController::class, 'store'])->name('dmca.store');
Route::get('/truyen/{slug}', [ComicController::class, 'show'])->name('comics.show');
Route::get('/truyen/{comicSlug}/{chapterSlug}', [ChapterController::class, 'show'])->name('chapters.show');
Route::get('/banners/{banner}/click', [BannerController::class, 'click'])->name('banners.click');
Route::get('/api/announcements/active', [AnnouncementController::class, 'active'])->name('announcements.active');
Route::post('/api/announcements/{announcement}/dismiss', [AnnouncementController::class, 'dismiss'])->name('announcements.dismiss');

Route::post('/api/lists', [\App\Http\Controllers\ReadingListController::class, 'store'])->middleware('auth')->name('api.lists.store');
Route::post('/api/lists/{id}/toggle-like', [\App\Http\Controllers\ReadingListController::class, 'toggleLike'])->middleware('auth')->name('api.lists.toggleLike');
Route::get('/api/wallet/balance', [\App\Http\Controllers\WalletController::class, 'balance'])->middleware('auth')->name('api.wallet.balance');
Route::post('/api/wallet/deposit', [\App\Http\Controllers\WalletController::class, 'deposit'])->middleware('auth')->name('api.wallet.deposit');
Route::post('/api/chapters/{chapterId}/unlock', [\App\Http\Controllers\WalletController::class, 'unlockChapter'])->middleware('auth')->name('api.chapters.unlock');
Route::post('/api/authors/{id}/follow', [\App\Http\Controllers\AuthorController::class, 'follow'])->name('api.authors.follow');
Route::post('/api/teams/{id}/follow', [\App\Http\Controllers\TeamController::class, 'follow'])->name('api.teams.follow');
Route::post('/api/push/subscribe', [\App\Http\Controllers\PushNotificationController::class, 'subscribe'])->name('api.push.subscribe');
Route::post('/api/push/unsubscribe', [\App\Http\Controllers\PushNotificationController::class, 'unsubscribe'])->name('api.push.unsubscribe');

Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\SocialAuthController::class, 'redirect'])->name('auth.social.redirect');
Route::get('/auth/{provider}/callback', [\App\Http\Controllers\SocialAuthController::class, 'callback'])->name('auth.social.callback');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Email Verification Routes
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [\App\Http\Controllers\EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [\App\Http\Controllers\EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');

    // 2FA Routes
    Route::get('/user/2fa', [\App\Http\Controllers\TwoFactorAuthController::class, 'show'])->name('2fa.show');
    Route::post('/user/2fa/enable', [\App\Http\Controllers\TwoFactorAuthController::class, 'enable'])->name('2fa.enable');
    Route::post('/user/2fa/disable', [\App\Http\Controllers\TwoFactorAuthController::class, 'disable'])->name('2fa.disable');
    Route::get('/2fa/challenge', [\App\Http\Controllers\TwoFactorAuthController::class, 'showChallenge'])->name('2fa.challenge');
    Route::post('/2fa/challenge', [\App\Http\Controllers\TwoFactorAuthController::class, 'verifyChallenge'])->name('2fa.challenge.verify');

    // Session Management
    Route::post('/user/logout-other-devices', [AuthController::class, 'logoutOtherDevices'])->name('user.logoutOtherDevices');
});

Route::middleware('auth')->group(function () {
    Route::get('/user', [UserDashboardController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/user/history', [UserDashboardController::class, 'history'])->name('user.history');
    Route::get('/user/likes', [UserDashboardController::class, 'likes'])->name('user.likes');
    Route::get('/user/comments', [UserDashboardController::class, 'comments'])->name('user.comments');
    Route::get('/user/ratings', [UserDashboardController::class, 'ratings'])->name('user.ratings');
    Route::get('/user/library', [LibraryController::class, 'index'])->name('user.library');
    Route::post('/user/library/toggle/{comic}', [LibraryController::class, 'toggle'])->name('library.toggle');
    Route::delete('/user/history/clear', [LibraryController::class, 'clearHistory'])->name('history.clear');

    Route::get('/user/notifications', [NotificationController::class, 'index'])->name('user.notifications.index');
    Route::get('/user/notifications/header', [NotificationController::class, 'header'])->name('user.notifications.header');
    Route::get('/user/notifications/{id}/open', [NotificationController::class, 'open'])->name('user.notifications.open');
    Route::patch('/user/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('user.notifications.readAll');
    Route::delete('/user/notifications/{id}', [NotificationController::class, 'destroy'])->name('user.notifications.destroy');

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
Route::get('/api/search/hot', [SearchController::class, 'hot'])->middleware('throttle:api')->name('search.hot');
Route::get('/api/search/advanced', [SearchController::class, 'advanced'])->middleware('throttle:api')->name('search.advanced');
Route::get('/api/comments', [CommentController::class, 'index'])->middleware('throttle:api')->name('comments.index');
Route::get('/api/recommendations', [RecommendationController::class, 'index'])->middleware('throttle:api')->name('recommendations.index');
Route::post('/api/reports', [\App\Http\Controllers\ReportController::class, 'store'])->middleware('throttle:api')->name('reports.store');

Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');
    Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->middleware('permission:analytics.view')->name('analytics.index');

    Route::get('/comics', [AdminComicController::class, 'index'])->middleware('permission:comics.view')->name('comics.index');
    Route::get('/comics/create', [AdminComicController::class, 'create'])->middleware('permission:comics.create')->name('comics.create');
    Route::post('/comics', [AdminComicController::class, 'store'])->middleware('permission:comics.create')->name('comics.store');
    Route::get('/comics/{id}/edit', [AdminComicController::class, 'edit'])->middleware('permission:comics.update')->name('comics.edit');
    Route::put('/comics/{id}', [AdminComicController::class, 'update'])->middleware('permission:comics.update')->name('comics.update');
    Route::delete('/comics/{id}', [AdminComicController::class, 'destroy'])->middleware('permission:comics.delete')->name('comics.destroy');

    Route::get('/chapters', [AdminChapterController::class, 'all'])->middleware('permission:chapters.view')->name('chapters.index');

    Route::prefix('/comics/{comic}/chapters')->name('comics.chapters.')->scopeBindings()->group(function () {
        Route::get('/', [AdminChapterController::class, 'index'])->middleware('permission:chapters.view')->name('index');
        Route::get('/create', [AdminChapterController::class, 'create'])->middleware('permission:chapters.create')->name('create');
        Route::post('/', [AdminChapterController::class, 'store'])->middleware('permission:chapters.create')->name('store');
        Route::get('/{chapter}/edit', [AdminChapterController::class, 'edit'])->middleware('permission:chapters.update')->name('edit');
        Route::put('/{chapter}', [AdminChapterController::class, 'update'])->middleware('permission:chapters.update')->name('update');
        Route::delete('/{chapter}', [AdminChapterController::class, 'destroy'])->middleware('permission:chapters.delete')->name('destroy');
    });

    Route::middleware('permission:genres.manage')->group(function () {
        Route::get('/genres', [AdminGenreController::class, 'index'])->name('genres.index');
        Route::get('/genres/create', [AdminGenreController::class, 'create'])->name('genres.create');
        Route::post('/genres', [AdminGenreController::class, 'store'])->name('genres.store');
        Route::delete('/genres/bulk', [AdminGenreController::class, 'bulkDestroy'])->name('genres.bulkDestroy');
        Route::get('/genres/{genre}/edit', [AdminGenreController::class, 'edit'])->name('genres.edit');
        Route::put('/genres/{genre}', [AdminGenreController::class, 'update'])->name('genres.update');
        Route::delete('/genres/{genre}', [AdminGenreController::class, 'destroy'])->name('genres.destroy');
    });

    Route::middleware('permission:tags.manage')->group(function () {
        Route::get('/tags', [AdminTagController::class, 'index'])->name('tags.index');
        Route::get('/tags/create', [AdminTagController::class, 'create'])->name('tags.create');
        Route::post('/tags', [AdminTagController::class, 'store'])->name('tags.store');
        Route::delete('/tags/bulk', [AdminTagController::class, 'bulkDestroy'])->name('tags.bulkDestroy');
        Route::get('/tags/{tag}/edit', [AdminTagController::class, 'edit'])->name('tags.edit');
        Route::put('/tags/{tag}', [AdminTagController::class, 'update'])->name('tags.update');
        Route::delete('/tags/{tag}', [AdminTagController::class, 'destroy'])->name('tags.destroy');
    });

    Route::middleware('permission:authors.manage')->group(function () {
        Route::get('/authors', [AdminAuthorController::class, 'index'])->name('authors.index');
        Route::get('/authors/create', [AdminAuthorController::class, 'create'])->name('authors.create');
        Route::post('/authors', [AdminAuthorController::class, 'store'])->name('authors.store');
        Route::get('/authors/{author}/edit', [AdminAuthorController::class, 'edit'])->name('authors.edit');
        Route::put('/authors/{author}', [AdminAuthorController::class, 'update'])->name('authors.update');
        Route::delete('/authors/{author}', [AdminAuthorController::class, 'destroy'])->name('authors.destroy');
    });

    Route::get('/users', [AdminUserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->middleware('permission:users.view')->name('users.show');
    Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->middleware('permission:users.manage_role')->name('users.updateRole');
    Route::patch('/users/{user}/toggle-role', [AdminUserController::class, 'toggleRole'])->middleware('permission:users.manage_role')->name('users.toggleRole');
    Route::patch('/users/{user}/toggle-ban', [AdminUserController::class, 'toggleBan'])->middleware('permission:users.ban')->name('users.toggleBan');

    Route::get('/comments', [AdminCommentController::class, 'index'])->middleware('permission:comments.view')->name('comments.index');
    Route::post('/comments/bulk', [AdminCommentController::class, 'bulk'])->middleware('permission:comments.moderate')->name('comments.bulk');
    Route::patch('/comments/{comment}/approve', [AdminCommentController::class, 'approve'])->middleware('permission:comments.moderate')->name('comments.approve');
    Route::patch('/comments/{comment}/hide', [AdminCommentController::class, 'hide'])->middleware('permission:comments.moderate')->name('comments.hide');
    Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy'])->middleware('permission:comments.moderate')->name('comments.destroy');
    Route::post('/comments/{id}/restore', [AdminCommentController::class, 'restore'])->middleware('permission:comments.moderate')->name('comments.restore');
    Route::post('/comments/{comment}/ban-user', [AdminCommentController::class, 'banUser'])->middleware('permission:comments.moderate')->name('comments.banUser');

    Route::get('/reports', [AdminReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
    Route::patch('/reports/{report}/status', [AdminReportController::class, 'updateStatus'])->middleware('permission:reports.manage')->name('reports.updateStatus');
    Route::delete('/reports/{report}', [AdminReportController::class, 'destroy'])->middleware('permission:reports.manage')->name('reports.destroy');

    Route::middleware('permission:schedules.manage')->group(function () {
        Route::get('/schedules', [AdminScheduleController::class, 'index'])->name('schedules.index');
        Route::post('/schedules', [AdminScheduleController::class, 'store'])->name('schedules.store');
        Route::put('/schedules/{schedule}', [AdminScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{schedule}', [AdminScheduleController::class, 'destroy'])->name('schedules.destroy');
    });

    Route::middleware('permission:banners.manage')->group(function () {
        Route::get('/banners', [AdminBannerController::class, 'index'])->name('banners.index');
        Route::post('/banners', [AdminBannerController::class, 'store'])->name('banners.store');
        Route::put('/banners/{banner}', [AdminBannerController::class, 'update'])->name('banners.update');
        Route::patch('/banners/{banner}/toggle-active', [AdminBannerController::class, 'toggleActive'])->name('banners.toggleActive');
        Route::delete('/banners/{banner}', [AdminBannerController::class, 'destroy'])->name('banners.destroy');
    });

    Route::middleware('permission:notifications.manage')->group(function () {
        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications', [AdminNotificationController::class, 'store'])->name('notifications.store');
        Route::patch('/notifications/{announcement}/toggle', [AdminNotificationController::class, 'toggle'])->name('notifications.toggle');
        Route::delete('/notifications/{announcement}', [AdminNotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    Route::get('/logs', [AdminAuditLogController::class, 'index'])->middleware('permission:audit.view')->name('logs.index');
    Route::delete('/logs/clear', [AdminAuditLogController::class, 'clear'])->middleware('permission:permissions.manage')->name('logs.clear');

    Route::get('/permissions', [AdminPermissionController::class, 'index'])->middleware('permission:permissions.manage')->name('permissions.index');
    Route::put('/permissions/{role}', [AdminPermissionController::class, 'update'])->middleware('permission:permissions.manage')->name('permissions.update');

    Route::get('/settings', [AdminSettingController::class, 'index'])->middleware('permission:settings.manage')->name('settings.index');
    Route::put('/settings', [AdminSettingController::class, 'update'])->middleware('permission:settings.manage')->name('settings.update');
});