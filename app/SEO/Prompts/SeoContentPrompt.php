<?php

declare(strict_types=1);

namespace App\SEO\Prompts;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class SeoContentPrompt
{
    /**
     * @param array<string, mixed> $context
     */
    public function build(array $context): string
    {
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

        $wordCount = $this->normalizeWordCount(
            Arr::get($context, 'word_count', 1000),
        );

        $faqCount = $this->normalizeFaqCount(
            Arr::get($context, 'max_faqs', 5),
        );

        $existingContent = $this->clean(
            Arr::get($context, 'existing_content'),
        );

        $additionalInstructions = $this->clean(
            Arr::get($context, 'additional_instructions'),
        );

        $locationInstruction = $city !== ''
            ? "Target location: {$city}"
            : 'Target location: not specifically provided';

        $existingContentInstruction = $existingContent !== ''
            ? $this->existingContentInstruction($existingContent)
            : 'No existing content was provided. Create fresh and original content.';

        $additionalInstructionBlock = $additionalInstructions !== ''
            ? $additionalInstructions
            : 'No additional business instructions were provided.';

        return <<<PROMPT
You are a senior SEO strategist, local SEO specialist, conversion copywriter, and travel-industry content editor.

Create production-ready SEO content for the following page.

PAGE INFORMATION

Topic: {$topic}
Primary focus keyword: {$focusKeyword}
Service type: {$serviceType}
{$locationInstruction}
Language: {$language}
Tone: {$tone}
Target article length: approximately {$wordCount} words
Required FAQ count: {$faqCount}

BUSINESS CONTEXT

Brand name: Dura Cabs
Business category: Cab booking, taxi services, airport transfers, local rides, round trips, tour travel, self-drive vehicles, and related mobility services.
Primary market: India

IMPORTANT FACTUAL RULES

1. Do not invent prices, discounts, distance, travel duration, fleet size, ratings, customer counts, government approvals, awards, partnerships, or guarantees.
2. Do not claim that a service is available in a city unless that availability is clearly stated in the supplied context.
3. Do not invent phone numbers, email addresses, office addresses, or booking policies.
4. Do not make medical, legal, financial, insurance, or safety guarantees.
5. Avoid fake urgency such as "last few cars remaining" or "offer ends today".
6. Do not claim "cheapest", "number one", "best in India", or similar absolute claims unless explicitly supported.
7. Use cautious wording such as "competitive fares", "easy booking", "professional service", or "subject to availability".
8. Do not keyword-stuff.
9. Do not copy or imitate any existing website.
10. Keep all content original, useful, natural, and written for real customers.

SEO REQUIREMENTS

1. The primary focus keyword should appear naturally in:
   - SEO title
   - meta description
   - opening paragraph
   - at least one heading
   - main body
   - closing call to action

2. Include semantically related keywords and service phrases naturally.

3. Use clear heading structure:
   - one logical H1 concept
   - multiple H2 sections
   - H3 sections only where useful

4. Write short paragraphs suitable for mobile screens.

5. Include practical information that helps a customer understand:
   - what the service is
   - who it is suitable for
   - key benefits
   - booking process
   - what customers should check before booking
   - common questions

6. Avoid repeating the same benefit in multiple sections.

7. Keep the SEO title preferably between 50 and 60 characters where practical.

8. Keep the meta description preferably between 145 and 160 characters where practical.

9. The slug must be lowercase, concise, hyphen-separated, and must not include the website domain.

10. The focus keyword should be a clean search phrase, not a full sentence.

CONTENT STRUCTURE

Create content using this general structure, adapting it naturally to the topic:

- Introduction
- Service overview
- Main benefits
- Why customers may choose Dura Cabs
- How booking works
- Service-specific useful information
- Travel or booking tips
- Call to action
- FAQs

Do not force irrelevant sections.

EXISTING CONTENT

{$existingContentInstruction}

ADDITIONAL INSTRUCTIONS

{$additionalInstructionBlock}

OUTPUT FORMAT

Return only valid JSON.

Do not use Markdown code fences.
Do not include any explanation before or after the JSON.
Do not include comments inside the JSON.

Use exactly this JSON structure:

{
  "seo_title": "string",
  "meta_title": "string",
  "meta_description": "string",
  "slug": "string",
  "focus_keyword": "string",
  "secondary_keywords": [
    "string"
  ],
  "excerpt": "string",
  "description": "HTML content",
  "content": "HTML content",
  "cta": "string",
  "faqs": [
    {
      "question": "string",
      "answer": "string"
    }
  ]
}

OUTPUT FIELD RULES

1. "seo_title" and "meta_title" may contain the same optimized title.
2. "description" and "content" may contain the same complete article.
3. Article content must be clean HTML using only:
   - <h2>
   - <h3>
   - <p>
   - <ul>
   - <ol>
   - <li>
   - <strong>
   - <em>
4. Do not include:
   - <html>
   - <head>
   - <body>
   - scripts
   - styles
   - classes
   - inline CSS
   - iframe
   - forms
5. Do not include an H1 tag because the page name will act as the H1.
6. "excerpt" should be approximately 30 to 50 words.
7. "cta" should be one short and natural booking call to action.
8. "secondary_keywords" should contain 5 to 10 useful related phrases.
9. Return exactly {$faqCount} FAQs when FAQ count is greater than zero.
10. Return an empty FAQ array when FAQ count is zero.
11. Every FAQ answer should be useful, concise, and factually cautious.
12. Ensure all JSON strings are properly escaped.
PROMPT;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(array $context): string
    {
        return $this->build($context);
    }

    private function existingContentInstruction(string $content): string
    {
        $plainContent = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                strip_tags($content),
            ) ?? '',
        );

        if ($plainContent === '') {
            return 'The existing content was empty after cleaning. Create fresh and original content.';
        }

        $limitedContent = Str::limit(
            $plainContent,
            6000,
            '...',
        );

        return <<<TEXT
Use the existing content only as factual reference.

Improve its structure, clarity, SEO value, readability, and conversion quality.

Do not preserve weak wording merely because it already exists.
Do not copy sentences unnecessarily.
Do not introduce facts that are not present.

Existing content:
{$limitedContent}
TEXT;
    }

    private function normalizeWordCount(mixed $value): int
    {
        $wordCount = filter_var(
            $value,
            FILTER_VALIDATE_INT,
        );

        if ($wordCount === false) {
            return 1000;
        }

        return max(
            300,
            min(3000, $wordCount),
        );
    }

    private function normalizeFaqCount(mixed $value): int
    {
        $faqCount = filter_var(
            $value,
            FILTER_VALIDATE_INT,
        );

        if ($faqCount === false) {
            return 5;
        }

        return max(
            0,
            min(10, $faqCount),
        );
    }

    private function clean(
        mixed $value,
        string $fallback = '',
    ): string {
        if (! is_scalar($value)) {
            return $fallback;
        }

        $value = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string) $value,
            ) ?? '',
        );

        return $value !== ''
            ? $value
            : $fallback;
    }
}