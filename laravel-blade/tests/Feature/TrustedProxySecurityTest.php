<?php

namespace Tests\Feature;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxySecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/client-ip', static fn (Request $request) => response()->json([
            'ip' => $request->ip(),
            'secure' => $request->isSecure(),
            'host' => $request->getHost(),
        ]));
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('api');
        parent::tearDown();
    }

    public function test_forwarded_headers_are_ignored_without_trusted_proxies(): void
    {
        config(['trustedproxy.proxies' => null, 'trustedproxy.hops' => 1]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->withHeaders([
                'X-Forwarded-For' => '203.0.113.99',
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'evil.example',
                'Forwarded' => 'for=203.0.113.99;proto=https',
            ])
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('ip', '198.51.100.10')
            ->assertJsonPath('secure', false)
            ->assertJsonPath('host', 'localhost');
    }

    public function test_trusted_proxy_keeps_only_the_nearest_forwarded_hop(): void
    {
        config(['trustedproxy.proxies' => 'REMOTE_ADDR', 'trustedproxy.hops' => 1]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders([
                'X-Forwarded-For' => '203.0.113.99, 198.51.100.7',
                'X-Forwarded-Proto' => 'http',
            ])
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('ip', '198.51.100.7');
    }

    public function test_explicit_proxy_list_ignores_forwarded_headers_from_untrusted_peers(): void
    {
        config(['trustedproxy.proxies' => '10.0.0.5', 'trustedproxy.hops' => 1]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.99'])
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('ip', '198.51.100.10');

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.99, 198.51.100.7'])
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('ip', '198.51.100.7');
    }

    public function test_remote_addr_sentinel_trusts_the_immediate_caller(): void
    {
        // This is the Render configuration. REMOTE_ADDR means "trust the peer
        // that opened the TCP connection"; the app port must therefore stay
        // unreachable except through the platform proxy.
        config(['trustedproxy.proxies' => 'REMOTE_ADDR', 'trustedproxy.hops' => 1]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.99, 198.51.100.7'])
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('ip', '198.51.100.7');
    }

    public function test_remote_addr_sentinel_never_expands_beyond_one_hop(): void
    {
        // An overstated hop count must not widen the trusted walk when the
        // only trusted peer is REMOTE_ADDR.
        config(['trustedproxy.proxies' => 'REMOTE_ADDR', 'trustedproxy.hops' => 5]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.99, 10.0.0.5'])
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('ip', '10.0.0.5');
    }

    public function test_multiple_proxy_addresses_and_cidr_ranges_are_supported(): void
    {
        config([
            'trustedproxy.proxies' => '10.0.0.0/8, 203.0.113.42',
            'trustedproxy.hops' => 2,
        ]);

        // Two trusted hops. The left-most value is caller-supplied and must be
        // discarded; 198.51.100.7 is the real client appended by the proxy.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.99, 198.51.100.7, 10.0.0.6'])
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('ip', '198.51.100.7');

        // The single address in the list is trusted too.
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->withHeaders(['X-Forwarded-For' => '198.51.100.8'])
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('ip', '198.51.100.8');

        // A peer outside every configured range is not trusted.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->withHeaders(['X-Forwarded-For' => '198.51.100.8'])
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('ip', '198.51.100.10');
    }

    public function test_caller_cannot_smuggle_a_trusted_proxy_address_into_the_chain(): void
    {
        config(['trustedproxy.proxies' => '10.0.0.5', 'trustedproxy.hops' => 1]);

        // The caller prepends 203.0.113.99 and repeats the trusted proxy IP.
        // Symfony must resolve to the nearest trusted hop, never the forged
        // caller value.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.99, 10.0.0.5'])
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('ip', '10.0.0.5');
    }

    public function test_too_few_hops_fails_closed_to_the_nearest_proxy(): void
    {
        config([
            'trustedproxy.proxies' => '10.0.0.5, 10.0.0.6',
            'trustedproxy.hops' => 1,
        ]);

        // The chain actually has two proxies. With hops=1 the middleware
        // discards the real client and Symfony can only resolve the nearest
        // proxy. That is a misconfiguration, not a spoof: the caller-supplied
        // 203.0.113.99 is still never returned.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.99, 198.51.100.7, 10.0.0.6'])
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('ip', '10.0.0.6');
    }

    public function test_invalid_proxy_entries_are_ignored_and_fail_closed(): void
    {
        config([
            'trustedproxy.proxies' => 'not-an-ip, 10.0.0.0/33, 2001:db8::/129',
            'trustedproxy.hops' => 1,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.99'])
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('ip', '10.0.0.5');

        // Mixed valid/invalid entries keep only the valid proxy.
        config(['trustedproxy.proxies' => 'not-an-ip, 10.0.0.5']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.99'])
            ->getJson('/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('ip', '203.0.113.99');
    }

    public function test_forwarded_proto_is_only_honoured_from_a_trusted_proxy(): void
    {
        config(['trustedproxy.proxies' => null]);
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->getJson('http://localhost/_test/client-ip')
            ->assertJsonPath('secure', false);

        config(['trustedproxy.proxies' => 'REMOTE_ADDR']);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-Proto' => 'http, https'])
            ->getJson('http://localhost/_test/client-ip')
            ->assertJsonPath('secure', true);

        // Symfony reads the left-most Proto value. The middleware keeps the
        // nearest proxy's right-most value instead, so a caller-prepended
        // "https" cannot force a secure scheme.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-Proto' => 'https, http'])
            ->getJson('http://localhost/_test/client-ip')
            ->assertJsonPath('secure', false);
    }

    public function test_rfc7239_forwarded_header_is_never_trusted(): void
    {
        config(['trustedproxy.proxies' => 'REMOTE_ADDR', 'trustedproxy.hops' => 1]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['Forwarded' => 'for=198.51.100.7;proto=https'])
            ->getJson('http://localhost/_test/client-ip')
            ->assertOk()
            ->assertJsonPath('secure', false);
    }

    public function test_ip_rate_limit_cannot_be_bypassed_with_spoofed_forwarded_for(): void
    {
        config(['trustedproxy.proxies' => null]);
        RateLimiter::for('api', static fn (Request $request) =>
            Limit::perMinute(2)->by($request->ip()));

        foreach ([1, 2] as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
                ->withHeaders(['X-Forwarded-For' => '203.0.113.'.$attempt])
                ->getJson('/api/search/live?q=test')
                ->assertStatus(200);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.3'])
            ->getJson('/api/search/live?q=test')
            ->assertStatus(429);

        // A different real peer still gets its own bucket.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.11'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.3'])
            ->getJson('/api/search/live?q=test')
            ->assertStatus(200);
    }

    public function test_rate_limit_uses_the_nearest_hop_behind_a_trusted_proxy(): void
    {
        config(['trustedproxy.proxies' => 'REMOTE_ADDR', 'trustedproxy.hops' => 1]);
        RateLimiter::for('api', static fn (Request $request) =>
            Limit::perMinute(1)->by($request->ip()));

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-For' => '198.51.100.7'])
            ->getJson('/api/search/live?q=test')
            ->assertStatus(200);

        // Rotating the client-controlled left-most value must not reset the
        // bucket: the right-most hop is unchanged.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.99, 198.51.100.7'])
            ->getJson('/api/search/live?q=test')
            ->assertStatus(429);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.99, 198.51.100.8'])
            ->getJson('/api/search/live?q=test')
            ->assertStatus(200);
    }
}
