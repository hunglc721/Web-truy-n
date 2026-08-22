<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectChapterImages
{
    /**
     * Chống hotlink trực tiếp từ các trang web bên ngoài.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Cho phép nếu có Temporary Signed URL hợp lệ
        if ($request->hasValidSignature()) {
            return $next($request);
        }

        // 2. Kiểm tra Referer Header
        $referer = $request->header('referer');
        if (!empty($referer)) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $appHost     = parse_url(config('app.url'), PHP_URL_HOST) ?? $request->getHost();

            // Nếu Referer từ bên ngoài không khớp domain của trang web
            if ($refererHost && !str_ends_with(strtolower($refererHost), strtolower($appHost))) {
                return response('Hotlinking is not allowed on this platform.', 403, [
                    'Content-Type' => 'text/plain',
                ]);
            }
        }

        return $next($request);
    }
}
