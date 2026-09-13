<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class OpenAICompatibleClient implements AIClientInterface
{
    public function complete(string $instruction, string $message): array
    {
        $failure = fn (string $error) => ['success' => false, 'content' => null, 'error' => $error];
        $url = rtrim((string) config('ai.base_url'), '/');
        if (config('ai.provider') !== 'openai_compatible') {
            return $failure('unsupported_provider');
        }
        if (! config('ai.api_key') || ! config('ai.model') ||
            ! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https' ||
            parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_QUERY) !== null) {
            return $failure('invalid_configuration');
        }

        try {
            $response = Http::withToken(config('ai.api_key'))->acceptJson()
                ->timeout(max(1, min(60, (int) config('ai.timeout'))))
                ->connectTimeout(min(5, max(1, (int) config('ai.timeout'))))
                ->withoutRedirecting()
                ->retry(1 + max(0, min(2, (int) config('ai.retry_times'))), 200,
                    fn ($exception) => $exception instanceof ConnectionException ||
                        ($exception instanceof RequestException &&
                            ($exception->response->status() === 429 || $exception->response->serverError())),
                    throw: false)
                ->post($url.'/chat/completions', [
                    'model' => config('ai.model'),
                    'messages' => [
                        ['role' => 'system', 'content' => $instruction],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);
        } catch (ConnectionException) {
            // Timeouts and connection failures expose no transport details or secrets.
            return $failure('connection_error');
        }

        if (! $response->successful()) {
            return $failure('http_error');
        }
        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            return $failure('invalid_response');
        }

        return ['success' => true, 'content' => $content, 'error' => null];
    }
}
