# Trusted reverse proxies (P11)

Trusted-proxy hardening for the Comicx Laravel app. This document covers only
the proxy configuration introduced in P11; it does not describe other
deployment work.

> Runtime verification on Render is **not yet performed**. The repository tests
> prove the middleware behaviour, not what the live ingress sends. Run the
> post-deploy checklist below after every deploy that changes the proxy,
> platform, or `TRUSTED_PROXIES` setting.

## Deployment topology (Render)

- `render.yaml` declares a Render **web service** using the Docker runtime.
- `Dockerfile` serves the app with Apache and exposes port 80; `docker-entrypoint.sh` rebinds Apache to Render's `$PORT`.
- Render documents that its load balancer terminates TLS and forwards requests to the service over HTTP, and that the bound service port is **not directly reachable from the public internet** (<https://render.com/docs/web-services#port-binding>).
- The immediate TCP peer (`REMOTE_ADDR`) is therefore Render's ingress proxy. Render does **not** publish a stable list of ingress proxy IPs/CIDRs, so no explicit proxy range can be derived from the repository or the platform documentation. Do not invent one.
- `TRUSTED_PROXIES=REMOTE_ADDR` in `render.yaml` trusts only the immediate caller, which is the least-privilege setting for this topology.

## Middleware behaviour

`app/Http/Middleware/TrustProxies.php` complements Symfony rather than
replacing its resolution rules:

- Symfony resolves the client IP from the **right-most untrusted**
  `X-Forwarded-For` entry once the immediate peer is a trusted proxy.
- The middleware first keeps only the right-most `TRUSTED_PROXY_HOPS` entries.
  When the hop count matches the real chain, caller-supplied left-most values
  never enter Symfony's walk.
- For `X-Forwarded-Proto`, `X-Forwarded-Host`, and `X-Forwarded-Port`, Symfony
  reads the **left-most** value. The middleware keeps only the right-most
  value for those headers, i.e. the value written by the nearest trusted proxy.
- It also drops `Forwarded`, `X-Forwarded-Prefix`, `X-Original-URL`, and
  `X-Rewrite-URL`.

This assumes the standard proxy contract: append the peer address to
`X-Forwarded-For`, and set/append the scheme, host, and port actually served.
It prevents a caller-prepended value from rotating rate-limit buckets, but it
is **defence in depth, not a substitute for firewall isolation**. If the proxy
passes a caller-controlled header through unchanged, no application code can
recover the real client IP.

## Configuration

```dotenv
# Local development, tests, or a directly exposed app: trust nobody.
TRUSTED_PROXIES=

# Single reverse proxy whose address is not stable (trust the TCP peer only).
TRUSTED_PROXIES=REMOTE_ADDR

# Explicit proxy list. Replace these documentation-only example ranges with
# the addresses/CIDRs published by your platform or network team.
TRUSTED_PROXIES=192.0.2.10,198.51.100.0/24,2001:db8::/32

# X-Forwarded-For entries added by the local chain (client + inner proxies).
TRUSTED_PROXY_HOPS=1
```

- Entries are comma/whitespace separated and may be IPv4/IPv6 addresses or CIDR ranges.
- `REMOTE_ADDR`, and Laravel's legacy `*`/`**`, mean "trust only the peer that opened the TCP connection".
- Invalid entries are ignored. If nothing valid remains, no proxy is trusted and forwarded headers are ignored (fail closed).
- `TRUSTED_PROXY_HOPS` must equal the number of `X-Forwarded-For` entries added by your local proxy chain: the client entry written by the first proxy, plus one entry per inner proxy. A single load balancer uses `1`.
- When `REMOTE_ADDR` is the only configured proxy, the middleware always uses exactly one hop and ignores a larger `TRUSTED_PROXY_HOPS` value.
- Too few hops discards the real client and resolves to the nearest proxy, so all clients behind that proxy can share a rate-limit bucket. Too many hops leaves caller-supplied entries in the chain and can expose a forged value when the real client IP also falls inside a trusted range.
- Never put client-controlled ranges in `TRUSTED_PROXIES`. Prefer the narrowest proxy addresses/CIDRs the platform or network team can provide.

Configuration source: `config/trustedproxy.php` (`TRUSTED_PROXIES`,
`TRUSTED_PROXY_HOPS`).

## Local and test configuration

- Leave `TRUSTED_PROXIES` empty when the app is reached directly (the default
  in `.env.example`). Forwarded headers are then ignored and the client IP
  stays `REMOTE_ADDR`.
- The automated suite uses `TRUSTED_PROXIES` overrides inside
  `tests/Feature/TrustedProxySecurityTest.php`; no local proxy is required.
- PHPUnit does not need any proxy environment variable, because the tests set
  the config per case.

## Self-managed proxy configuration

Replace the example ranges above with the addresses or CIDRs of your own
proxies and block direct access to the app port at the firewall.

1. Take the address from the load balancer/ingress configuration (Nginx
   `proxy_pass`, HAProxy, Kubernetes ingress, cloud LB), not from request
   headers.
2. List the narrowest proxy addresses/CIDRs you can.
3. Set `TRUSTED_PROXY_HOPS` to the number of `X-Forwarded-For` entries your
   chain appends. A single reverse proxy in front of the app uses `1`.
4. Re-check the list after any platform migration or proxy scaling change.

## Warning: direct exposure

If the container port is published directly (`docker run -p ...`), the host
firewall allows direct access, or a second ingress path bypasses the proxy,
`TRUSTED_PROXIES=REMOTE_ADDR` (or `*`) lets any direct client set its own
`X-Forwarded-For`, `X-Forwarded-Proto`, and `X-Forwarded-Host`. In that case:

- set `TRUSTED_PROXIES` to the real proxy list and block direct access at the firewall; **or**
- set `TRUSTED_PROXIES=` so forwarded headers are ignored until the network path is fixed.

Never rely on the proxy to strip client-supplied forwarded headers, and never
configure a client-controlled range as a trusted proxy.

## Post-deploy runtime verification

> These checks must be repeated after every deploy that changes the proxy,
> platform, or `TRUSTED_PROXIES` setting. CI only proves the middleware and
> tests, not the live ingress.

| Check | Expected result |
|---|---|
| HTTPS | Public URL serves HTTPS; `APP_URL`/asset URLs are `https://`; no redirect loop |
| Client IP | `auth.login_failed.ip` / activity log shows the real client IP, not a Render-internal address |
| Forged `X-Forwarded-For` | A caller-supplied value never appears as the logged client IP |
| Rate limit | Same client with different forged headers shares one bucket; different clients get separate buckets |
| Direct access | App port is not reachable except through the Render ingress |

1. Confirm the deployed configuration is the intended one:

   ```bash
   php artisan tinker --execute="dump(config('trustedproxy.proxies'), config('trustedproxy.hops'));"
   ```

2. Trigger a failed login through the public URL and inspect the
   `auth.login_failed` log entry. Its `ip` must be the real client IP, not
   Render's internal address and not a forged value:

   ```bash
   curl -s -o /dev/null -X POST https://<app-host>/login \
     -H 'X-Forwarded-For: 203.0.113.99' \
     --data 'email=probe@example.invalid&password=wrong'
   ```

   Check `stderr`/security logs. If the logged `ip` equals `203.0.113.99` (the
   forged value), forwarded headers are being trusted from an untrusted peer;
   stop and fix the topology/config.

3. Repeat the same request without the forged header. The logged IP must stay
   the client's real address.
4. Verify HTTPS URL generation (`APP_URL` starts with `https://`) and that the
   app does not issue an HTTP redirect loop behind the proxy.
5. Send two requests from different real clients and confirm they receive
   separate rate-limit buckets; send two requests from the same client with
   different forged `X-Forwarded-For` values and confirm they share one bucket.

## Code and tests

| File | Purpose |
|---|---|
| `app/Support/TrustedProxyList.php` | Parses `TRUSTED_PROXIES`; fails closed on empty/invalid input |
| `app/Http/Middleware/TrustProxies.php` | Trims forwarded headers, delegates resolution to Symfony |
| `config/trustedproxy.php` | `proxies` and `hops` settings |
| `bootstrap/app.php` | Replaces Laravel's default `TrustProxies` middleware |
| `app/Providers/AppServiceProvider.php` | Stops trusting raw `X-Forwarded-Proto` |
| `render.yaml` | `TRUSTED_PROXIES=REMOTE_ADDR`, `TRUSTED_PROXY_HOPS=1` |
| `tests/Feature/TrustedProxySecurityTest.php` | 13 behavioural security cases |
