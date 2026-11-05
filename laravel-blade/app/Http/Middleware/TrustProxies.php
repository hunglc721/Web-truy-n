<?php

namespace App\Http\Middleware;

use App\Support\TrustedProxyList;
use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

/**
 * Trusted-proxy handling with a hardened forwarded-header chain.
 *
 * Symfony resolves the client IP from the right-most untrusted entry of
 * X-Forwarded-For once REMOTE_ADDR is a trusted proxy. This middleware trims
 * the chain to the configured number of local proxy hops first. When
 * TRUSTED_PROXY_HOPS matches the real chain, caller-supplied left-most entries
 * never enter that walk even when a proxy appends instead of replacing the
 * header.
 *
 * The other headers need different treatment: Symfony reads the first
 * (left-most) value of X-Forwarded-Proto/Host/Port. We therefore retain only
 * the right-most value for them, which is the value written by the nearest
 * trusted proxy.
 *
 * This assumes the standard proxy contract: append the peer address to
 * X-Forwarded-For and set/append the scheme, host, and port actually served.
 * It is defence in depth, not a substitute for keeping the origin unreachable
 * except through the proxy.
 *
 * Proxies are configured through TRUSTED_PROXIES. An empty or invalid setting
 * trusts nobody and keeps the client IP at REMOTE_ADDR.
 */
class TrustProxies extends Middleware
{
    protected function setTrustedProxyIpAddresses(Request $request)
    {
        $proxies = TrustedProxyList::parse(config('trustedproxy.proxies'));

        if ($proxies !== null) {
            $hops = max(1, (int) config('trustedproxy.hops', 1));

            // REMOTE_ADDR trusts only the peer that opened the connection.
            // There is exactly one such hop; letting a larger value through
            // would widen the walk into caller-supplied entries.
            if ($proxies === ['REMOTE_ADDR']) {
                $hops = 1;
            }

            $this->keepRightmost($request, 'X-Forwarded-For', $hops);
            $this->keepRightmost($request, 'X-Forwarded-Host', 1);
            $this->keepRightmost($request, 'X-Forwarded-Port', 1, static fn (string $value): bool => ctype_digit($value));
            $this->keepRightmost($request, 'X-Forwarded-Proto', 1, static fn (string $value): bool =>
                in_array(strtolower($value), ['http', 'https'], true));

            $trustedIps = array_values(array_filter(array_map(
                static fn (string $proxy): string => $proxy === 'REMOTE_ADDR'
                    ? (string) $request->server->get('REMOTE_ADDR')
                    : $proxy,
                $proxies,
            )));

            if ($trustedIps !== []) {
                $request->setTrustedProxies($trustedIps, $this->headers());
            }
        }

        // RFC 7239 and common rewrite headers are not used by this app and
        // would otherwise bypass the X-Forwarded-* sanitisation above.
        $request->headers->remove('Forwarded');
        $request->headers->remove('X-Forwarded-Prefix');
        $request->headers->remove('X-Original-URL');
        $request->headers->remove('X-Rewrite-URL');
    }

    protected function headers()
    {
        return Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_PORT;
    }

    /**
     * Keep the right-most values, i.e. those written by our own proxies.
     *
     * X-Forwarded-For is ordered client-first and nearest-proxy-last. Taking
     * the last $keep entries drops anything the caller prepended. For the
     * single-value headers $keep is 1, so only the nearest proxy's value is
     * left for Symfony.
     */
    private function keepRightmost(
        Request $request,
        string $header,
        int $keep,
        ?callable $accept = null,
    ): void {
        $values = array_values(array_filter(
            array_map('trim', explode(',', (string) $request->headers->get($header, ''))),
            static fn (string $value): bool => $value !== '',
        ));

        if ($values === []) {
            $request->headers->remove($header);

            return;
        }

        $values = array_slice($values, -$keep);

        if ($accept !== null) {
            $values = array_values(array_filter($values, $accept));
        }

        if ($values === []) {
            $request->headers->remove($header);

            return;
        }

        $request->headers->set($header, implode(', ', $values));
    }
}
