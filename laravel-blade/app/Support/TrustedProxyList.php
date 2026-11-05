<?php

namespace App\Support;

/**
 * Parses the TRUSTED_PROXIES setting into a list Symfony can consume.
 *
 * Accepted entries:
 *  - a single IPv4/IPv6 address;
 *  - an IPv4/IPv6 CIDR range;
 *  - the literal REMOTE_ADDR (or Laravel's legacy "*" / "**"), which tells
 *    Laravel to trust only the peer that opened the TCP connection.
 *
 * Anything else is dropped. A malformed or empty list therefore fails closed:
 * forwarded headers are ignored and the client IP stays REMOTE_ADDR.
 */
final class TrustedProxyList
{
    /**
     * @return array<int, string>|null
     */
    public static function parse(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        } elseif (! is_array($value)) {
            return null;
        }

        $proxies = [];

        foreach ($value as $entry) {
            if (! is_string($entry)) {
                continue;
            }

            $entry = trim($entry);

            if ($entry === '') {
                continue;
            }

            if ($entry === '*' || $entry === '**' || strcasecmp($entry, 'REMOTE_ADDR') === 0) {
                $proxies[] = 'REMOTE_ADDR';

                continue;
            }

            if (self::isIpOrCidr($entry)) {
                $proxies[] = $entry;
            }
        }

        return $proxies === [] ? null : array_values(array_unique($proxies));
    }

    private static function isIpOrCidr(string $value): bool
    {
        $parts = explode('/', $value, 2);
        $address = $parts[0];

        if (! filter_var($address, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (count($parts) === 1) {
            return true;
        }

        $prefix = $parts[1];

        if ($prefix === '' || ! ctype_digit($prefix)) {
            return false;
        }

        $maxPrefix = str_contains($address, ':') ? 128 : 32;

        return (int) $prefix <= $maxPrefix;
    }
}
