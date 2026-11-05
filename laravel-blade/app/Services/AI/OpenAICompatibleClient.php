<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAICompatibleClient implements AIClientInterface
{
    public function complete(string $instruction, string $message, array $options = []): array
    {
        $failure = fn (string $error) => ['success' => false, 'content' => null, 'error' => $error];
        if (! config('ai.enabled')) {
            return $failure('ai_disabled');
        }
        $url = rtrim((string) config('ai.base_url'), '/');
        if (config('ai.provider') !== 'openai_compatible') {
            return $failure('unsupported_provider');
        }
        if (! config('ai.api_key') || ! config('ai.model') ||
            ! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https' ||
            parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_QUERY) !== null) {
            return $failure('invalid_configuration');
        }

        $started = hrtime(true);
        $logFailure = function (string $error, ?int $status = null) use ($started): void {
            Log::warning('AI provider failure', [
                'provider' => config('ai.provider'), 'model' => config('ai.model'),
                'error_type' => $error, 'http_status' => $status,
                'duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
            ]);
        };
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
                    ...$this->sampling($options),
                ]);
        } catch (ConnectionException) {
            // Timeouts and connection failures expose no transport details or secrets.
            $logFailure('connection_error');

            return $failure('connection_error');
        }

        if (! $response->successful()) {
            $logFailure('http_error', $response->status());

            return $failure('http_error');
        }
        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            $logFailure('invalid_response', $response->status());

            return $failure('invalid_response');
        }

        return ['success' => true, 'content' => $content, 'error' => null];
    }

    /** @param array{temperature?: float, max_tokens?: int} $options */
    private function sampling(array $options): array
    {
        $sampling = [];
        if (isset($options['temperature']) && is_numeric($options['temperature'])) {
            $sampling['temperature'] = max(0.0, min(1.0, (float) $options['temperature']));
        }
        if (isset($options['max_tokens']) && is_numeric($options['max_tokens'])) {
            $sampling['max_tokens'] = max(16, min(4000, (int) $options['max_tokens']));
        }

        return $sampling;
    }
}
