<?php

declare(strict_types=1);

namespace App\SEO\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class GeminiProvider implements AiProviderInterface
{
    /**
     * Gemini se text generate karta hai.
     *
     * @param array<string, mixed> $options
     */
    public function generate(
        string $prompt,
        array $options = [],
    ): string {
        $prompt = trim($prompt);

        if ($prompt === '') {
            throw new RuntimeException(
                'Gemini prompt empty nahi ho sakta.',
            );
        }

        $config = $this->getConfig();

        $apiKey = trim(
            (string) ($config['api_key'] ?? ''),
        );

        if ($apiKey === '') {
            throw new RuntimeException(
                'GEMINI_API_KEY configured nahi hai.',
            );
        }

        $model = trim(
            (string) (
                $options['model']
                ?? $config['model']
                ?? 'gemini-2.5-flash'
            ),
        );

        if ($model === '') {
            throw new RuntimeException(
                'Gemini model configured nahi hai.',
            );
        }

        $endpoint = rtrim(
            (string) (
                $config['endpoint']
                ?? 'https://generativelanguage.googleapis.com/v1beta/models'
            ),
            '/',
        );

        $url = sprintf(
            '%s/%s:generateContent',
            $endpoint,
            rawurlencode($model),
        );

        $generationConfig = $this->buildGenerationConfig(
            config: $config,
            options: $options,
        );

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
            'generationConfig' => $generationConfig,
        ];

        $systemInstruction = trim(
            (string) ($options['system_instruction'] ?? ''),
        );

        if ($systemInstruction !== '') {
            $payload['systemInstruction'] = [
                'parts' => [
                    [
                        'text' => $systemInstruction,
                    ],
                ],
            ];
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                ->connectTimeout(
                    (int) (
                        $options['connect_timeout']
                        ?? $config['connect_timeout']
                        ?? 15
                    ),
                )
                ->timeout(
                    (int) (
                        $options['timeout']
                        ?? $config['timeout']
                        ?? 120
                    ),
                )
                ->retry(
                    times: 2,
                    sleepMilliseconds: 1000,
                    throw: false,
                )
                ->post($url, $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Gemini API se connection nahi ho saka: '
                . $exception->getMessage(),
                previous: $exception,
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Gemini request fail ho gayi: '
                . $exception->getMessage(),
                previous: $exception,
            );
        }

        if ($response->failed()) {
            $message = $this->extractErrorMessage(
                $response->json(),
            );

            throw new RuntimeException(
                sprintf(
                    'Gemini API error [%d]: %s',
                    $response->status(),
                    $message,
                ),
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException(
                'Gemini API ne invalid response return kiya.',
            );
        }

        $text = $this->extractGeneratedText($data);

        if ($text === '') {
            $finishReason = Arr::get(
                $data,
                'candidates.0.finishReason',
                'unknown',
            );

            $blockReason = Arr::get(
                $data,
                'promptFeedback.blockReason',
            );

            $message = 'Gemini se generated text nahi mila.';

            if (is_scalar($finishReason)) {
                $message .= ' Finish reason: '
                    . (string) $finishReason
                    . '.';
            }

            if (is_scalar($blockReason)) {
                $message .= ' Block reason: '
                    . (string) $blockReason
                    . '.';
            }

            throw new RuntimeException($message);
        }

        return $text;
    }

    /**
     * Provider configured hai ya nahi.
     */
    public function isConfigured(): bool
    {
        return trim(
            (string) config(
                'seo-ai.providers.gemini.api_key',
                '',
            ),
        ) !== '';
    }

    /**
     * Provider identifier.
     */
    public function name(): string
    {
        return 'gemini';
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfig(): array
    {
        $config = config(
            'seo-ai.providers.gemini',
            [],
        );

        if (! is_array($config)) {
            throw new RuntimeException(
                'Gemini provider configuration invalid hai.',
            );
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function buildGenerationConfig(
        array $config,
        array $options,
    ): array {
        $generationConfig = [
            'maxOutputTokens' => max(
                100,
                (int) (
                    $options['max_output_tokens']
                    ?? $config['max_output_tokens']
                    ?? 4000
                ),
            ),
        ];

        $temperature = $options['temperature']
            ?? $config['temperature']
            ?? null;

        if (
            $temperature !== null
            && $temperature !== ''
            && is_numeric($temperature)
        ) {
            $generationConfig['temperature'] = max(
                0,
                min(2, (float) $temperature),
            );
        }

        $topP = $options['top_p'] ?? null;

        if (
            $topP !== null
            && $topP !== ''
            && is_numeric($topP)
        ) {
            $generationConfig['topP'] = max(
                0,
                min(1, (float) $topP),
            );
        }

        $topK = $options['top_k'] ?? null;

        if (
            $topK !== null
            && $topK !== ''
            && is_numeric($topK)
        ) {
            $generationConfig['topK'] = max(
                1,
                (int) $topK,
            );
        }

        if (
            (bool) ($options['json'] ?? false)
            || ($options['response_format'] ?? null) === 'json'
        ) {
            $generationConfig['responseMimeType'] =
                'application/json';
        }

        return $generationConfig;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractGeneratedText(array $data): string
    {
        $parts = Arr::get(
            $data,
            'candidates.0.content.parts',
            [],
        );

        if (! is_array($parts)) {
            return '';
        }

        $texts = [];

        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }

            $text = trim(
                (string) ($part['text'] ?? ''),
            );

            if ($text !== '') {
                $texts[] = $text;
            }
        }

        return trim(
            implode("\n", $texts),
        );
    }

    private function extractErrorMessage(
        mixed $response,
    ): string {
        if (is_array($response)) {
            $message = Arr::get(
                $response,
                'error.message',
            );

            if (is_scalar($message)) {
                $message = trim((string) $message);

                if ($message !== '') {
                    return $message;
                }
            }

            try {
                return json_encode(
                    $response,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR,
                );
            } catch (Throwable) {
                //
            }
        }

        if (is_scalar($response)) {
            $message = trim((string) $response);

            if ($message !== '') {
                return $message;
            }
        }

        return 'Unknown Gemini API error.';
    }
}