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
use App\Http\Controllers\Admin\AdminComicController;
use App\Http\Controllers\Admin\AdminGenreController;
use App\Http\Controllers\Admin\AdminTagController;
use App\Http\Controllers\Admin\AdminAuthorController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminChapterController;
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
// Constraint slug: chỉ accept [a-z0-9-] để chặn URL rác và tránh route conflict
Route::get('/truyen/{slug}', [ComicController::class, 'show'])
    ->name('comics.show')
    ->where('slug', '[a-z0-9\-]+');

Route::get('/truyen/{comicSlug}/{chapterSlug}', [ChapterController::class, 'show'])
    ->name('chapters.show')
    ->where(['comicSlug' => '[a-z0-9\-]+', 'chapterSlug' => '[a-z0-9\-]+']);

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

    Route::post('/api/reading-history', [ChapterController::class, 'saveHistory'])->name('history.save');
    Route::post('/api/comments', [CommentController::class, 'store'])->name('comments.store');

    // ── Tủ Sách & Lượt Thích (AJAX JSON) ───────────────────────────
    Route::post('/api/comics/{comicId}/toggle-library', [ComicActionController::class, 'toggleLibrary'])->name('comics.toggleLibrary');
    Route::post('/api/comics/{comicId}/toggle-like', [ComicActionController::class, 'toggleLike'])->name('comics.toggleLike');
});

// --- ROUTE ADMIN (Bảo mật với Auth + AdminMiddleware) ---
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard redirect
    Route::get('/', fn() => redirect()->route('admin.comics.index'))->name('dashboard');

    // ── Quản lý Truyện ──────────────────────────────────────────
    Route::get('/comics', [AdminComicController::class, 'index'])->name('comics.index');
    Route::get('/comics/create', [AdminComicController::class, 'create'])->name('comics.create');
    Route::post('/comics', [AdminComicController::class, 'store'])->name('comics.store');
    Route::get('/comics/{id}/edit', [AdminComicController::class, 'edit'])->name('comics.edit');
    Route::put('/comics/{id}', [AdminComicController::class, 'update'])->name('comics.update');
    Route::delete('/comics/{id}', [AdminComicController::class, 'destroy'])->name('comics.destroy');

    // Quản lý Chapter (Bulk upload & CRUD nested dưới comic)
    Route::get('/comics/{comic}/chapters', [AdminChapterController::class, 'index'])->name('comics.chapters.index');
    Route::get('/comics/{comic}/chapters/create', [AdminChapterController::class, 'create'])->name('comics.chapters.create');
    Route::post('/comics/{comic}/chapters', [AdminChapterController::class, 'store'])->name('comics.chapters.store');
    Route::get('/comics/{comic}/chapters/{chapter}/edit', [AdminChapterController::class, 'edit'])->name('comics.chapters.edit');
    Route::put('/comics/{comic}/chapters/{chapter}', [AdminChapterController::class, 'update'])->name('comics.chapters.update');
    Route::delete('/comics/{comic}/chapters/{chapter}', [AdminChapterController::class, 'destroy'])->name('comics.chapters.destroy');

    // ── Quản lý Thể loại (Genres) ───────────────────────────────
    Route::get('/genres', [AdminGenreController::class, 'index'])->name('genres.index');
    Route::get('/genres/create', [AdminGenreController::class, 'create'])->name('genres.create');
    Route::post('/genres', [AdminGenreController::class, 'store'])->name('genres.store');
    Route::get('/genres/{genre}/edit', [AdminGenreController::class, 'edit'])->name('genres.edit');
    Route::put('/genres/{genre}', [AdminGenreController::class, 'update'])->name('genres.update');
    Route::delete('/genres/{genre}', [AdminGenreController::class, 'destroy'])->name('genres.destroy');

    // ── Quản lý Tags ────────────────────────────────────────────
    Route::get('/tags', [AdminTagController::class, 'index'])->name('tags.index');
    Route::get('/tags/create', [AdminTagController::class, 'create'])->name('tags.create');
    Route::post('/tags', [AdminTagController::class, 'store'])->name('tags.store');
    Route::get('/tags/{tag}/edit', [AdminTagController::class, 'edit'])->name('tags.edit');
    Route::put('/tags/{tag}', [AdminTagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [AdminTagController::class, 'destroy'])->name('tags.destroy');

    // ── Quản lý Tác giả (Authors) ───────────────────────────────
    Route::get('/authors', [AdminAuthorController::class, 'index'])->name('authors.index');
    Route::get('/authors/create', [AdminAuthorController::class, 'create'])->name('authors.create');
    Route::post('/authors', [AdminAuthorController::class, 'store'])->name('authors.store');
    Route::get('/authors/{author}/edit', [AdminAuthorController::class, 'edit'])->name('authors.edit');
    Route::put('/authors/{author}', [AdminAuthorController::class, 'update'])->name('authors.update');
    Route::delete('/authors/{author}', [AdminAuthorController::class, 'destroy'])->name('authors.destroy');

    // ── Quản lý Thành viên (Users) ──────────────────────────────
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/toggle-role', [AdminUserController::class, 'toggleRole'])->name('users.toggleRole');
    Route::patch('/users/{user}/toggle-ban', [AdminUserController::class, 'toggleBan'])->name('users.toggleBan');

    // ── Tương tác & Vận hành & Hệ thống ───────────────────────────
    Route::get('/comments', fn() => view('admin.comments.index'))->name('comments.index');
    Route::get('/reports', fn() => view('admin.reports.index'))->name('reports.index');
    Route::get('/schedules', fn() => view('admin.schedules.index'))->name('schedules.index');
    Route::get('/banners', fn() => view('admin.banners.index'))->name('banners.index');
    Route::get('/permissions', fn() => view('admin.permissions.index'))->name('permissions.index');
    Route::get('/settings', fn() => view('admin.settings.index'))->name('settings.index');
});
