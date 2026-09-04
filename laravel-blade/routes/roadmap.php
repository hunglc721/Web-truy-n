<?php

use App\Http\Controllers\ChapterCatalogController;
use App\Http\Controllers\ComicReleaseMetaController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\ScheduleCompletedController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/api/comics/{comic:slug}/chapters', [ChapterCatalogController::class, 'index'])
    ->middleware('throttle:api')
    ->name('api.comics.chapters.index');

Route::get('/api/comics/{comic:slug}/release-meta', [ComicReleaseMetaController::class, 'show'])
    ->middleware('throttle:api')
    ->name('api.comics.releaseMeta');

Route::get('/tags/{slug}', [TagController::class, 'show'])->name('tags.show');
Route::get('/schedule/completed', [ScheduleCompletedController::class, 'index'])->name('schedule.completed');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/about', [PublicPageController::class, 'about'])->name('pages.about');
Route::get('/terms', [PublicPageController::class, 'terms'])->name('pages.terms');
Route::get('/privacy', [PublicPageController::class, 'privacy'])->name('pages.privacy');
Route::get('/contact', [PublicPageController::class, 'contact'])->name('pages.contact');
Route::post('/contact', [PublicPageController::class, 'submitContact'])
    ->middleware('throttle:6,1')
    ->name('pages.contact.submit');
