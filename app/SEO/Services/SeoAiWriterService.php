<?php

declare(strict_types=1);

namespace App\SEO\Services;

use App\SEO\AI\AiManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class SeoAiWriterService
{
    public function __construct(
        private readonly AiManager $aiManager,
    ) {
    }

    /**
     * SEO title, meta description, slug, focus keyword,
     * article content aur FAQ generate karta hai.
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function generate(array $input): array
    {
        $data = $this->normalizeInput($input);

        $prompt = $this->buildPrompt($data);

        $response = $this->aiManager->generate(
    prompt: $prompt,
    options: [
        'max_output_tokens' => $this->maxOutputTokens(
            wordCount: $data['word_count'],
        ),

        'system_instruction' => $this->systemPrompt(),

        'json' => true,

        'response_format' => 'json',
    ],
    provider: $data['provider'],
);

        $result = $this->decodeResponse($response);

        return $this->normalizeResult(
            result: $result,
            input: $data,
        );
    }

    /**
     * Sirf SEO title suggestions generate karta hai.
     *
     * @param array<string, mixed> $input
     *
     * @return array<int, string>
     */
    public function generateTitles(array $input): array
    {
        $data = $this->normalizeInput($input);

        $prompt = <<<PROMPT
Generate 10 unique SEO title suggestions.

Topic: {$data['topic']}
Focus keyword: {$data['focus_keyword']}
Target city: {$data['city']}
Brand: {$data['brand_name']}
Language: {$data['language']}
Tone: {$data['tone']}

Rules:
- Each title must be between 30 and 60 characters where practical.
- Include the focus keyword naturally.
- Avoid fake claims, fake prices and misleading promises.
- Return only valid JSON.
- JSON format:

{
  "titles": [
    "Title 1",
    "Title 2"
  ]
}
PROMPT;

        $response = $this->aiManager->generate(
            prompt: $prompt,
            options: [
                'max_output_tokens' => 1200,
                'system_instruction' => $this->systemPrompt(),
                'json' => true,
                'response_format' => 'json',
            ],
            provider: $data['provider'],
        );

        $decoded = $this->decodeResponse($response);

        $titles = $decoded['titles'] ?? [];

        if (! is_array($titles)) {
            return [];
        }

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn (mixed $title): string => trim(
                            (string) $title,
                        ),
                        $titles,
                    ),
                    static fn (string $title): bool => $title !== '',
                ),
            ),
        );
    }

    /**
     * Sirf meta-description suggestions generate karta hai.
     *
     * @param array<string, mixed> $input
     *
     * @return array<int, string>
     */
    public function generateMetaDescriptions(array $input): array
    {
        $data = $this->normalizeInput($input);

        $prompt = <<<PROMPT
Generate 5 unique SEO meta-description suggestions.

Topic: {$data['topic']}
Focus keyword: {$data['focus_keyword']}
Target city: {$data['city']}
Brand: {$data['brand_name']}
Language: {$data['language']}
Tone: {$data['tone']}

Rules:
- Each description should preferably be 120 to 160 characters.
- Include the focus keyword naturally.
- Add a useful call to action.
- Do not invent prices, ratings or availability.
- Return only valid JSON.
- JSON format:

{
  "meta_descriptions": [
    "Description 1",
    "Description 2"
  ]
}
PROMPT;

        $response = $this->aiManager->generate(
            prompt: $prompt,
            options: [
                'max_output_tokens' => 1200,
                'system_instruction' => $this->systemPrompt(),
                'json' => true,
                'response_format' => 'json',
            ],
            provider: $data['provider'],
        );

        $decoded = $this->decodeResponse($response);

        $descriptions = $decoded['meta_descriptions'] ?? [];

        if (! is_array($descriptions)) {
            return [];
        }

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn (mixed $description): string => trim(
                            (string) $description,
                        ),
                        $descriptions,
                    ),
                    static fn (string $description): bool => $description !== '',
                ),
            ),
        );
    }

    /**
     * Existing content ko rewrite karta hai.
     *
     * @param array<string, mixed> $options
     */
    public function rewrite(
        string $content,
        string $mode = 'improve',
        array $options = [],
    ): string {
        $content = trim($content);

        if ($content === '') {
            throw new InvalidArgumentException(
                'Rewrite karne ke liye content required hai.',
            );
        }

        $mode = strtolower(trim($mode));

        $instruction = match ($mode) {
            'expand' => 'Expand the content with useful details while preserving factual accuracy.',
            'shorten' => 'Shorten the content without removing important information.',
            'humanize' => 'Rewrite the content to sound natural, clear and human-written.',
            'professional' => 'Rewrite the content in a professional business tone.',
            'sales' => 'Rewrite the content as persuasive but honest sales copy.',
            'local_seo' => 'Improve the content for local SEO without keyword stuffing.',
            default => 'Improve clarity, SEO structure, readability and usefulness.',
        };

        $language = trim(
            (string) (
                $options['language']
                ?? config('seo-ai.writer.language', 'English')
            ),
        );

        $focusKeyword = trim(
            (string) ($options['focus_keyword'] ?? ''),
        );

        $prompt = <<<PROMPT
Rewrite the content below.

Instruction:
{$instruction}

Language:
{$language}

Focus keyword:
{$focusKeyword}

Rules:
- Preserve factual meaning.
- Do not invent prices, reviews, offers, policies or availability.
- Use useful headings where appropriate.
- Avoid keyword stuffing.
- Return only rewritten content.
- Do not return JSON.
- Do not use markdown code fences.

Content:
{$content}
PROMPT;

        return trim(
            $this->aiManager->generate(
                prompt: $prompt,
                options: [
                    'max_output_tokens' => 5000,
                    'system_instruction' => $this->systemPrompt(),
                ],
                provider: isset($options['provider'])
                    ? trim((string) $options['provider'])
                    : null,
            ),
        );
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function normalizeInput(array $input): array
    {
        $topic = trim(
            (string) (
                $input['topic']
                ?? $input['name']
                ?? ''
            ),
        );

        if ($topic === '') {
            throw new InvalidArgumentException(
                'AI writer ke liye topic required hai.',
            );
        }

        $focusKeyword = trim(
            (string) (
                $input['focus_keyword']
                ?? $input['keyword']
                ?? $topic
            ),
        );

        $wordCount = (int) (
            $input['word_count']
            ?? config('seo-ai.writer.word_count', 1000)
        );

        $wordCount = max(
            300,
            min(3000, $wordCount),
        );

        $maxFaqs = (int) (
            $input['max_faqs']
            ?? config('seo-ai.writer.max_faqs', 5)
        );

        $maxFaqs = max(
            0,
            min(15, $maxFaqs),
        );

        return [
            'topic' => $topic,

            'focus_keyword' => $focusKeyword,

            'city' => trim(
                (string) ($input['city'] ?? ''),
            ),

            'language' => trim(
                (string) (
                    $input['language']
                    ?? config('seo-ai.writer.language', 'English')
                ),
            ),

            'tone' => trim(
                (string) (
                    $input['tone']
                    ?? config('seo-ai.writer.tone', 'Professional')
                ),
            ),

            'word_count' => $wordCount,

            'country' => trim(
                (string) (
                    $input['country']
                    ?? config('seo-ai.writer.country', 'India')
                ),
            ),

            'brand_name' => trim(
                (string) (
                    $input['brand_name']
                    ?? config('seo-ai.writer.brand_name', 'Dura Cabs')
                ),
            ),

            'service_type' => trim(
                (string) ($input['service_type'] ?? ''),
            ),

            'existing_content' => trim(
                (string) ($input['existing_content'] ?? ''),
            ),

            'additional_instructions' => trim(
                (string) (
                    $input['additional_instructions']
                    ?? ''
                ),
            ),

            'max_faqs' => $maxFaqs,

            'provider' => isset($input['provider'])
                ? trim((string) $input['provider'])
                : null,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildPrompt(array $data): string
    {
        $existingContent = $data['existing_content'] !== ''
            ? $data['existing_content']
            : 'No existing content supplied.';

        $additionalInstructions = $data['additional_instructions'] !== ''
            ? $data['additional_instructions']
            : 'No additional instructions.';

        return <<<PROMPT
Create complete SEO content for the following page.

Business:
{$data['brand_name']}

Topic:
{$data['topic']}

Primary focus keyword:
{$data['focus_keyword']}

Target city:
{$data['city']}

Country:
{$data['country']}

Service type:
{$data['service_type']}

Language:
{$data['language']}

Tone:
{$data['tone']}

Target article length:
Approximately {$data['word_count']} words.

Maximum FAQ count:
{$data['max_faqs']}

Existing content:
{$existingContent}

Additional instructions:
{$additionalInstructions}

Important rules:
- Write accurate, useful and original content.
- Do not invent fares, discounts, customer ratings, availability, guarantees or policies.
- Do not claim that a service exists unless clearly provided in the input.
- Use the focus keyword naturally.
- Avoid keyword stuffing.
- Use clear H2 and H3 headings.
- Include a practical call to action.
- Meta title should preferably be 30 to 60 characters.
- Meta description should preferably be 120 to 160 characters.
- Slug must be lowercase and hyphen-separated.
- FAQs must be relevant and factual.
- Return only valid JSON.
- Do not use markdown code fences.
- Do not include comments before or after the JSON.

Required JSON format:

{
  "seo_title": "SEO title",
  "meta_description": "Meta description",
  "slug": "seo-friendly-slug",
  "focus_keyword": "Primary keyword",
  "secondary_keywords": [
    "keyword one",
    "keyword two"
  ],
  "content": "<h2>Heading</h2><p>HTML content</p>",
  "excerpt": "Short summary",
  "cta": "Call to action",
  "faqs": [
    {
      "question": "Question",
      "answer": "Answer"
    }
  ]
}
PROMPT;
    }

    private function systemPrompt(): string
    {
        return 'You are Dura SEO AI, an expert SEO strategist and content writer. '
            . 'Generate conversion-focused, readable and factually safe SEO content. '
            . 'Never invent business facts, pricing, ratings, offers or policies. '
            . 'When JSON is requested, return strict valid JSON only.';
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(string $response): array
    {
        $response = $this->sanitizeResponse($response);

        Log::info('RAW GEMINI RESPONSE', [
            'response' => $response,
        ]);

        if ($response === '') {
            throw new RuntimeException(
                'AI ne empty response return kiya.',
            );
        }

        try {
            $decoded = json_decode(
                $response,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (Throwable $exception) {
            Log::error('JSON DECODE FAILED', [
                'response' => $response,
                'error' => $exception->getMessage(),
            ]);

            $json = $this->extractJsonObject($response);

            if ($json === '') {
                throw new RuntimeException(
                    'AI response valid JSON format me nahi hai.',
                    previous: $exception,
                );
            }

            try {
                $decoded = json_decode(
                    $json,
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (Throwable $jsonException) {
                Log::error('JSON OBJECT FAILED', [
                    'json' => $json,
                    'error' => $jsonException->getMessage(),
                ]);

                throw new RuntimeException(
                    'AI response JSON parse nahi ho saka.',
                    previous: $jsonException,
                );
            }
        }

        if (! is_array($decoded)) {
            throw new RuntimeException(
                'AI response expected array format me nahi hai.',
            );
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function normalizeResult(
        array $result,
        array $input,
    ): array {
        $seoTitle = trim(
            (string) (
                $result['seo_title']
                ?? $result['title']
                ?? $input['topic']
            ),
        );

        $metaDescription = trim(
            (string) (
                $result['meta_description']
                ?? ''
            ),
        );

        $focusKeyword = trim(
            (string) (
                $result['focus_keyword']
                ?? $input['focus_keyword']
            ),
        );

        $slug = Str::slug(
            trim(
                (string) (
                    $result['slug']
                    ?? $seoTitle
                    ?? $input['topic']
                ),
            ),
        );

        $secondaryKeywords = $result['secondary_keywords'] ?? [];

        if (! is_array($secondaryKeywords)) {
            $secondaryKeywords = [];
        }

        $faqs = $result['faqs'] ?? [];

        if (! is_array($faqs)) {
            $faqs = [];
        }

        $normalizedFaqs = [];

        foreach ($faqs as $faq) {
            if (! is_array($faq)) {
                continue;
            }

            $question = trim(
                (string) ($faq['question'] ?? ''),
            );

            $answer = trim(
                (string) ($faq['answer'] ?? ''),
            );

            if ($question === '' || $answer === '') {
                continue;
            }

            $normalizedFaqs[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return [
            'seo_title' => $seoTitle,

            'meta_title' => $seoTitle,

            'meta_description' => $metaDescription,

            'slug' => $slug,

            'focus_keyword' => $focusKeyword,

            'secondary_keywords' => array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn (mixed $keyword): string => trim(
                                (string) $keyword,
                            ),
                            $secondaryKeywords,
                        ),
                        static fn (string $keyword): bool => $keyword !== '',
                    ),
                ),
            ),

            'content' => trim(
                (string) ($result['content'] ?? ''),
            ),

            'description' => trim(
                (string) ($result['content'] ?? ''),
            ),

            'excerpt' => trim(
                (string) ($result['excerpt'] ?? ''),
            ),

            'cta' => trim(
                (string) ($result['cta'] ?? ''),
            ),

            'faqs' => $normalizedFaqs,
        ];
    }

    private function sanitizeResponse(string $response): string
    {
        $response = preg_replace('/^\xEF\xBB\xBF/', '', $response) ?? $response;
        $response = str_replace("\0", '', $response);
        $response = trim($response);

        return $this->removeCodeFence($response);
    }

    private function removeCodeFence(string $response): string
    {
        $response = preg_replace(
            '/^```(?:json)?\s*/i',
            '',
            $response,
        ) ?? $response;

        $response = preg_replace(
            '/\s*```$/',
            '',
            $response,
        ) ?? $response;

        return trim($response);
    }

    private function extractJsonObject(string $response): string
    {
        $start = strpos($response, '{');
        $end = strrpos($response, '}');

        if (
            $start === false
            || $end === false
            || $end <= $start
        ) {
            return '';
        }

        return substr(
            $response,
            $start,
            ($end - $start) + 1,
        );
    }

    private function maxOutputTokens(int $wordCount): int
    {
        return match (true) {
            $wordCount <= 500 => 2500,
            $wordCount <= 1000 => 4500,
            $wordCount <= 1500 => 6500,
            $wordCount <= 2000 => 8500,
            default => 10000,
        };
    }
}