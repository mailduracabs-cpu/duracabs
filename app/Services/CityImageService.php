<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CityImageService
{
    public function getOrGenerate(string $cityName): ?string
    {
        $cityName = trim($cityName);

        if ($cityName === '') {
            return null;
        }

        $slug = Str::slug($cityName);

        if ($slug === '') {
            return null;
        }

        $relativePath = "city-images/{$slug}.webp";

        // Already generated: reuse it.
        if (Storage::disk('public')->exists($relativePath)) {
            return Storage::disk('public')->url($relativePath);
        }

        try {
            return $this->generate($cityName, $relativePath);
        } catch (\Throwable $e) {
            Log::error('City image generation failed', [
                'city' => $cityName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function generate(string $cityName, string $relativePath): ?string
    {
        $apiKey = (string) env('GEMINI_API_KEY');
        $model = (string) env(
            'GEMINI_IMAGE_MODEL',
            'gemini-3.1-flash-image'
        );

        $endpoint = rtrim(
            (string) env(
                'GEMINI_ENDPOINT',
                'https://generativelanguage.googleapis.com/v1beta/models'
            ),
            '/'
        );

        if ($apiKey === '') {
            throw new \RuntimeException('GEMINI_API_KEY is missing.');
        }

        $prompt = <<<PROMPT
Create a premium photorealistic travel photograph representing {$cityName}, India.

Requirements:
- Show a famous, recognizable landmark or authentic visual identity of {$cityName}.
- Natural realistic photography, not illustration or painting.
- Premium Indian travel website style.
- Bright natural daylight.
- Clean composition with enough negative space for website text.
- Wide 16:9 landscape composition.
- No logos.
- No watermark.
- No written text.
- No fake road signs.
- Avoid distorted buildings, vehicles, or people.
- Suitable as a hero image for a taxi and travel booking website.
PROMPT;

        $response = Http::timeout(
                (int) env('GEMINI_TIMEOUT', 120)
            )
            ->connectTimeout(
                (int) env('GEMINI_CONNECT_TIMEOUT', 15)
            )
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post(
                "{$endpoint}/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseModalities' => [
                            'TEXT',
                            'IMAGE',
                        ],
                        'imageConfig' => [
                            'aspectRatio' => '16:9',
                        ],
                    ],
                ]
            );

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Gemini API error: ' .
                $response->status() . ' ' .
                $response->body()
            );
        }

        $parts = data_get(
            $response->json(),
            'candidates.0.content.parts',
            []
        );

        foreach ($parts as $part) {
            $base64 = data_get($part, 'inlineData.data');

            if (! $base64) {
                $base64 = data_get($part, 'inline_data.data');
            }

            if (! $base64) {
                continue;
            }

            $binary = base64_decode($base64, true);

            if ($binary === false) {
                continue;
            }

            Storage::disk('public')->put(
                $relativePath,
                $binary
            );

            return Storage::disk('public')->url(
                $relativePath
            );
        }

        throw new \RuntimeException(
            'Gemini response did not contain an image.'
        );
    }
}