<?php

namespace App\Observers;

use App\Models\Banner;
use Illuminate\Support\Facades\Cache;

/**
 * BannerObserver — Tự động xóa cache `home.banners` khi banner thay đổi.
 */
class BannerObserver
{
    public function saved(Banner $banner): void
    {
        Cache::forget('home.banners');
    }

    public function deleted(Banner $banner): void
    {
        Cache::forget('home.banners');
    }
}
