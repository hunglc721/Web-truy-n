<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\RateLimiter;

class AIQuota
{
    /** Parser and conversational response reserve from the same backend-owned scopes. */
    public function reserve(array $scopes): bool
    {
        $maximum = (int) config('ai.max_calls');
        if ($maximum <= 0 || $scopes === []) {
            return false;
        }
        $keys = array_map(fn (string $scope) => 'ai:calls:'.hash('sha256', $scope), array_unique($scopes));
        foreach ($keys as $key) {
            if (RateLimiter::tooManyAttempts($key, $maximum)) {
                return false;
            }
        }
        foreach ($keys as $key) {
            if (RateLimiter::hit($key, max(1, (int) config('ai.decay_seconds'))) > $maximum) {
                return false;
            }
        }

        return true;
    }
}
