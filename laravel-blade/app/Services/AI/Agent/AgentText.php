<?php

namespace App\Services\AI\Agent;

use Illuminate\Support\Str;

final class AgentText
{
    /** Lowercase, accent-free, punctuation-free form used for matching. */
    public static function normalize(?string $value): string
    {
        $value = Str::ascii(mb_strtolower(trim((string) $value), 'UTF-8'));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    /** Strip tags/control characters and cap length for outbound model data. */
    public static function clean(?string $value, int $max): string
    {
        $value = strip_tags((string) $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return mb_substr(trim($value), 0, max(0, $max));
    }

    /** @return list<string> */
    public static function numbers(string $value): array
    {
        preg_match_all('/\d+(?:[.,]\d+)?/', $value, $matches);

        return array_values(array_unique(array_map(
            static fn (string $number): string => self::number($number),
            $matches[0]
        )));
    }

    public static function number(string $value): string
    {
        $value = str_replace(',', '.', trim($value));
        if (str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return $value === '' ? '0' : $value;
    }
}
