<?php

declare(strict_types=1);

namespace App\SEO\Services;

use App\SEO\AI\AiManager;
use App\SEO\Prompts\FaqPrompt;
use App\SEO\Prompts\MetaPrompt;
use App\SEO\Prompts\RewritePrompt;
use App\SEO\Prompts\SeoContentPrompt;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;

final class SeoSuggestionService
{
    public function __construct(
        private readonly AiManager $aiManager,
        private readonly SeoContentPrompt $seoContentPrompt,
        private readonly MetaPrompt $metaPrompt,
        private readonly FaqPrompt $faqPrompt,
        private readonly RewritePrompt $rewritePrompt,
        private readonly SeoAnalysisService $seoAnalysisService,
    ) {
    }

    /**
     * Complete SEO page content generate karta hai.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function generateContent(
        array $context,
        ?string $provider = null,
    ): array {
        $prompt = $this->seoContentPrompt->build($context);

        $result = $this->requestJson(
            prompt: $prompt,
            provider: $provider,
        );

        return $this->normalizeCompleteContent(
            data: $result,
            context: $context,
        );
    }

    /**
     * SEO title, meta description, slug aur keywords generate karta hai.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function generateMetadata(
        array $context,
        ?string $provider = null,
    ): array {
        $prompt = $this->metaPrompt->build($context);

        $result = $this->requestJson(
            prompt: $prompt,
            provider: $provider,
        );

        return $this->normalizeMetadata(
            data: $result,
            context: $context,
        );
    }

    /**
     * Multiple SEO title suggestions generate karta hai.
     *
     * @param array<string, mixed> $context
     * @return array<int, string>
     */
    public function generateTitles(
        array $context,
        ?string $provider = null,
    ): array {
        $prompt = $this->metaPrompt->buildTitlePrompt($context);

        $result = $this->requestJson(
            prompt: $prompt,
            provider: $provider,
        );

        return $this->normalizeStringList(
            value: Arr::get($result, 'titles', []),
            maximum: 5,
        );
    }

    /**
     * Multiple meta-description suggestions generate karta hai.
     *
     * @param array<string, mixed> $context
     * @return array<int, string>
     */
    public function generateMetaDescriptions(
        array $context,
        ?string $provider = null,
    ): array {
        $prompt = $this->metaPrompt->buildDescriptionPrompt($context);

        $result = $this->requestJson(
            prompt: $prompt,
            provider: $provider,
        );

        return $this->normalizeStringList(
            value: Arr::get($result, 'descriptions', []),
            maximum: 5,
        );
    }

    /**
     * FAQ suggestions generate karta hai.
     *
     * @param array<string, mixed> $context
     * @return array<int, array{question: string, answer: string}>
     */
    public function generateFaqs(
        array $context,
        ?string $provider = null,
    ): array {
        $prompt = $this->faqPrompt->build($context);

        $result = $this->requestJson(
            prompt: $prompt,
            provider: $provider,
        );

        $maximum = $this->normalizeFaqCount(
            Arr::get($context, 'max_faqs', 5),
        );

        return $this->normalizeFaqs(
            value: Arr::get($result, 'faqs', []),
            maximum: $maximum,
        );
    }

    /**
     * Existing content ko selected mode ke hisaab se rewrite karta hai.
     *
     * Supported modes:
     * rewrite, expand, shorten, humanize, improve_seo
     *
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    public function rewrite(
        array $context,
        string $mode = 'rewrite',
        ?string $provider = null,
    ): array {
        $context['mode'] = $mode;

        $prompt = $this->rewritePrompt->build($context);

        $result = $this->requestJson(
            prompt: $prompt,
            provider: $provider,
        );

        return $this->normalizeRewriteResult($result);
    }

    /**
     * Existing content expand karta hai.
     *
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    public function expand(
        array $context,
        ?string $provider = null,
    ): array {
        return $this->rewrite(
            context: $context,
            mode: 'expand',
            provider: $provider,
        );
    }

    /**
     * Existing content short karta hai.
     *
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    public function shorten(
        array $context,
        ?string $provider = null,
    ): array {
        return $this->rewrite(
            context: $context,
            mode: 'shorten',
            provider: $provider,
        );
    }

    /**
     * Existing content humanize karta hai.
     *
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    public function humanize(
        array $context,
        ?string $provider = null,
    ): array {
        return $this->rewrite(
            context: $context,
            mode: 'humanize',
            provider: $provider,
        );
    }

    /**
     * Existing content ka SEO improve karta hai.
     *
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    public function improveSeo(
        array $context,
        ?string $provider = null,
    ): array {
        return $this->rewrite(
            context: $context,
            mode: 'improve_seo',
            provider: $provider,
        );
    }

    /**
     * AI call ke bina page ki local index-readiness suggestions return karta hai.
     *
     * Actual Google indexing status yahan claim nahi kiya jata. Ye method sirf
     * technical blockers, warnings aur recommended actions batata hai.
     *
     * @param array<string, mixed> $context
     * @return array{
     *     index_ready: bool,
     *     status: string,
     *     status_label: string,
     *     status_color: string,
     *     page_url: string,
     *     sitemap_eligible: bool,
     *     priority: string,
     *     summary: string,
     *     blockers: array<int, array<string, string>>,
     *     warnings: array<int, array<string, string>>,
     *     actions: array<int, array<string, string>>
     * }
     */
    public function indexingSuggestions(array $context): array
    {
        $analysis = $this->seoAnalysisService
            ->analyzeIndexReadiness($context);

        $actions = [];

        foreach ($analysis['blockers'] as $blocker) {
            $actions[] = [
                'priority' => 'high',
                'code' => (string) ($blocker['code'] ?? 'indexing_blocker'),
                'title' => (string) ($blocker['title'] ?? 'Indexing blocker'),
                'action' => (string) (
                    $blocker['recommendation']
                    ?? 'Resolve this issue before requesting indexing.'
                ),
            ];
        }

        foreach ($analysis['warnings'] as $warning) {
            $actions[] = [
                'priority' => 'medium',
                'code' => (string) ($warning['code'] ?? 'seo_warning'),
                'title' => (string) ($warning['title'] ?? 'SEO warning'),
                'action' => (string) (
                    $warning['recommendation']
                    ?? 'Review this recommendation.'
                ),
            ];
        }

        if ($analysis['index_ready']) {
            $actions[] = [
                'priority' => 'low',
                'code' => 'submit_sitemap',
                'title' => 'Include in sitemap',
                'action' => 'Ensure this URL is present in the XML sitemap.',
            ];

            $actions[] = [
                'priority' => 'low',
                'code' => 'inspect_url',
                'title' => 'Inspect in Search Console',
                'action' => 'Use Google Search Console URL Inspection after publishing.',
            ];
        }

        $priority = match (true) {
            $analysis['blockers'] !== [] => 'high',
            $analysis['warnings'] !== [] => 'medium',
            default => 'low',
        };

        return [
            'index_ready' => (bool) $analysis['index_ready'],
            'status' => (string) $analysis['status'],
            'status_label' => (string) $analysis['status_label'],
            'status_color' => (string) $analysis['status_color'],
            'page_url' => (string) $analysis['page_url'],
            'sitemap_eligible' => (bool) $analysis['sitemap_eligible'],
            'priority' => $priority,
            'summary' => $this->buildIndexingSummary($analysis),
            'blockers' => $analysis['blockers'],
            'warnings' => $analysis['warnings'],
            'actions' => $actions,
        ];
    }

    /**
     * Filament notification ya compact status card ke liye short text.
     *
     * @param array<string, mixed> $context
     */
    public function indexingSummary(array $context): string
    {
        return $this->indexingSuggestions($context)['summary'];
    }

    /**
     * @param array<string, mixed> $analysis
     */
    private function buildIndexingSummary(array $analysis): string
    {
        $blockerCount = count($analysis['blockers'] ?? []);
        $warningCount = count($analysis['warnings'] ?? []);

        if ($blockerCount > 0) {
            return sprintf(
                '%s. Resolve %d indexing blocker%s and review %d warning%s.',
                (string) ($analysis['status_label'] ?? 'Needs Attention'),
                $blockerCount,
                $blockerCount === 1 ? '' : 's',
                $warningCount,
                $warningCount === 1 ? '' : 's',
            );
        }

        if ($warningCount > 0) {
            return sprintf(
                '%s with %d warning%s. The page is sitemap eligible.',
                (string) ($analysis['status_label'] ?? 'Index Ready'),
                $warningCount,
                $warningCount === 1 ? '' : 's',
            );
        }

        return 'Index Ready. No local indexing blockers or warnings were detected.';
    }

    /**
     * AI provider ko prompt bhejkar valid JSON result return karta hai.
     *
     * @return array<string, mixed>
     */
    private function requestJson(
        string $prompt,
        ?string $provider = null,
    ): array {
        try {
            $response = $this->aiManager->generate(
                prompt: $prompt,
                provider: $provider,
                options: [
                    'temperature' => 0.4,
                ],
            );
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException(
                'AI provider se response generate nahi ho saka: '
                . $exception->getMessage(),
                previous: $exception,
            );
        }

        $rawResponse = $this->extractResponseText($response);

        if ($rawResponse === '') {
            throw new RuntimeException(
                'AI provider ne empty response return kiya.',
            );
        }

        return $this->decodeJson($rawResponse);
    }

    /**
     * AiManager ke string ya array response ko plain text me convert karta hai.
     */
    private function extractResponseText(mixed $response): string
    {
        if (is_string($response)) {
            return trim($response);
        }

        if (! is_array($response)) {
            return '';
        }

        $possibleValues = [
            Arr::get($response, 'output_text'),
            Arr::get($response, 'text'),
            Arr::get($response, 'content'),
            Arr::get($response, 'response'),
            Arr::get($response, 'message'),
            Arr::get($response, 'data.output_text'),
            Arr::get($response, 'data.text'),
            Arr::get($response, 'data.content'),
        ];

        foreach ($possibleValues as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    /**
     * Markdown fences ya extra text ke andar se JSON safely decode karta hai.
     *
     * @return array<string, mixed>
     */
    private function decodeJson(string $response): array
    {
        $response = trim($response);

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

        $response = trim($response);

        try {
            $decoded = json_decode(
                $response,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            $jsonObject = $this->extractJsonObject($response);

            if ($jsonObject === null) {
                throw new RuntimeException(
                    'AI response valid JSON format me nahi tha.',
                );
            }

            try {
                $decoded = json_decode(
                    $jsonObject,
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (JsonException $exception) {
                throw new RuntimeException(
                    'AI response ka JSON parse nahi ho saka: '
                    . $exception->getMessage(),
                    previous: $exception,
                );
            }
        }

        if (! is_array($decoded)) {
            throw new RuntimeException(
                'AI response expected JSON object nahi tha.',
            );
        }

        return $decoded;
    }

    private function extractJsonObject(string $response): ?string
    {
        $firstBrace = strpos($response, '{');
        $lastBrace = strrpos($response, '}');

        if (
            $firstBrace === false
            || $lastBrace === false
            || $lastBrace <= $firstBrace
        ) {
            return null;
        }

        return substr(
            $response,
            $firstBrace,
            $lastBrace - $firstBrace + 1,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function normalizeCompleteContent(
        array $data,
        array $context,
    ): array {
        $title = $this->cleanText(
            Arr::get($data, 'seo_title')
                ?: Arr::get($data, 'meta_title'),
        );

        $metaTitle = $this->cleanText(
            Arr::get($data, 'meta_title')
                ?: $title,
        );

        $focusKeyword = $this->cleanText(
            Arr::get($data, 'focus_keyword')
                ?: Arr::get($context, 'focus_keyword')
                ?: Arr::get($context, 'topic'),
        );

        $content = $this->sanitizeHtml(
            Arr::get($data, 'content')
                ?: Arr::get($data, 'description'),
        );

        $description = $this->sanitizeHtml(
            Arr::get($data, 'description')
                ?: $content,
        );

        $slug = Str::slug(
            $this->cleanText(
                Arr::get($data, 'slug')
                    ?: $focusKeyword
                    ?: Arr::get($context, 'topic'),
            ),
        );

        return [
            'seo_title' => $title,
            'meta_title' => $metaTitle,
            'meta_description' => $this->cleanText(
                Arr::get($data, 'meta_description'),
            ),
            'slug' => $slug,
            'focus_keyword' => $focusKeyword,
            'secondary_keywords' => $this->normalizeStringList(
                Arr::get($data, 'secondary_keywords', []),
                10,
            ),
            'content' => $content,
            'description' => $description,
            'excerpt' => $this->cleanText(
                Arr::get($data, 'excerpt'),
            ),
            'cta' => $this->cleanText(
                Arr::get($data, 'cta'),
            ),
            'faqs' => $this->normalizeFaqs(
                Arr::get($data, 'faqs', []),
                $this->normalizeFaqCount(
                    Arr::get($context, 'max_faqs', 5),
                ),
            ),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function normalizeMetadata(
        array $data,
        array $context,
    ): array {
        $title = $this->cleanText(
            Arr::get($data, 'seo_title')
                ?: Arr::get($data, 'meta_title'),
        );

        $focusKeyword = $this->cleanText(
            Arr::get($data, 'focus_keyword')
                ?: Arr::get($context, 'focus_keyword')
                ?: Arr::get($context, 'topic'),
        );

        return [
            'seo_title' => $title,
            'meta_title' => $this->cleanText(
                Arr::get($data, 'meta_title')
                    ?: $title,
            ),
            'meta_description' => $this->cleanText(
                Arr::get($data, 'meta_description'),
            ),
            'slug' => Str::slug(
                $this->cleanText(
                    Arr::get($data, 'slug')
                        ?: $focusKeyword,
                ),
            ),
            'focus_keyword' => $focusKeyword,
            'secondary_keywords' => $this->normalizeStringList(
                Arr::get($data, 'secondary_keywords', []),
                8,
            ),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function normalizeRewriteResult(array $data): array
    {
        $content = $this->sanitizeHtml(
            Arr::get($data, 'content')
                ?: Arr::get($data, 'description'),
        );

        $description = $this->sanitizeHtml(
            Arr::get($data, 'description')
                ?: $content,
        );

        if ($content === '' && $description === '') {
            throw new RuntimeException(
                'AI rewrite response me usable content nahi mila.',
            );
        }

        return [
            'content' => $content,
            'description' => $description,
            'excerpt' => $this->cleanText(
                Arr::get($data, 'excerpt'),
            ),
            'cta' => $this->cleanText(
                Arr::get($data, 'cta'),
            ),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function normalizeStringList(
        mixed $value,
        int $maximum,
    ): array {
        if (is_string($value)) {
            $value = preg_split(
                '/[\r\n,]+/u',
                $value,
            ) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $item = $this->cleanText($item);

            if ($item === '') {
                continue;
            }

            $key = Str::lower($item);

            if (isset($items[$key])) {
                continue;
            }

            $items[$key] = $item;

            if (count($items) >= $maximum) {
                break;
            }
        }

        return array_values($items);
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    private function normalizeFaqs(
        mixed $value,
        int $maximum,
    ): array {
        if (! is_array($value)) {
            return [];
        }

        $faqs = [];

        foreach ($value as $faq) {
            if (! is_array($faq)) {
                continue;
            }

            $question = $this->cleanText(
                Arr::get($faq, 'question'),
            );

            $answer = $this->cleanText(
                Arr::get($faq, 'answer'),
            );

            if ($question === '' || $answer === '') {
                continue;
            }

            $questionKey = Str::lower($question);

            if (isset($faqs[$questionKey])) {
                continue;
            }

            $faqs[$questionKey] = [
                'question' => $question,
                'answer' => $answer,
            ];

            if (count($faqs) >= $maximum) {
                break;
            }
        }

        return array_values($faqs);
    }

    private function cleanText(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $value = html_entity_decode(
            strip_tags((string) $value),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $value,
            ) ?? '',
        );
    }

    private function sanitizeHtml(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $html = trim((string) $value);

        if ($html === '') {
            return '';
        }

        $html = strip_tags(
            $html,
            '<h2><h3><p><ul><ol><li><strong><em>',
        );

        $html = preg_replace(
            '/<(h2|h3|p|ul|ol|li|strong|em)\b[^>]*>/i',
            '<$1>',
            $html,
        ) ?? $html;

        $html = preg_replace(
            '/\son\w+\s*=\s*(["\']).*?\1/iu',
            '',
            $html,
        ) ?? $html;

        return trim($html);
    }

    private function normalizeFaqCount(mixed $value): int
    {
        $count = filter_var(
            $value,
            FILTER_VALIDATE_INT,
        );

        if ($count === false) {
            return 5;
        }

        return max(
            0,
            min(10, $count),
        );
    }
}