<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Task 9: Chuẩn hóa – chỉ dùng is_admin (cột role không tồn tại trong schema)
        if (auth()->check() && auth()->user()->isAdmin()) {
            return $next($request);
        }

        return redirect('/')->with('error', 'Bạn không có quyền truy cập trang quản trị!');
    }
}
