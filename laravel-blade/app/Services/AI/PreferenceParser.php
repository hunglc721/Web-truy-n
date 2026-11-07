<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PreferenceParser
{
    public function __construct(private IntentParser $ai, private RuleBasedPreferenceParser $rules, private AIQuota $quota) {}

    /**
     * The backend supplies a trusted user:<id>, conversation:<id>, or session:<id> scope.
     * source remains ai/fallback on cache hits; cached indicates no new provider call.
     *
     * @param  array  $previous  Current backend-owned preference state (for follow-up turns).
     * @param  array  $history   Recent transcript, used only as model context.
     */
    public function parseNaturalLanguage(string $message, string $scopeKey, array $quotaScopes = [],
        array $previous = [], array $history = []): array
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $message) ?? ''));
        if ($normalized === '' || mb_strlen($message) > 4000 ||
            ! preg_match('/^(user|conversation|session):[^\s]{1,200}$/u', $scopeKey)) {
            return ['success' => false, 'preferences' => null, 'error' => 'invalid_input'];
        }
        $scopeHash = hash('sha256', $scopeKey);
        $stateHash = hash('sha256', json_encode([$previous, $history], JSON_UNESCAPED_UNICODE));
        $enabled = (bool) config('ai.enabled');
        $cacheKey = 'ai:parse:'.($enabled ? 'enabled:' : 'disabled:').$scopeHash.':'.$stateHash.':'.hash('sha256', $normalized);
        if (($cached = Cache::get($cacheKey)) !== null) {
            return [...$cached, 'cached' => true];
        }
        if (! $enabled) {
            $result = ['success' => false, 'error' => 'ai_disabled'];
        } elseif (! $this->quota->reserve([$scopeKey, ...$quotaScopes])) {
            $result = ['success' => false, 'error' => 'rate_limited'];
        } else {
            $result = $this->ai->parse($normalized, $previous, $history);
        }
        if ($result['success']) {
            $result['preferences'] = $this->applyAdditiveIntent($normalized, $result['preferences'], $previous);
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
            $fallback = $this->rules->parse($normalized);
            $result = ['success' => true,
                'preferences' => $this->applyAdditiveIntent($normalized, $fallback, $previous),
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

    /**
     * "Thêm hài hước" must extend the current filter, not silently drop "School Life".
     *
     * Only additive wording triggers the union; replacement wording ("đổi sang",
     * "chỉ", ...) keeps the delta semantics, and quick replies are unaffected.
     */
    private function applyAdditiveIntent(string $message, array $preferences, array $previous): array
    {
        $additive = '/(?<![\p{L}\p{N}])(?:thêm|nữa|cũng|kèm|bổ\s*sung|và)(?![\p{L}\p{N}])/iu';
        $replacement = '/(?<![\p{L}\p{N}])(?:đổi(?:\s+(?:sang|thành))?|chuyển\s+sang|thay\s+vì|chỉ(?:\s+(?:còn|muốn|cần))?)(?![\p{L}\p{N}])/iu';
        if ($previous === [] || preg_match($replacement, $message) || ! preg_match($additive, $message)) {
            return $preferences;
        }
        foreach (['genres', 'themes', 'settings', 'character_traits', 'tones', 'relationships'] as $field) {
            if (! empty($preferences[$field])) {
                $preferences[$field] = array_values(array_unique([
                    ...($previous[$field] ?? []), ...$preferences[$field],
                ]));
            }
        }
        if (($preferences['status'] ?? null) === null && ! empty($previous['status'])) {
            $preferences['status'] = $previous['status'];
        }

        return $preferences;
    }

}
