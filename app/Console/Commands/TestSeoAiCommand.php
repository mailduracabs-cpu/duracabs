<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\SEO\AI\AiManager;
use App\SEO\Services\SeoSuggestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use JsonException;
use Throwable;

final class TestSeoAiCommand extends Command
{
    /**
     * Command name and options.
     *
     * Examples:
     *
     * php artisan seo-ai:test
     * php artisan seo-ai:test --mode=meta
     * php artisan seo-ai:test --mode=faq --city=Agra
     * php artisan seo-ai:test --mode=rewrite
     */
    protected $signature = 'seo-ai:test
        {--mode=content : Test mode: content, meta, titles, descriptions, faq, rewrite, expand, shorten, humanize, improve-seo, provider}
        {--provider= : AI provider name, for example openai}
        {--topic=Agra to Delhi Cab Service : Main page topic}
        {--keyword=Agra to Delhi taxi service : Focus keyword}
        {--city=Agra : Target city}
        {--service=one_way : Service type}
        {--language=English : Output language}
        {--tone=Professional : Writing tone}
        {--words=700 : Target word count}
        {--faqs=5 : Number of FAQs}
        {--content= : Existing content for rewrite testing}
        {--raw : Show raw JSON output}';

    /**
     * Command description.
     */
    protected $description = 'Test Dura Cabs SEO AI providers, prompts, and suggestion services.';

    public function __construct(
        private readonly SeoSuggestionService $seoSuggestionService,
        private readonly AiManager $aiManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $mode = $this->normalizeMode(
            (string) $this->option('mode'),
        );

        $provider = $this->normalizeNullableString(
            $this->option('provider'),
        );

        $context = $this->buildContext();

        $this->newLine();
        $this->components->info('Dura SEO AI Test');
        $this->line('Mode: <fg=cyan>' . $mode . '</>');
        $this->line(
            'Provider: <fg=cyan>'
            . ($provider ?? 'default')
            . '</>',
        );
        $this->line(
            'Topic: <fg=cyan>'
            . $context['topic']
            . '</>',
        );
        $this->newLine();

        try {
            $result = match ($mode) {
                'provider' => $this->testProvider(
                    context: $context,
                    provider: $provider,
                ),

                'meta' => $this->seoSuggestionService->generateMetadata(
                    context: $context,
                    provider: $provider,
                ),

                'titles' => [
                    'titles' => $this->seoSuggestionService->generateTitles(
                        context: $context,
                        provider: $provider,
                    ),
                ],

                'descriptions' => [
                    'descriptions' => $this->seoSuggestionService
                        ->generateMetaDescriptions(
                            context: $context,
                            provider: $provider,
                        ),
                ],

                'faq' => [
                    'faqs' => $this->seoSuggestionService->generateFaqs(
                        context: $context,
                        provider: $provider,
                    ),
                ],

                'rewrite' => $this->seoSuggestionService->rewrite(
                    context: $context,
                    mode: 'rewrite',
                    provider: $provider,
                ),

                'expand' => $this->seoSuggestionService->expand(
                    context: $context,
                    provider: $provider,
                ),

                'shorten' => $this->seoSuggestionService->shorten(
                    context: $context,
                    provider: $provider,
                ),

                'humanize' => $this->seoSuggestionService->humanize(
                    context: $context,
                    provider: $provider,
                ),

                'improve-seo' => $this->seoSuggestionService->improveSeo(
                    context: $context,
                    provider: $provider,
                ),

                default => $this->seoSuggestionService->generateContent(
                    context: $context,
                    provider: $provider,
                ),
            };
        } catch (Throwable $exception) {
            report($exception);

            $this->components->error(
                'SEO AI test failed: ' . $exception->getMessage(),
            );

            if ($this->getOutput()->isVerbose()) {
                $this->newLine();
                $this->line($exception->getTraceAsString());
            }

            return self::FAILURE;
        }

        $this->displayResult($result);

        $this->newLine();
        $this->components->success('SEO AI test completed successfully.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(): array
    {
        $wordCount = filter_var(
            $this->option('words'),
            FILTER_VALIDATE_INT,
        );

        $faqCount = filter_var(
            $this->option('faqs'),
            FILTER_VALIDATE_INT,
        );

        $content = $this->normalizeNullableString(
            $this->option('content'),
        );

        if ($content === null) {
            $content = <<<'HTML'
<p>Dura Cabs provides taxi booking services for customers planning intercity and local travel. Customers can select a suitable ride type and submit their trip details online.</p>
<p>Service availability and final fare may depend on the selected route, pickup location, travel date, and vehicle category.</p>
HTML;
        }

        return [
            'topic' => $this->normalizeString(
                $this->option('topic'),
                'Agra to Delhi Cab Service',
            ),

            'focus_keyword' => $this->normalizeString(
                $this->option('keyword'),
                'Agra to Delhi taxi service',
            ),

            'city' => $this->normalizeString(
                $this->option('city'),
                'Agra',
            ),

            'service_type' => $this->normalizeString(
                $this->option('service'),
                'one_way',
            ),

            'language' => $this->normalizeString(
                $this->option('language'),
                'English',
            ),

            'tone' => $this->normalizeString(
                $this->option('tone'),
                'Professional',
            ),

            'word_count' => $wordCount !== false
                ? max(150, min(3000, $wordCount))
                : 700,

            'max_faqs' => $faqCount !== false
                ? max(1, min(10, $faqCount))
                : 5,

            'existing_content' => $content,
            'content' => $content,

            'additional_instructions' =>
                'Keep the content factual, useful, and suitable for Dura Cabs customers in India.',
        ];
    }

    /**
     * Direct provider connectivity test.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function testProvider(
        array $context,
        ?string $provider,
    ): array {
        $topic = Arr::get(
            $context,
            'topic',
            'Taxi service',
        );

        $response = $this->aiManager->generate(
            prompt: <<<PROMPT
Return only valid JSON without Markdown.

Create one short SEO title for this topic:

{$topic}

Use this exact structure:

{
  "status": "ok",
  "title": "string"
}
PROMPT,
            provider: $provider,
            options: [
                'temperature' => 0.2,
            ],
        );

        if (is_array($response)) {
            return $response;
        }

        return [
            'raw_response' => is_scalar($response)
                ? trim((string) $response)
                : '',
        ];
    }

    /**
     * @param array<string, mixed> $result
     */
    private function displayResult(array $result): void
    {
        if ((bool) $this->option('raw')) {
            $this->displayJson($result);

            return;
        }

        if (isset($result['seo_title'])) {
            $this->displayCompleteContent($result);

            return;
        }

        if (isset($result['titles']) && is_array($result['titles'])) {
            $this->displayStringList(
                title: 'SEO Title Suggestions',
                items: $result['titles'],
            );

            return;
        }

        if (
            isset($result['descriptions'])
            && is_array($result['descriptions'])
        ) {
            $this->displayStringList(
                title: 'Meta Description Suggestions',
                items: $result['descriptions'],
            );

            return;
        }

        if (isset($result['faqs']) && is_array($result['faqs'])) {
            $this->displayFaqs($result['faqs']);

            return;
        }

        if (
            isset($result['content'])
            || isset($result['description'])
        ) {
            $this->displayRewriteResult($result);

            return;
        }

        $this->displayJson($result);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function displayCompleteContent(array $result): void
    {
        $this->components->twoColumnDetail(
            'SEO title',
            (string) Arr::get($result, 'seo_title', '—'),
        );

        $this->components->twoColumnDetail(
            'Meta title',
            (string) Arr::get($result, 'meta_title', '—'),
        );

        $this->components->twoColumnDetail(
            'Meta description',
            (string) Arr::get($result, 'meta_description', '—'),
        );

        $this->components->twoColumnDetail(
            'Slug',
            (string) Arr::get($result, 'slug', '—'),
        );

        $this->components->twoColumnDetail(
            'Focus keyword',
            (string) Arr::get($result, 'focus_keyword', '—'),
        );

        $secondaryKeywords = Arr::get(
            $result,
            'secondary_keywords',
            [],
        );

        if (is_array($secondaryKeywords)) {
            $this->components->twoColumnDetail(
                'Secondary keywords',
                implode(
                    ', ',
                    array_map(
                        static fn (mixed $item): string => (string) $item,
                        $secondaryKeywords,
                    ),
                ),
            );
        }

        $this->newLine();
        $this->components->info('Excerpt');
        $this->line(
            (string) Arr::get($result, 'excerpt', '—'),
        );

        $this->newLine();
        $this->components->info('CTA');
        $this->line(
            (string) Arr::get($result, 'cta', '—'),
        );

        $this->newLine();
        $this->components->info('Generated Content');
        $this->line(
            (string) (
                Arr::get($result, 'content')
                ?: Arr::get($result, 'description')
                ?: '—'
            ),
        );

        $faqs = Arr::get($result, 'faqs', []);

        if (is_array($faqs) && $faqs !== []) {
            $this->displayFaqs($faqs);
        }
    }

    /**
     * @param array<string, mixed> $result
     */
    private function displayRewriteResult(array $result): void
    {
        $this->components->info('Rewritten Content');

        $this->line(
            (string) (
                Arr::get($result, 'content')
                ?: Arr::get($result, 'description')
                ?: '—'
            ),
        );

        $excerpt = trim(
            (string) Arr::get($result, 'excerpt', ''),
        );

        if ($excerpt !== '') {
            $this->newLine();
            $this->components->info('Excerpt');
            $this->line($excerpt);
        }

        $cta = trim(
            (string) Arr::get($result, 'cta', ''),
        );

        if ($cta !== '') {
            $this->newLine();
            $this->components->info('CTA');
            $this->line($cta);
        }
    }

    /**
     * @param array<int, mixed> $items
     */
    private function displayStringList(
        string $title,
        array $items,
    ): void {
        $this->components->info($title);

        if ($items === []) {
            $this->warn('No suggestions returned.');

            return;
        }

        foreach (array_values($items) as $index => $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $this->line(
                sprintf(
                    '%d. %s',
                    $index + 1,
                    trim((string) $item),
                ),
            );
        }
    }

    /**
     * @param array<int, mixed> $faqs
     */
    private function displayFaqs(array $faqs): void
    {
        $this->newLine();
        $this->components->info('FAQs');

        if ($faqs === []) {
            $this->warn('No FAQs returned.');

            return;
        }

        foreach (array_values($faqs) as $index => $faq) {
            if (! is_array($faq)) {
                continue;
            }

            $question = trim(
                (string) Arr::get($faq, 'question', ''),
            );

            $answer = trim(
                (string) Arr::get($faq, 'answer', ''),
            );

            if ($question === '' || $answer === '') {
                continue;
            }

            $this->newLine();
            $this->line(
                '<fg=yellow>'
                . ($index + 1)
                . '. '
                . $question
                . '</>',
            );

            $this->line($answer);
        }
    }

    /**
     * @param array<string, mixed> $result
     */
    private function displayJson(array $result): void
    {
        try {
            $json = json_encode(
                $result,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            $this->components->error(
                'Result JSON me convert nahi ho saka: '
                . $exception->getMessage(),
            );

            return;
        }

        $this->line($json);
    }

    private function normalizeMode(string $mode): string
    {
        $mode = strtolower(
            trim($mode),
        );

        $mode = str_replace(
            '_',
            '-',
            $mode,
        );

        $supportedModes = [
            'content',
            'meta',
            'titles',
            'descriptions',
            'faq',
            'rewrite',
            'expand',
            'shorten',
            'humanize',
            'improve-seo',
            'provider',
        ];

        return in_array(
            $mode,
            $supportedModes,
            true,
        )
            ? $mode
            : 'content';
    }

    private function normalizeString(
        mixed $value,
        string $fallback,
    ): string {
        if (! is_scalar($value)) {
            return $fallback;
        }

        $value = trim(
            (string) $value,
        );

        return $value !== ''
            ? $value
            : $fallback;
    }

    private function normalizeNullableString(
        mixed $value,
    ): ?string {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim(
            (string) $value,
        );

        return $value !== ''
            ? $value
            : null;
    }
}