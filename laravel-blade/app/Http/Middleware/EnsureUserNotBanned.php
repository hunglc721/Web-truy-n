<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotBanned
{
    /**
     * Handle an incoming request.
     *
     * Kiểm tra cờ ban của người dùng hiện tại.
     * Nếu tài khoản đã bị khóa:
     *  - Đăng xuất ngay lập tức
     *  - Vô hiệu hóa session & token
     *  - Trả về JSON (nếu AJAX/API) hoặc chuyển hướng về trang login kèm thông báo lỗi.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isBanned()) {
            $message = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để biết thêm chi tiết.';

            Auth::logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $message,
                    'code'    => 403,
                ], 403);
            }

            return redirect()->route('login')->with('error', $message);
        }

        return $next($request);
    }
}
