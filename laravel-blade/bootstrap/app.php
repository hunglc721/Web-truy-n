<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ── Scoped Route Model Binding ────────────────────────────────────────
        // Đảm bảo khi route có cả {comic} và {chapter},
        // Laravel tự động scope chapter phải thuộc comic đó.
        // Thay vì tự query thủ công, binding làm điều này tự động.
        $middleware->scopeApiBindings();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

