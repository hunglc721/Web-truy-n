<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class PreferenceParser
{
    public function __construct(private IntentParser $ai, private RuleBasedPreferenceParser $rules) {}

    /**
     * The backend supplies a trusted user:<id>, conversation:<id>, or session:<id> scope.
     * source remains ai/fallback on cache hits; cached indicates no new provider call.
     */
    public function parseNaturalLanguage(string $message, string $scopeKey): array
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $message) ?? ''));
        if ($normalized === '' || mb_strlen($message) > 4000 ||
            ! preg_match('/^(user|conversation|session):[^\s]{1,200}$/u', $scopeKey)) {
            return ['success' => false, 'preferences' => null, 'error' => 'invalid_input'];
        }
        $scopeHash = hash('sha256', $scopeKey);
        $enabled = (bool) config('ai.enabled');
        $cacheKey = 'ai:parse:'.($enabled ? 'enabled:' : 'disabled:').$scopeHash.':'.hash('sha256', $normalized);
        if (($cached = Cache::get($cacheKey)) !== null) {
            return [...$cached, 'cached' => true];
        }
        $rateKey = 'ai:calls:'.$scopeHash;
        if (! $enabled) {
            $result = ['success' => false, 'error' => 'ai_disabled'];
        } elseif ((int) config('ai.max_calls') <= 0 || RateLimiter::tooManyAttempts($rateKey, (int) config('ai.max_calls'))) {
            $result = ['success' => false, 'error' => 'rate_limited'];
        } else {
            // Reserve the call before contacting the provider, including failed calls.
            RateLimiter::hit($rateKey, max(1, (int) config('ai.decay_seconds')));
            $result = $this->ai->parse($normalized);
        }
        if ($result['success']) {
            $result += ['source' => 'ai', 'cached' => false];
        } else {
            $reason = in_array($result['error'], ['ai_disabled', 'rate_limited', 'connection_error', 'http_error',
                'invalid_json', 'invalid_schema', 'invalid_response', 'unsupported_provider', 'invalid_configuration'], true)
                ? $result['error'] : 'parse_failed';
            Log::notice('AI preference fallback', [
                'scope_type' => explode(':', $scopeKey, 2)[0],
                'provider' => config('ai.provider'), 'model' => config('ai.model'),
                'reason' => $reason, 'fallback_used' => true,
            ]);
            $result = ['success' => true, 'preferences' => $this->rules->parse($normalized),
                'error' => null, 'source' => 'fallback', 'fallback_reason' => $reason, 'cached' => false];
        }
        $ttl = max(0, (int) config('ai.parse_cache_ttl'));
        if ($ttl > 0) {
            Cache::put($cacheKey, $result, $ttl);
        }

        return $result;
    }

    public function parseQuickReply(string $label): array
    {
        $delta = $this->rules->quickReply($label);

        return ['success' => $delta !== null, 'preferences' => $delta,
            'error' => $delta === null ? 'invalid_quick_reply' : null, 'source' => 'quick_reply'];
    }
}
