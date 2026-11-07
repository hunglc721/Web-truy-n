<?php

namespace App\Support;

/**
 * Resolves AI provider settings from environment-style values.
 *
 * Gemini exposes an OpenAI-compatible endpoint, so the existing client can talk
 * to it. A Gemini key supplied through AI_API_KEY, GEMINI_API_KEY, or
 * GOOGLE_API_KEY gets the Gemini endpoint and a default model when
 * AI_BASE_URL / AI_MODEL are not explicitly configured. Explicit values always
 * win, and non-Gemini keys keep their provider settings untouched.
 */
final class AIProviderSettings
{
    public const GEMINI_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/openai';

    public const GEMINI_DEFAULT_MODEL = 'gemini-3.5-flash-lite';

    /**
     * @param  array<string, mixed>  $values
     * @return array{provider: string, api_key: string, base_url: string, model: string, gemini: bool}
     */
    public static function resolve(array $values): array
    {
        $provider = strtolower(trim((string) ($values['provider'] ?? '')));
        if ($provider === '') {
            $provider = 'openai_compatible';
        }

        $apiKey = self::firstNonEmpty($values, ['api_key', 'gemini_api_key', 'google_api_key', 'google_ai_api_key']);
        $baseUrl = trim((string) ($values['base_url'] ?? ''));
        $model = trim((string) ($values['model'] ?? ''));

        // Only infer Gemini when the endpoint was not configured explicitly, so
        // an AIza key pointed at a proxy keeps the operator's base URL.
        $gemini = in_array($provider, ['gemini', 'google', 'google_gemini'], true)
            || ($baseUrl === '' && $apiKey !== '' && str_starts_with($apiKey, 'AIza'));

        if ($gemini) {
            $provider = 'openai_compatible';
            $baseUrl = $baseUrl !== '' ? $baseUrl : self::GEMINI_BASE_URL;
            if ($model === '') {
                $model = trim((string) ($values['gemini_model'] ?? '')) ?: self::GEMINI_DEFAULT_MODEL;
            }
        }

        return [
            'provider' => $provider,
            'api_key' => $apiKey,
            'base_url' => $baseUrl,
            'model' => $model,
            'gemini' => $gemini,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $keys
     */
    private static function firstNonEmpty(array $values, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($values[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
