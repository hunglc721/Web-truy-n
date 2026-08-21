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
use App\Http\Middleware\AdminMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes - WebComics (Production-Ready SEO & Protection)
|--------------------------------------------------------------------------
*/

// --- ROUTE PUBLIC ---
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/genres', [GenreController::class, 'index'])->name('genres');
Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');
Route::get('/originals', [OriginalsController::class, 'index'])->name('originals');

// Chuẩn SEO URL cho Chi tiết truyện & Đọc chương
Route::get('/truyen/{slug}', [ComicController::class, 'show'])->name('comics.show');
Route::get('/truyen/{comicSlug}/{chapterSlug}', [ChapterController::class, 'show'])->name('chapters.show');

// --- ROUTE AUTHENTICATION ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// --- ROUTE USER TỦ SÁCH CÁ NHÂN & LỊCH SỬ ĐỌC ---
Route::middleware('auth')->group(function () {
    Route::get('/user/library', [LibraryController::class, 'index'])->name('user.library');
    Route::post('/user/library/toggle/{comic}', [LibraryController::class, 'toggle'])->name('library.toggle');
    Route::delete('/user/history/clear', [LibraryController::class, 'clearHistory'])->name('history.clear');

    // ── Lịch sử đọc (throttle: 60 req/phút – cuộn trang liên tục) ──────
    Route::post('/api/reading-history', [ChapterController::class, 'saveHistory'])
        ->middleware('throttle:history-save')
        ->name('history.save');

    // ── Bình luận (throttle: 5 req/phút – chặn spam bình luận) ─────────
    Route::post('/api/comments', [CommentController::class, 'store'])
        ->middleware('throttle:comments')
        ->name('comments.store');

    // PATCH /api/comments/{comment} — sửa bình luận (Policy: chủ BL trong 15p / admin)
    Route::patch('/api/comments/{comment}', [CommentController::class, 'update'])
        ->name('comments.update');

    // DELETE /api/comments/{comment} — xóa mềm (Policy: chủ BL / admin)
    Route::delete('/api/comments/{comment}', [CommentController::class, 'destroy'])
        ->name('comments.destroy');

    // ── Tủ Sách & Lượt Thích (AJAX JSON) ───────────────────────────────
    Route::post('/api/comics/{comicId}/toggle-library', [ComicActionController::class, 'toggleLibrary'])
        ->middleware('throttle:library-toggle')
        ->name('comics.toggleLibrary');

    Route::post('/api/comics/{comicId}/toggle-like', [ComicActionController::class, 'toggleLike'])
        ->middleware('throttle:like-toggle')
        ->name('comics.toggleLike');
});

// ── API Bình luận (Công khai cho cả Guest & User đọc, Rate limit: 120 req/phút) ──
Route::get('/api/comments', [CommentController::class, 'index'])
    ->middleware('throttle:api')
    ->name('comments.index');

// ── API Gợi ý truyện (Công khai cho cả Guest & User, Rate limit: 120 req/phút) ──
Route::get('/api/recommendations', [RecommendationController::class, 'index'])
    ->middleware('throttle:api')
    ->name('recommendations.index');

// ── API Báo lỗi ảnh / Nội dung (Công khai cho cả Guest & User, Rate limit: 60 req/phút) ──
Route::post('/api/reports', [\App\Http\Controllers\ReportController::class, 'store'])
    ->middleware('throttle:api')
    ->name('reports.store');


// --- ROUTE ADMIN (Bảo mật với Auth + AdminMiddleware) ---
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard redirect
    Route::get('/', fn() => redirect()->route('admin.comics.index'))->name('dashboard');

    // ── Quản lý Truyện ──────────────────────────────────────────────────
    Route::get('/comics', [AdminComicController::class, 'index'])->name('comics.index');
    Route::get('/comics/create', [AdminComicController::class, 'create'])->name('comics.create');
    Route::post('/comics', [AdminComicController::class, 'store'])->name('comics.store');
    Route::get('/comics/{id}/edit', [AdminComicController::class, 'edit'])->name('comics.edit');
    Route::put('/comics/{id}', [AdminComicController::class, 'update'])->name('comics.update');
    Route::delete('/comics/{id}', [AdminComicController::class, 'destroy'])->name('comics.destroy');

    // Quản lý Chapter — scopeBindings() đảm bảo {chapter} phải thuộc {comic}
    // Tránh admin truy cập chapter của comic khác qua URL manipulation
    Route::prefix('/comics/{comic}/chapters')->name('comics.chapters.')->scopeBindings()->group(function () {
        Route::get('/', [AdminChapterController::class, 'index'])->name('index');
        Route::get('/create', [AdminChapterController::class, 'create'])->name('create');
        Route::post('/', [AdminChapterController::class, 'store'])->name('store');
        Route::get('/{chapter}/edit', [AdminChapterController::class, 'edit'])->name('edit');
        Route::put('/{chapter}', [AdminChapterController::class, 'update'])->name('update');
        Route::delete('/{chapter}', [AdminChapterController::class, 'destroy'])->name('destroy');
    });

    // ── Quản lý Thể loại (Genres) ───────────────────────────────────────
    Route::get('/genres', [AdminGenreController::class, 'index'])->name('genres.index');
    Route::get('/genres/create', [AdminGenreController::class, 'create'])->name('genres.create');
    Route::post('/genres', [AdminGenreController::class, 'store'])->name('genres.store');
    Route::get('/genres/{genre}/edit', [AdminGenreController::class, 'edit'])->name('genres.edit');
    Route::put('/genres/{genre}', [AdminGenreController::class, 'update'])->name('genres.update');
    Route::delete('/genres/{genre}', [AdminGenreController::class, 'destroy'])->name('genres.destroy');

    // ── Quản lý Tags ─────────────────────────────────────────────────────
    Route::get('/tags', [AdminTagController::class, 'index'])->name('tags.index');
    Route::get('/tags/create', [AdminTagController::class, 'create'])->name('tags.create');
    Route::post('/tags', [AdminTagController::class, 'store'])->name('tags.store');
    Route::get('/tags/{tag}/edit', [AdminTagController::class, 'edit'])->name('tags.edit');
    Route::put('/tags/{tag}', [AdminTagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [AdminTagController::class, 'destroy'])->name('tags.destroy');

    // ── Quản lý Tác giả (Authors) ───────────────────────────────────────
    Route::get('/authors', [AdminAuthorController::class, 'index'])->name('authors.index');
    Route::get('/authors/create', [AdminAuthorController::class, 'create'])->name('authors.create');
    Route::post('/authors', [AdminAuthorController::class, 'store'])->name('authors.store');
    Route::get('/authors/{author}/edit', [AdminAuthorController::class, 'edit'])->name('authors.edit');
    Route::put('/authors/{author}', [AdminAuthorController::class, 'update'])->name('authors.update');
    Route::delete('/authors/{author}', [AdminAuthorController::class, 'destroy'])->name('authors.destroy');

    // ── Quản lý Thành viên (Users) ──────────────────────────────────────
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/toggle-role', [AdminUserController::class, 'toggleRole'])->name('users.toggleRole');
    Route::patch('/users/{user}/toggle-ban', [AdminUserController::class, 'toggleBan'])->name('users.toggleBan');

    // ── Quản lý & Kiểm duyệt Bình luận (BE-09) ─────────────────────────
    Route::get('/comments', [AdminCommentController::class, 'index'])->name('comments.index');
    Route::patch('/comments/{comment}/approve', [AdminCommentController::class, 'approve'])->name('comments.approve');
    Route::patch('/comments/{comment}/hide', [AdminCommentController::class, 'hide'])->name('comments.hide');
    Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/{id}/restore', [AdminCommentController::class, 'restore'])->name('comments.restore');
    Route::post('/comments/{comment}/ban-user', [AdminCommentController::class, 'banUser'])->name('comments.banUser');

    // ── Trung Tâm Xử Lý Báo Cáo Sự Cố (BE-10 Report Center) ────────────
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::patch('/reports/{report}/status', [AdminReportController::class, 'updateStatus'])->name('reports.updateStatus');
    Route::delete('/reports/{report}', [AdminReportController::class, 'destroy'])->name('reports.destroy');

    // ── Quản lý Lịch Phát Sóng Tuần (BE-11) ────────────────────────────
    Route::get('/schedules', [AdminScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules', [AdminScheduleController::class, 'store'])->name('schedules.store');
    Route::delete('/schedules/{schedule}', [AdminScheduleController::class, 'destroy'])->name('schedules.destroy');

    // ── Quản lý Banner Quảng cáo Trang chủ (BE-12) ─────────────────────
    Route::get('/banners', [AdminBannerController::class, 'index'])->name('banners.index');
    Route::post('/banners', [AdminBannerController::class, 'store'])->name('banners.store');
    Route::put('/banners/{banner}', [AdminBannerController::class, 'update'])->name('banners.update');
    Route::patch('/banners/{banner}/toggle-active', [AdminBannerController::class, 'toggleActive'])->name('banners.toggleActive');
    Route::delete('/banners/{banner}', [AdminBannerController::class, 'destroy'])->name('banners.destroy');

    // ── Vận hành & Hệ thống ────────────────────────────────────────────
    Route::get('/permissions', fn() => view('admin.permissions.index'))->name('permissions.index');
    Route::get('/settings', fn() => view('admin.settings.index'))->name('settings.index');
});
