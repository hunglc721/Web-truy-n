<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Kiểm tra người dùng đã đăng nhập và có quyền admin
        if (auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->is_admin)) {
            return $next($request);
        }

        return redirect('/')->with('error', 'Bạn không có quyền truy cập trang quản trị!');
    }
}
