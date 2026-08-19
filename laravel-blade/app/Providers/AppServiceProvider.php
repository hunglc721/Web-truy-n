<?php

namespace App\Providers;

use App\Models\Comment;
use App\Policies\CommentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix #3 – Đăng ký CommentPolicy cho model Comment
        // Gate tự động map: 'create' → CommentPolicy::create(), 'delete' → CommentPolicy::delete()
        Gate::policy(Comment::class, CommentPolicy::class);
    }
}
