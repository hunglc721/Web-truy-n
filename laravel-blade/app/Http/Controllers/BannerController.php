<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\RedirectResponse;

class BannerController extends Controller
{
    public function click(Banner $banner): RedirectResponse
    {
        if (!$banner->is_active || $banner->is_expired || $banner->is_scheduled) {
            return redirect()->route('home');
        }

        $banner->increment('clicks_count');

        $target = trim((string) $banner->link_url);
        if ($target === '') {
            return redirect()->route('home');
        }

        if (str_starts_with($target, '/')) {
            return redirect($target);
        }

        if (filter_var($target, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string) parse_url($target, PHP_URL_SCHEME));
            if (in_array($scheme, ['http', 'https'], true)) {
                return redirect()->away($target);
            }
        }

        return redirect()->route('home');
    }
}
