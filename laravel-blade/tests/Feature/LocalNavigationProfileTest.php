<?php

namespace Tests\Feature;

use App\Http\Middleware\LocalNavigationProfile;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LocalNavigationProfileTest extends TestCase
{
    public function test_local_profile_records_slow_sql_bindings_and_repeated_queries(): void
    {
        $this->app->instance('env', 'local');
        Log::spy();
        $request = Request::create('/truyen/example');
        $response = (new LocalNavigationProfile)->handle($request, function ($request) {
            for ($id = 1; $id <= 5; $id++) {
                LocalNavigationProfile::record($request, new QueryExecuted('select * from comics where id = ?', [$id], 100.0, DB::connection()));
            }
            return response('ok');
        });
        $this->assertTrue($response->headers->has('Server-Timing'));
        Log::shouldHaveReceived('warning')->with('local.navigation.slow_query', \Mockery::on(fn ($context) =>
            $context['sql'] === 'select * from comics where id = ?' && $context['bindings'] === [1] && $context['time_ms'] === 100.0))->once();
        Log::shouldHaveReceived('warning')->with('local.navigation.repeated_query', \Mockery::on(fn ($context) => $context['count'] === 5))->once();
        Log::shouldHaveReceived('log')->with('info', 'local.navigation.request', \Mockery::on(fn ($context) =>
            $context['query_count'] === 5 && $context['sql_time_ms'] === 500.0))->once();
    }

    public function test_production_and_unrelated_routes_are_not_instrumented(): void
    {
        Log::spy();
        foreach ([['production', '/'], ['local', '/admin'], ['local', '/user/notifications/header']] as [$env, $path]) {
            $this->app->instance('env', $env);
            $response = (new LocalNavigationProfile)->handle(Request::create($path), function ($request) {
                LocalNavigationProfile::record($request, new QueryExecuted('select 1', [], 150, DB::connection()));
                return response('ok');
            });
            $this->assertFalse($response->headers->has('Server-Timing'));
        }
        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('log');
    }
}
