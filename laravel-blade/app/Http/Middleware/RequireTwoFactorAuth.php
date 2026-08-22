<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasTwoFactorEnabled()) {
            if (!$request->session()->get('2fa_passed', false)) {
                return redirect()->route('2fa.challenge');
            }
        }

        return $next($request);
    }
}
