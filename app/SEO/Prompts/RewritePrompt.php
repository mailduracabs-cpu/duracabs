<?php

declare(strict_types=1);

namespace App\SEO\Prompts;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class RewritePrompt
{
    /**
     * @param array<string, mixed> $context
     */
    public function build(array $context): string
    {
        $content = $this->cleanContent(
            Arr::get($context, 'content')
                ?? Arr::get($context, 'existing_content'),
        );

        $mode = $this->normalizeMode(
            Arr::get($context, 'mode', 'rewrite'),
        );

        $topic = $this->clean(
            Arr::get($context, 'topic'),
            'Taxi service',
        );

        $focusKeyword = $this->clean(
            Arr::get($context, 'focus_keyword'),
            $topic,
        );

        $city = $this->clean(
            Arr::get($context, 'city'),
        );

        $serviceType = $this->clean(
            Arr::get($context, 'service_type'),
            'SEO landing page',
        );

        $language = $this->clean(
            Arr::get($context, 'language'),
            'English',
        );

        $tone = $this->clean(
            Arr::get($context, 'tone'),
            'Professional',
        );

        $targetWordCount = $this->normalizeWordCount(
            Arr::get($context, 'word_count', 800),
        );

        $additionalInstructions = $this->cleanContent(
            Arr::get($context, 'additional_instructions'),
        );

        $cityLine = $city !== ''
            ? "Target location: {$city}"
            : 'Target location: not specifically provided';

        $modeInstructions = $this->modeInstructions(
            mode: $mode,
            targetWordCount: $targetWordCount,
        );

        $additionalInstructionsBlock = $additionalInstructions !== ''
            ? $additionalInstructions
            : 'No additional instructions were provided.';

        $contentBlock = $content !== ''
            ? $content
            : 'No usable source content was provided.';

        return <<<PROMPT
You are a senior SEO editor, conversion copywriter, readability specialist, and travel-industry content writer.

Rewrite the supplied content for a Dura Cabs page.

TASK MODE

{$modeInstructions}

PAGE CONTEXT

Topic: {$topic}
Focus keyword: {$focusKeyword}
Service type: {$serviceType}
{$cityLine}
Language: {$language}
Tone: {$tone}
Target length: approximately {$targetWordCount} words

BUSINESS CONTEXT

Brand name: Dura Cabs

Business category:
- Taxi booking
- One-way cab service
- Round-trip taxi
- Airport transfer
- Local taxi
- Tour travel
- Self-drive vehicles
- Related mobility services

Primary market: India

CORE REWRITE RULES

1. Preserve factual meaning from the source content.
2. Improve grammar, spelling, clarity, structure, readability, and flow.
3. Remove duplicate sentences, repetitive benefits, filler, and awkward wording.
4. Keep the writing natural and useful for real customers.
5. Do not keyword-stuff.
6. Include the focus keyword naturally where appropriate.
7. Use related search phrases naturally.
8. Use short paragraphs suitable for mobile screens.
9. Use clear headings when the content is long enough.
10. Keep the tone consistent throughout.
11. Do not copy wording from other websites.
12. Do not mention that the content was rewritten by AI.

FACTUAL SAFETY RULES

1. Do not invent:
   - prices
   - discounts
   - distance
   - travel time
   - toll charges
   - taxes
   - ratings
   - customer counts
   - fleet size
   - awards
   - guarantees
   - service availability
   - booking policies
   - cancellation policies
   - refund policies

2. Do not add phone numbers, email addresses, office addresses, or URLs.

3. Do not make unsupported claims such as:
   - cheapest
   - number one
   - best in India
   - guaranteed lowest fare
   - always available
   - fully guaranteed
   - completely safe

4. Use cautious wording where facts depend on a booking, route, city, vehicle, or availability.

5. Preserve valid factual details from the source, but do not exaggerate them.

SEO RULES

1. Use the primary focus keyword naturally in:
   - the introduction
   - at least one relevant heading
   - the body
   - the closing section

2. Do not force the keyword into every paragraph.

3. Use a logical heading hierarchy.

4. Do not include an H1 tag because the page name will act as the H1.

5. Keep headings descriptive and search-friendly.

6. End with a natural call to action when suitable.

HTML RULES

Return clean HTML using only:

- <h2>
- <h3>
- <p>
- <ul>
- <ol>
- <li>
- <strong>
- <em>

Do not include:

- <html>
- <head>
- <body>
- <h1>
- scripts
- styles
- classes
- inline CSS
- iframe
- tables
- forms
- comments

SOURCE CONTENT

{$contentBlock}

ADDITIONAL INSTRUCTIONS

{$additionalInstructionsBlock}

OUTPUT FORMAT

Return only valid JSON.

Do not use Markdown code fences.
Do not include explanations before or after the JSON.
Do not include comments.

Use exactly this JSON structure:

{
  "content": "HTML content",
  "description": "HTML content",
  "excerpt": "string",
  "cta": "string"
}

OUTPUT FIELD RULES

1. "content" and "description" may contain the same rewritten article.
2. "excerpt" should summarize the rewritten content in approximately 30 to 50 words.
3. "cta" should contain one short, natural call to action.
4. All JSON strings must be correctly escaped.
5. Do not return empty fields.
PROMPT;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function rewrite(array $context): string
    {
        $context['mode'] = 'rewrite';

        return $this->build($context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function expand(array $context): string
    {
        $context['mode'] = 'expand';

        return $this->build($context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function shorten(array $context): string
    {
        $context['mode'] = 'shorten';

        return $this->build($context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function humanize(array $context): string
    {
        $context['mode'] = 'humanize';

        return $this->build($context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function improveSeo(array $context): string
    {
        $context['mode'] = 'improve_seo';

        return $this->build($context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(array $context): string
    {
        return $this->build($context);
    }

    private function modeInstructions(
        string $mode,
        int $targetWordCount,
    ): string {
        return match ($mode) {
            'expand' => <<<TEXT
Expand the source content into a more complete and useful article.

Requirements:
- Add helpful context and relevant sections.
- Improve topic coverage without adding unsupported facts.
- Target approximately {$targetWordCount} words.
- Do not pad the content with repetition or generic filler.
TEXT,

            'shorten' => <<<TEXT
Shorten the source content while preserving its main meaning and useful facts.

Requirements:
- Remove repetition, filler, and weak sentences.
- Keep the most valuable customer information.
- Make the result direct and easy to scan.
- Keep the result below approximately {$targetWordCount} words.
TEXT,

            'humanize' => <<<TEXT
Humanize the source content.

Requirements:
- Make it sound natural, warm, and written by an experienced human editor.
- Remove robotic phrases, repetitive transitions, and unnatural keyword use.
- Vary sentence length naturally.
- Keep the content professional and trustworthy.
- Preserve the original factual meaning.
TEXT,

            'improve_seo' => <<<TEXT
Improve the SEO quality of the source content.

Requirements:
- Strengthen topic relevance and heading structure.
- Use the focus keyword and related phrases naturally.
- Improve search intent coverage.
- Improve readability and internal consistency.
- Do not sacrifice natural writing for keyword usage.
- Target approximately {$targetWordCount} words.
TEXT,

            default => <<<TEXT
Rewrite the source content into a polished, clear, and professional article.

Requirements:
- Preserve factual meaning.
- Improve structure, readability, grammar, and conversion quality.
- Remove repetition and weak wording.
- Target approximately {$targetWordCount} words.
TEXT,
        };
    }

    private function normalizeMode(mixed $value): string
    {
        if (! is_scalar($value)) {
            return 'rewrite';
        }

        $mode = Str::snake(
            trim((string) $value),
        );

        return in_array(
            $mode,
            [
                'rewrite',
                'expand',
                'shorten',
                'humanize',
                'improve_seo',
            ],
            true,
        )
            ? $mode
            : 'rewrite';
    }

    private function normalizeWordCount(mixed $value): int
    {
        $wordCount = filter_var(
            $value,
            FILTER_VALIDATE_INT,
        );

        if ($wordCount === false) {
            return 800;
        }

        return max(
            150,
            min(3000, $wordCount),
        );
    }

    private function clean(
        mixed $value,
        string $fallback = '',
    ): string {
        if (! is_scalar($value)) {
            return $fallback;
        }

        $value = html_entity_decode(
            strip_tags((string) $value),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        $value = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $value,
            ) ?? '',
        );

        $value = Str::limit(
            $value,
            1000,
            '',
        );

        return $value !== ''
            ? $value
            : $fallback;
    }

    private function cleanContent(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $value = html_entity_decode(
            strip_tags(
                (string) $value,
                '<h2><h3><p><ul><ol><li><strong><em>',
            ),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        $value = trim($value);

        return Str::limit(
            $value,
            12000,
            '...',
        );
    }
}