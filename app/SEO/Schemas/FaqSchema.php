<?php

declare(strict_types=1);

namespace App\SEO\Schemas;

final class FaqSchema
{
    /**
     * Build an FAQPage schema.
     *
     * Expected FAQ item format:
     *
     * [
     *     [
     *         'question' => 'What is the fare?',
     *         'answer' => 'The fare depends on the selected vehicle.'
     *     ]
     * ]
     *
     * Alternate keys also supported:
     *
     * question: name, title
     * answer: text, content, description
     *
     * @param array<int, array<string, mixed>> $faqs
     *
     * @return array<string, mixed>
     */
    public function build(
        string $pageUrl,
        array $faqs,
        ?string $faqId = null
    ): array {
        $pageUrl = $this->normaliseUrl($pageUrl);
        $faqId ??= $pageUrl . '#faq';

        $mainEntity = $this->questions($faqs);

        if ($mainEntity === []) {
            return [];
        }

        return [
            '@type' => 'FAQPage',
            '@id' => $faqId,
            'url' => $pageUrl,
            'mainEntity' => $mainEntity,
        ];
    }

    /**
     * Convert raw FAQ data into Question schema objects.
     *
     * @param array<int, array<string, mixed>> $faqs
     *
     * @return array<int, array<string, mixed>>
     */
    public function questions(array $faqs): array
    {
        $questions = [];
        $seen = [];

        foreach ($faqs as $faq) {
            if (! is_array($faq)) {
                continue;
            }

            $question = $this->firstText($faq, [
                'question',
                'name',
                'title',
            ]);

            $answer = $this->firstText($faq, [
                'answer',
                'text',
                'content',
                'description',
            ]);

            if ($question === null || $answer === null) {
                continue;
            }

            $signature = mb_strtolower($question);

            if (isset($seen[$signature])) {
                continue;
            }

            $seen[$signature] = true;

            $questions[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        return $questions;
    }

    /**
     * Build one Question schema object.
     *
     * @return array<string, mixed>
     */
    public function question(
        string $question,
        string $answer
    ): array {
        $question = $this->cleanText($question);
        $answer = $this->cleanText($answer);

        if ($question === null || $answer === null) {
            return [];
        }

        return [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $answer,
            ],
        ];
    }

    /**
     * Build FAQPage from a question => answer map.
     *
     * @param array<string, string> $faqs
     *
     * @return array<string, mixed>
     */
    public function fromMap(
        string $pageUrl,
        array $faqs,
        ?string $faqId = null
    ): array {
        $normalised = [];

        foreach ($faqs as $question => $answer) {
            $normalised[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $this->build(
            pageUrl: $pageUrl,
            faqs: $normalised,
            faqId: $faqId
        );
    }

    /**
     * Return an FAQPage @id reference.
     *
     * @return array{@id: string}
     */
    public function reference(
        string $pageUrl,
        ?string $faqId = null
    ): array {
        return [
            '@id' => $faqId ?? $this->id($pageUrl),
        ];
    }

    /**
     * Resolve default FAQPage identifier.
     */
    public function id(string $pageUrl): string
    {
        return $this->normaliseUrl($pageUrl) . '#faq';
    }

    /**
     * Validate FAQ items without building JSON-LD.
     *
     * @param array<int, array<string, mixed>> $faqs
     *
     * @return array{
     *     valid: bool,
     *     valid_count: int,
     *     invalid_count: int,
     *     errors: array<int, string>
     * }
     */
    public function validate(array $faqs): array
    {
        $validCount = 0;
        $invalidCount = 0;
        $errors = [];

        foreach ($faqs as $index => $faq) {
            $position = $index + 1;

            if (! is_array($faq)) {
                $invalidCount++;
                $errors[] = "FAQ {$position} is not an array.";
                continue;
            }

            $question = $this->firstText($faq, [
                'question',
                'name',
                'title',
            ]);

            $answer = $this->firstText($faq, [
                'answer',
                'text',
                'content',
                'description',
            ]);

            if ($question === null) {
                $errors[] = "FAQ {$position} is missing a question.";
            }

            if ($answer === null) {
                $errors[] = "FAQ {$position} is missing an answer.";
            }

            if ($question === null || $answer === null) {
                $invalidCount++;
                continue;
            }

            $validCount++;
        }

        return [
            'valid' => $invalidCount === 0,
            'valid_count' => $validCount,
            'invalid_count' => $invalidCount,
            'errors' => $errors,
        ];
    }

    /**
     * Resolve the first non-empty text value from the supplied keys.
     *
     * @param array<string, mixed> $data
     * @param array<int, string> $keys
     */
    private function firstText(
        array $data,
        array $keys
    ): ?string {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;

            if (! is_scalar($value)) {
                continue;
            }

            $cleaned = $this->cleanText((string) $value);

            if ($cleaned !== null) {
                return $cleaned;
            }
        }

        return null;
    }

    /**
     * Clean text while preserving safe HTML content.
     *
     * Schema FAQ answers may contain basic HTML, but scripts and unsafe
     * embedded elements are removed.
     */
    private function cleanText(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace(
            '#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is',
            '',
            $value
        ) ?? $value;

        $value = preg_replace(
            '#<(script|style|iframe|object|embed)[^>]*/?>#is',
            '',
            $value
        ) ?? $value;

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * Normalise absolute and relative URLs.
     */
    private function normaliseUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return rtrim(url('/'), '/') . '/';
        }

        if (
            str_starts_with($url, 'http://')
            || str_starts_with($url, 'https://')
        ) {
            $parts = parse_url($url);
            $path = is_array($parts)
                ? (string) ($parts['path'] ?? '/')
                : '/';

            if ($path === '' || $path === '/') {
                return rtrim($url, '/') . '/';
            }

            return rtrim($url, '/');
        }

        return url('/' . ltrim($url, '/'));
    }
}