<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Chuyển hướng tới trang xác thực của nhà cung cấp (Google / Facebook).
     */
    public function redirect(string $provider)
    {
        $allowedProviders = ['google', 'facebook'];
        if (!in_array($provider, $allowedProviders, true)) {
            return redirect()->route('login')->withErrors(['email' => 'Nhà cung cấp đăng nhập không được hỗ trợ.']);
        }

        // Nếu đã cài laravel/socialite và cấu hình Client ID
        if (class_exists(\Laravel\Socialite\Facades\Socialite::class) && config("services.{$provider}.client_id")) {
            return \Laravel\Socialite\Facades\Socialite::driver($provider)->redirect();
        }

        // Mô phỏng / Chế độ DEV: chuyển hướng callback với thông tin giả lập nếu chưa có API key thực tế
        return redirect()->route('auth.social.callback', [
            'provider' => $provider,
            'dev_mock' => 1,
        ]);
    }

    /**
     * Xử lý dữ liệu trả về từ Google / Facebook OAuth.
     */
    public function callback(Request $request, string $provider)
    {
        $allowedProviders = ['google', 'facebook'];
        if (!in_array($provider, $allowedProviders, true)) {
            return redirect()->route('login')->withErrors(['email' => 'Nhà cung cấp đăng nhập không hợp lệ.']);
        }

        $socialUser = null;

        if (class_exists(\Laravel\Socialite\Facades\Socialite::class) && config("services.{$provider}.client_id")) {
            try {
                $socialUser = \Laravel\Socialite\Facades\Socialite::driver($provider)->user();
            } catch (\Throwable $e) {
                return redirect()->route('login')->withErrors(['email' => 'Không thể đăng nhập bằng ' . ucfirst($provider) . ': ' . $e->getMessage()]);
            }
        } elseif ($request->boolean('dev_mock')) {
            // Mock social user for test / dev environment
            $socialUser = (object) [
                'id'       => 'mock_' . $provider . '_' . Str::random(8),
                'name'     => 'Người Dùng ' . ucfirst($provider),
                'email'    => 'user_' . Str::random(6) . '@' . $provider . '.com',
                'avatar'   => 'https://ui-avatars.com/api/?name=Social+User',
            ];
        }

        if (!$socialUser || empty($socialUser->email)) {
            return redirect()->route('login')->withErrors(['email' => 'Không nhận được thông tin email từ ' . ucfirst($provider) . '.']);
        }

        // 1. Tìm tài khoản theo OAuth ID
        $user = User::where('oauth_provider', $provider)
            ->where('oauth_id', $socialUser->id)
            ->first();

        // 2. Nếu chưa liên kết, tìm theo Email
        if (!$user) {
            $user = User::where('email', $socialUser->email)->first();

            if ($user) {
                // Liên kết tài khoản hiện có
                $user->forceFill([
                    'oauth_provider' => $provider,
                    'oauth_id'       => $socialUser->id,
                    'avatar'         => $user->avatar ?: ($socialUser->avatar ?? null),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            } else {
                // Tạo tài khoản mới
                $memberRoleId = Role::where('slug', 'member')->value('id');
                $user = User::create([
                    'name'              => $socialUser->name ?: 'Độc giả ' . ucfirst($provider),
                    'email'             => strtolower($socialUser->email),
                    'password'          => Hash::make(Str::random(32)),
                    'role_id'           => $memberRoleId,
                    'oauth_provider'    => $provider,
                    'oauth_id'          => $socialUser->id,
                    'avatar'            => $socialUser->avatar ?? null,
                    'email_verified_at' => now(),
                ]);
            }
        }

        if ($user->isBanned()) {
            return redirect()->route('login')->withErrors(['email' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Admin.']);
        }

        Auth::login($user, true);
        $request->session()->put('2fa_passed', true);
        ActivityLog::record('auth.social_login', $user, ['provider' => $provider]);

        return redirect()->intended(route('user.library'))
            ->with('success', 'Đăng nhập thành công với tài khoản ' . ucfirst($provider) . '!');
    }
}
