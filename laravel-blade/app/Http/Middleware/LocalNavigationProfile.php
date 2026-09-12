<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocalNavigationProfile
{
    private const KEY = '_local_navigation_profile';

    public function handle(Request $request, Closure $next)
    {
        $path = $request->path();
        if (!app()->environment('local') || !$request->isMethod('GET')
            || !($path === '/' || $path === 'user/library' || preg_match('~^truyen/[^/]+(?:/[^/]+)?$~', $path))) {
            return $next($request);
        }

        $profile = (object) ['id' => bin2hex(random_bytes(8)), 'count' => 0, 'sql_ms' => 0.0, 'groups' => []];
        $request->attributes->set(self::KEY, $profile);
        $started = hrtime(true);
        $response = null;
        try {
            $response = $next($request);
            return $response;
        } finally {
            $middlewareTime = round((hrtime(true) - $started) / 1e6, 2);
            $time = defined('LARAVEL_START') && !app()->runningInConsole()
                ? round((microtime(true) - LARAVEL_START) * 1000, 2) : $middlewareTime;
            $context = ['request_id' => $profile->id, 'route' => $request->route()?->getName(),
                'path' => $request->path(), 'action' => $request->route()?->getActionName(),
                'status' => $response?->getStatusCode(), 'time_ms' => $time, 'middleware_time_ms' => $middlewareTime,
                'sql_time_ms' => round($profile->sql_ms, 2), 'query_count' => $profile->count];
            $groups = array_values($profile->groups);
            usort($groups, fn ($a, $b) => $b['time_ms'] <=> $a['time_ms']);
            $context['top_queries'] = array_map(function ($group) {
                unset($group['binding_hashes']);
                return $group;
            }, array_slice($groups, 0, 5));
            foreach ($groups as $group) {
                // Repetition is evidence to inspect, not automatic proof of an N+1.
                if ($group['count'] >= 5 && count($group['binding_hashes']) >= 3
                    && preg_match('/^select\b/i', ltrim($group['sql']))) {
                    unset($group['binding_hashes']);
                    Log::warning('local.navigation.repeated_query', ['request_id' => $profile->id,
                        'route' => $context['route']] + $group);
                }
            }
            Log::log($time >= 1000 ? 'warning' : 'info', 'local.navigation.request', $context);
            $response?->headers->set('X-Local-Request-Id', $profile->id);
            $response?->headers->set('Server-Timing', 'app;dur=' . $time . ', db;dur=' . round($profile->sql_ms, 2), false);
            $request->attributes->remove(self::KEY);
        }
    }

    public static function record(Request $request, QueryExecuted $query): void
    {
        if (!app()->environment('local') || !($profile = $request->attributes->get(self::KEY))) return;
        $profile->count++;
        $profile->sql_ms += $query->time;
        $key = $query->connectionName . ':' . $query->sql;
        if (!isset($profile->groups[$key])) {
            $origin = [];
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 60) as $frame) {
                $file = str_replace('\\', '/', $frame['file'] ?? '');
                $base = str_replace('\\', '/', base_path()) . '/';
                if (str_starts_with($file, $base . 'app/') && $file !== str_replace('\\', '/', __FILE__)
                    && !str_contains($file, '/Providers/')) {
                    $origin[] = substr($file, strlen($base)) . ':' . ($frame['line'] ?? 0);
                }
            }
            $profile->groups[$key] = ['sql' => $query->sql, 'connection' => $query->connectionName,
                'count' => 0, 'time_ms' => 0.0, 'max_time_ms' => 0.0, 'origin' => array_slice(array_unique($origin), 0, 4),
                'binding_hashes' => []];
        }
        $group = &$profile->groups[$key];
        $group['count']++;
        $group['time_ms'] = round($group['time_ms'] + $query->time, 2);
        $group['max_time_ms'] = max($group['max_time_ms'], $query->time);
        $group['binding_hashes'][hash('sha256', serialize($query->bindings))] = true;
        if ($query->time >= 100) {
            Log::warning('local.navigation.slow_query', ['request_id' => $profile->id,
                'route' => $request->route()?->getName(), 'sql' => $query->sql, 'bindings' => $query->bindings,
                'time_ms' => $query->time, 'connection' => $query->connectionName, 'origin' => $group['origin']]);
        }
    }
}
