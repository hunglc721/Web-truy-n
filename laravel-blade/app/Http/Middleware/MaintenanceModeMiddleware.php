<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceModeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Admin area + authentication must remain reachable during maintenance.
        if ($request->is('admin', 'admin/*', 'login', 'logout')) {
            return $next($request);
        }

        if (auth()->check() && auth()->user()->isAdmin()) {
            return $next($request);
        }

        try {
            if (!Setting::valueOf('maintenance_mode', false)) {
                return $next($request);
            }

            $allowedIps = collect(explode(',', (string) Setting::valueOf('maintenance_ips', '')))
                ->map(fn ($ip) => trim($ip))
                ->filter();

            if ($allowedIps->contains($request->ip())) {
                return $next($request);
            }

            return response()
                ->view('errors.maintenance', [
                    'message' => Setting::valueOf(
                        'maintenance_message',
                        'Hệ thống đang bảo trì. Vui lòng quay lại sau.'
                    ),
                ], 503)
                ->header('Retry-After', '3600');
        } catch (\Throwable) {
            // Fresh installs may not have migrated the settings table yet.
            return $next($request);
        }
    }
}
