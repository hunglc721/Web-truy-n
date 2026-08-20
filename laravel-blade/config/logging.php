<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

/**
 * WebComics — Logging Configuration
 *
 * Channels được định nghĩa:
 *  - stack         (default) → daily + security + queue
 *  - daily         → logs/laravel-YYYY-MM-DD.log (14 ngày giữ)
 *  - security      → logs/security.log  — auth events, bans, failed logins
 *  - queue         → logs/queue.log     — job dispatch, retry, failed
 *  - activity      → logs/activity.log  — activity log write failures
 *  - stderr        → stderr (for Docker / production containers)
 *  - null          → devnull (dùng khi testing)
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace'   => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    */

    'channels' => [

        // ── Main Stack: ghi vào daily log + security log ───────────────────
        'stack' => [
            'driver'            => 'stack',
            'channels'          => ['daily', 'security'],
            'ignore_exceptions' => false,
        ],

        // ── Daily — log chính, rotate 14 ngày ─────────────────────────────
        'daily' => [
            'driver'     => 'daily',
            'path'       => storage_path('logs/laravel.log'),
            'level'      => env('LOG_LEVEL', 'debug'),
            'days'       => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        // ── Security — chỉ ghi WARNING trở lên ───────────────────────────
        // Events: auth.login_failed, auth.login_banned, Unhandled exceptions
        'security' => [
            'driver'     => 'daily',
            'path'       => storage_path('logs/security.log'),
            'level'      => 'warning',
            'days'       => 30,         // Giữ 30 ngày để audit
            'formatter'  => \Monolog\Formatter\JsonFormatter::class,
        ],

        // ── Queue — theo dõi job dispatch/retry/fail ──────────────────────
        // Được dùng trực tiếp: Log::channel('queue')->info(...)
        'queue' => [
            'driver'     => 'daily',
            'path'       => storage_path('logs/queue.log'),
            'level'      => 'info',
            'days'       => 7,
        ],

        // ── Activity — fallback khi ActivityLog::record() throw exception ─
        'activity' => [
            'driver'     => 'daily',
            'path'       => storage_path('logs/activity.log'),
            'level'      => 'error',
            'days'       => 60,         // Giữ lâu hơn vì dùng cho audit
        ],

        // ── Stderr — dùng khi deploy Docker / container ───────────────────
        'stderr' => [
            'driver'    => 'monolog',
            'level'     => env('LOG_LEVEL', 'debug'),
            'handler'   => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with'      => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        // ── Null — dùng trong tests ───────────────────────────────────────
        'null' => [
            'driver'  => 'monolog',
            'handler' => NullHandler::class,
        ],

        // ── Single file (legacy / quick debug) ───────────────────────────
        'single' => [
            'driver' => 'single',
            'path'   => storage_path('logs/laravel.log'),
            'level'  => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

    ],

];
