<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted reverse proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of proxy IP addresses or CIDR ranges, for example:
    |
    |     TRUSTED_PROXIES=192.0.2.10,198.51.100.0/24
    |
    | Use REMOTE_ADDR to trust only the peer that opened the TCP connection.
    | That is the least-privilege setting for a platform such as Render, whose
    | load balancer is the immediate caller and whose application port is not
    | reachable directly from the public internet.
    |
    | Leave empty when the application is reached directly (local development,
    | PHP's built-in server, tests). Forwarded headers are then ignored and the
    | client IP is always REMOTE_ADDR.
    |
    | Invalid entries are ignored. If no valid entry remains, no proxy is
    | trusted. Never list client-controlled ranges here.
    |
    */
    'proxies' => env('TRUSTED_PROXIES'),

    /*
    |--------------------------------------------------------------------------
    | Number of proxy hops
    |--------------------------------------------------------------------------
    |
    | The number of X-Forwarded-For entries added by your local proxy chain:
    | the client entry written by the first proxy, plus one entry per inner
    | proxy. One hop is correct for a single load balancer such as Render.
    |
    | The middleware keeps the right-most entries. If this is too low, the
    | client IP resolves to the nearest proxy instead of the real client; if
    | it is too high, caller-supplied entries can remain in the chain. Set it
    | to the exact number of proxies you operate.
    |
    */
    'hops' => max(1, (int) env('TRUSTED_PROXY_HOPS', 1)),
];
