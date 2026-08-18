<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Giao diện Đăng nhập
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    /**
     * Xử lý Đăng nhập với Validation & CSRF Protection
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'Vui lòng nhập địa chỉ Email.',
            'email.email'       => 'Email không đúng định dạng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        // Kiểm tra thông tin đăng nhập
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Kiểm tra nếu tài khoản bị khóa
            if ($user->isBanned()) {
                Auth::logout();
                return back()->withInput()->withErrors([
                    'email' => 'Tài khoản của bạn đã bị khóa đến ' . $user->banned_at->format('d/m/Y H:i') . '. Vui lòng liên hệ Admin.',
                ]);
            }

            $request->session()->regenerate();

            // Nếu là admin -> chuyển tới admin dashboard, ngược lại về trang chủ hoặc tủ sách
            if ($user->is_admin) {
                return redirect()->intended(route('admin.dashboard'))
                    ->with('success', 'Chào mừng Admin ' . $user->name . ' quay trở lại!');
            }

            return redirect()->intended(route('user.library'))
                ->with('success', 'Đăng nhập thành công! Chào mừng ' . $user->name);
        }

        return back()->withInput()->withErrors([
            'email' => 'Thông tin đăng nhập (Email hoặc Mật khẩu) không chính xác.',
        ]);
    }

    /**
     * Giao diện Đăng ký
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.register');
    }

    /**
     * Xử lý Đăng ký tài khoản người dùng mới
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'name.required'     => 'Vui lòng nhập họ tên của bạn.',
            'email.required'    => 'Vui lòng nhập địa chỉ Email.',
            'email.unique'      => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed'=> 'Mật khẩu xác nhận không khớp.',
            'password.min'      => 'Mật khẩu tối thiểu phải từ 6 ký tự.',
        ]);

        $user = User::create([
            'name'     => trim($request->input('name')),
            'email'    => strtolower(trim($request->input('email'))),
            'password' => Hash::make($request->input('password')),
            'is_admin' => false,
        ]);

        Auth::login($user);

        return redirect()->route('user.library')
            ->with('success', 'Đăng ký tài khoản thành công! Tủ sách cá nhân của bạn đã sẵn sàng.');
    }

    /**
     * Xử lý Đăng xuất
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Đã đăng xuất tài khoản thành công.');
    }
}
