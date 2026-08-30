<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\MaintenanceModeMiddleware::class,
            \App\Http\Middleware\EnsureUserNotBanned::class,
        ]);

        $middleware->alias([
            'admin'       => \App\Http\Middleware\AdminMiddleware::class,
            'permission'  => \App\Http\Middleware\PermissionMiddleware::class,
            'not_banned'  => \App\Http\Middleware\EnsureUserNotBanned::class,
            'maintenance' => \App\Http\Middleware\MaintenanceModeMiddleware::class,
            '2fa'         => \App\Http\Middleware\RequireTwoFactorAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Không tìm thấy tài nguyên yêu cầu.',
                    'code'    => 404,
                ], 404);
            }

            return response()->view('errors.404', [
                'message' => 'Trang hoặc nội dung bạn tìm kiếm không tồn tại.',
            ], 404);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            $model = class_basename($e->getModel());

            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "{$model} không tồn tại.",
                    'code'    => 404,
                ], 404);
            }

            return response()->view('errors.404', [
                'message' => "{$model} không tồn tại hoặc đã bị xóa.",
            ], 404);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage() ?: 'Bạn không có quyền thực hiện hành động này.',
                    'code'    => 403,
                ], 403);
            }

            return response()->view('errors.403', [
                'message' => $e->getMessage() ?: 'Bạn không có quyền truy cập trang này.',
            ], 403);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Vui lòng đăng nhập để tiếp tục.',
                    'code'    => 401,
                ], 401);
            }

            return redirect()->guest(route('login'))
                ->with('error', 'Vui lòng đăng nhập để tiếp tục.');
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Phương thức HTTP không được hỗ trợ.',
                    'code'    => 405,
                ], 405);
            }

            return response()->view('errors.405', [], 405);
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 429) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;

                if ($request->expectsJson()) {
                    return response()->json([
                        'status'      => 'error',
                        'message'     => 'Bạn thao tác quá nhanh. Vui lòng thử lại sau ' . $retryAfter . ' giây.',
                        'retry_after' => (int) $retryAfter,
                        'code'        => 429,
                    ], 429, ['Retry-After' => $retryAfter]);
                }

                return response()->view('errors.429', [
                    'retry_after' => $retryAfter,
                ], 429);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors'  => $e->errors(),
                    'code'    => 422,
                ], 422);
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($e instanceof AuthenticationException
                || $e instanceof AuthorizationException
                || $e instanceof ValidationException
                || $e instanceof NotFoundHttpException
                || $e instanceof ModelNotFoundException
                || $e instanceof MethodNotAllowedHttpException
                || $e instanceof HttpException
                || $e instanceof \Illuminate\Http\Exceptions\HttpResponseException
            ) {
                return null;
            }

            Log::error('Unhandled exception', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'url'       => $request->fullUrl(),
                'method'    => $request->method(),
                'user_id'   => auth()->id(),
                'ip'        => $request->ip(),
                'user_agent'=> $request->userAgent(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => app()->isProduction()
                        ? 'Hệ thống gặp sự cố. Vui lòng thử lại sau.'
                        : $e->getMessage(),
                    'code'    => 500,
                ], 500);
            }

            if (app()->isProduction()) {
                return response()->view('errors.500', [], 500);
            }

            return null;
        });

        $exceptions->dontReport([
            AuthenticationException::class,
            AuthorizationException::class,
            ValidationException::class,
            NotFoundHttpException::class,
        ]);
    })->create();
