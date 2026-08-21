<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingController extends Controller
{
    private const DEFAULTS = [
        'site_name' => 'WebComics',
        'tagline' => 'Đọc Manga, Manhwa & Manhua Online',
        'meta_description' => 'Nền tảng đọc truyện tranh trực tuyến WebComics.',
        'seo_keywords' => 'đọc truyện,manga,manhwa,manhua,webtoon',
        'facebook_url' => '',
        'twitter_url' => '',
        'discord_url' => '',
        'google_analytics_id' => '',
        'maintenance_mode' => false,
        'maintenance_message' => 'Hệ thống đang bảo trì. Vui lòng quay lại sau.',
        'maintenance_ips' => '',
    ];

    public function index(): View
    {
        $settings = [];

        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = Setting::valueOf($key, $default);
        }

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'discord_url' => ['nullable', 'url', 'max:255'],
            'google_analytics_id' => ['nullable', 'string', 'max:50'],
            'maintenance_mode' => ['nullable', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
            'maintenance_ips' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['maintenance_mode'] = $request->boolean('maintenance_mode');

        foreach ($data as $key => $value) {
            Setting::putValue(
                $key,
                $value,
                $key === 'maintenance_mode' ? 'bool' : 'string',
                auth()->id()
            );
        }

        ActivityLog::record('admin.settings.updated', null, [
            'keys' => array_keys($data),
            'maintenance_mode' => $data['maintenance_mode'],
        ]);

        return back()->with('success', 'Đã lưu cài đặt website.');
    }
}
