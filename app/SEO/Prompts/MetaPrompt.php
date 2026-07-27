<?php

declare(strict_types=1);

namespace App\SEO\Prompts;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class MetaPrompt
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

        $existingTitle = $this->clean(
            Arr::get($context, 'meta_title'),
        );

        $existingDescription = $this->clean(
            Arr::get($context, 'meta_description'),
        );

        $additionalInstructions = $this->clean(
            Arr::get($context, 'additional_instructions'),
        );

        $locationLine = $city !== ''
            ? "Target location: {$city}"
            : 'Target location: not specifically provided';

        $existingTitleLine = $existingTitle !== ''
            ? $existingTitle
            : 'No existing title provided';

        $existingDescriptionLine = $existingDescription !== ''
            ? $existingDescription
            : 'No existing meta description provided';

        $instructionLine = $additionalInstructions !== ''
            ? $additionalInstructions
            : 'No additional instructions provided';

        return <<<PROMPT
You are a senior SEO strategist and conversion copywriter.

Create optimized SEO metadata for a Dura Cabs page.

PAGE DETAILS

Topic: {$topic}
Focus keyword: {$focusKeyword}
Service type: {$serviceType}
{$locationLine}
Language: {$language}
Tone: {$tone}

CURRENT METADATA

Existing title:
{$existingTitleLine}

Existing meta description:
{$existingDescriptionLine}

ADDITIONAL INSTRUCTIONS

{$instructionLine}

BUSINESS CONTEXT

Brand: Dura Cabs
Business category: Taxi booking, cab services, airport transfers, local rides, round trips, tour travel, self-drive vehicles, and mobility services.
Primary market: India

IMPORTANT RULES

1. Do not invent prices, discounts, ratings, awards, customer counts, guarantees, or service availability.
2. Do not use unsupported claims such as:
   - cheapest
   - number one
   - best in India
   - guaranteed lowest price
3. Use natural and factual wording.
4. Include the focus keyword naturally.
5. Avoid keyword stuffing.
6. Do not use clickbait.
7. Do not use fake urgency.
8. Keep the result original.
9. Mention Dura Cabs naturally where appropriate.
10. Do not add phone numbers, URLs, or unsupported locations.

SEO TITLE REQUIREMENTS

1. Prefer 50 to 60 characters.
2. Put the focus keyword near the beginning where natural.
3. Keep the title readable and attractive.
4. Avoid repeating the same word unnecessarily.
5. Do not use all capital letters.
6. Use only one title separator where useful:
   - |
   - -
7. Include the brand name only when it fits naturally.
8. Do not cut words merely to meet the character target.

META DESCRIPTION REQUIREMENTS

1. Prefer 145 to 160 characters.
2. Include the focus keyword naturally.
3. Explain the page value clearly.
4. Include a soft call to action.
5. Avoid unsupported superlatives.
6. Write one complete sentence or two short connected sentences.
7. Do not end with incomplete wording.
8. Do not repeat the title exactly.

SLUG REQUIREMENTS

1. Lowercase only.
2. Hyphen-separated.
3. Concise and readable.
4. Include the main keyword concept.
5. Do not include:
   - domain names
   - dates unless essential
   - stop words unnecessarily
   - special characters
   - leading or trailing hyphens

OUTPUT FORMAT

Return only valid JSON.

Do not use Markdown code fences.
Do not include any explanation before or after the JSON.
Do not include comments.

Use exactly this JSON structure:

{
  "seo_title": "string",
  "meta_title": "string",
  "meta_description": "string",
  "slug": "string",
  "focus_keyword": "string",
  "secondary_keywords": [
    "string"
  ]
}

OUTPUT FIELD RULES

1. "seo_title" and "meta_title" should normally contain the same optimized title.
2. "focus_keyword" should be a clean search phrase.
3. "secondary_keywords" should contain 5 to 8 relevant phrases.
4. All keywords must be relevant to the page topic.
5. Ensure the JSON is valid and all strings are properly escaped.
PROMPT;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function buildTitlePrompt(array $context): string
    {
        return $this->buildSpecializedPrompt(
            context: $context,
            task: 'title',
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function buildDescriptionPrompt(array $context): string
    {
        return $this->buildSpecializedPrompt(
            context: $context,
            task: 'description',
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(array $context): string
    {
        return $this->build($context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildSpecializedPrompt(
        array $context,
        string $task,
    ): string {
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

        $locationLine = $city !== ''
            ? "Target location: {$city}"
            : 'Target location: not specifically provided';

        if ($task === 'title') {
            return <<<PROMPT
You are a senior SEO strategist.

Generate SEO title suggestions for a Dura Cabs page.

Topic: {$topic}
Focus keyword: {$focusKeyword}
Service type: {$serviceType}
{$locationLine}
Language: {$language}
Tone: {$tone}

Rules:

1. Generate exactly 5 title suggestions.
2. Prefer 50 to 60 characters.
3. Include the focus keyword naturally.
4. Keep every suggestion distinct.
5. Avoid clickbait and unsupported claims.
6. Do not invent prices, ratings, awards, guarantees, or availability.
7. Include Dura Cabs only when it fits naturally.
8. Do not use all capital letters.
9. Do not repeat words unnecessarily.

Return only valid JSON using this exact structure:

{
  "titles": [
    "string",
    "string",
    "string",
    "string",
    "string"
  ]
}

Do not include Markdown code fences or explanations.
PROMPT;
        }

        return <<<PROMPT
You are a senior SEO strategist and conversion copywriter.

Generate meta description suggestions for a Dura Cabs page.

Topic: {$topic}
Focus keyword: {$focusKeyword}
Service type: {$serviceType}
{$locationLine}
Language: {$language}
Tone: {$tone}

Rules:

1. Generate exactly 5 meta description suggestions.
2. Prefer 145 to 160 characters.
3. Include the focus keyword naturally.
4. Keep every suggestion distinct.
5. Explain the page value clearly.
6. Include a soft call to action where natural.
7. Avoid clickbait and fake urgency.
8. Do not invent prices, ratings, awards, guarantees, or availability.
9. Do not use unsupported claims such as cheapest or number one.
10. Each description must be a complete sentence.

Return only valid JSON using this exact structure:

{
  "descriptions": [
    "string",
    "string",
    "string",
    "string",
    "string"
  ]
}

Do not include Markdown code fences or explanations.
PROMPT;
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
                strip_tags((string) $value),
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
}