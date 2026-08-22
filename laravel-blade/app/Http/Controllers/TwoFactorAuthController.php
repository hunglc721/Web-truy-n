<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorAuthController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactorService
    ) {}

    public function show(Request $request)
    {
        $user = $request->user();
        $has2FA = $user->hasTwoFactorEnabled();

        $secret = null;
        $qrCodeUrl = null;

        if (!$has2FA) {
            // Lấy secret đang chờ kích hoạt trong session hoặc tạo mới
            $secret = $request->session()->get('2fa_pending_secret') ?? $this->twoFactorService->generateSecretKey();
            $request->session()->put('2fa_pending_secret', $secret);
            $qrCodeUrl = $this->twoFactorService->getQrCodeUrl($user, $secret);
        }

        return view('auth.two-factor', compact('user', 'has2FA', 'secret', 'qrCodeUrl'));
    }

    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.required' => 'Vui lòng nhập mã xác thực 6 chữ số từ ứng dụng.',
            'code.size'     => 'Mã xác thực gồm 6 chữ số.',
        ]);

        $user = $request->user();
        $secret = $request->session()->get('2fa_pending_secret');

        if (!$secret || !$this->twoFactorService->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Mã xác thực 2FA không chính xác hoặc đã hết hạn.']);
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret'         => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at'   => now(),
        ])->save();

        $request->session()->forget('2fa_pending_secret');
        $request->session()->put('2fa_passed', true);
        ActivityLog::record('auth.2fa_enabled', $user);

        return redirect()->route('2fa.show')->with('success', 'Đã kích hoạt xác thực 2 bước (2FA) thành công! Hãy lưu lại mã khôi phục.')
            ->with('recovery_codes', $recoveryCodes);
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu để tắt 2FA.',
        ]);

        $user = $request->user();
        if (!\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Mật khẩu không chính xác.']);
        }

        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();

        ActivityLog::record('auth.2fa_disabled', $user);

        return redirect()->route('2fa.show')->with('success', 'Đã tắt xác thực 2 bước (2FA).');
    }

    public function showChallenge()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (session('2fa_passed')) {
            return redirect()->route('home');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasTwoFactorEnabled()) {
            return redirect()->route('home');
        }

        // 1. Kiểm tra nếu nhập mã 6 số TOTP
        if ($request->filled('code')) {
            $secret = decrypt($user->two_factor_secret);
            if ($this->twoFactorService->verifyKey($secret, $request->code)) {
                $request->session()->put('2fa_passed', true);
                return redirect()->intended($user->canAccessAdmin() ? route('admin.dashboard') : route('user.library'));
            }
        }

        // 2. Kiểm tra nếu nhập mã khôi phục (Recovery code)
        if ($request->filled('recovery_code')) {
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?? [];
            $inputCode = trim($request->recovery_code);

            if (($key = array_search($inputCode, $recoveryCodes, true)) !== false) {
                // Xóa mã đã sử dụng
                unset($recoveryCodes[$key]);
                $user->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode(array_values($recoveryCodes))),
                ])->save();

                $request->session()->put('2fa_passed', true);
                ActivityLog::record('auth.2fa_recovery_used', $user);

                return redirect()->intended($user->canAccessAdmin() ? route('admin.dashboard') : route('user.library'))
                    ->with('warning', 'Bạn đã sử dụng một mã khôi phục. Hãy tạo mới mã khôi phục trong trang cài đặt bảo mật.');
            }
        }

        return back()->withErrors(['code' => 'Mã xác thực hoặc mã khôi phục không chính xác.']);
    }
}
