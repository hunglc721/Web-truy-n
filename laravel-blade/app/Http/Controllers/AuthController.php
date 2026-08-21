<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ Email.',
            'email.email' => 'Email không đúng định dạng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            /** @var User $user */
            $user = Auth::user();

            if ($user->isBanned()) {
                Auth::logout();
                Log::warning('auth.login_banned', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);

                return back()->withInput()->withErrors([
                    'email' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Admin.',
                ]);
            }

            $request->session()->regenerate();
            ActivityLog::record('auth.login', $user, [
                'role' => $user->roleSlug(),
                'can_access_admin' => $user->canAccessAdmin(),
            ]);

            if ($user->canAccessAdmin()) {
                return redirect()->intended(route('admin.dashboard'))
                    ->with('success', 'Chào mừng ' . $user->name . ' quay trở lại khu quản trị!');
            }

            return redirect()->intended(route('user.library'))
                ->with('success', 'Đăng nhập thành công! Chào mừng ' . $user->name);
        }

        Log::warning('auth.login_failed', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
        ]);

        return back()->withInput()->withErrors([
            'email' => 'Thông tin đăng nhập (Email hoặc Mật khẩu) không chính xác.',
        ]);
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'name.required' => 'Vui lòng nhập họ tên của bạn.',
            'email.required' => 'Vui lòng nhập địa chỉ Email.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'password.min' => 'Mật khẩu tối thiểu phải từ 6 ký tự.',
        ]);

        $memberRoleId = Role::where('slug', 'member')->value('id');
        $user = User::create([
            'name' => trim($request->input('name')),
            'email' => strtolower(trim($request->input('email'))),
            'password' => Hash::make($request->input('password')),
            'is_admin' => false,
            'role_id' => $memberRoleId,
        ]);

        Auth::login($user);
        ActivityLog::record('auth.register', $user, ['role' => 'member']);

        return redirect()->route('user.library')
            ->with('success', 'Đăng ký tài khoản thành công! Tủ sách cá nhân của bạn đã sẵn sàng.');
    }

    public function logout(Request $request)
    {
        ActivityLog::record('auth.logout', Auth::user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Đã đăng xuất tài khoản thành công.');
    }
}
