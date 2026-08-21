<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->canAccessAdmin()) {
            return $next($request);
        }

        return redirect('/')->with('error', 'Bạn không có quyền truy cập trang quản trị!');
    }
}
