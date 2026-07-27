<?php

declare(strict_types=1);

namespace App\SEO\Prompts;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class FaqPrompt
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

        $faqCount = $this->normalizeFaqCount(
            Arr::get($context, 'max_faqs', 5),
        );

        $existingContent = $this->cleanLongContent(
            Arr::get($context, 'existing_content'),
        );

        $additionalInstructions = $this->cleanLongContent(
            Arr::get($context, 'additional_instructions'),
        );

        $locationLine = $city !== ''
            ? "Target location: {$city}"
            : 'Target location: not specifically provided';

        $existingContentBlock = $existingContent !== ''
            ? <<<TEXT
Use the following page content as factual reference:

{$existingContent}
TEXT
            : 'No existing page content was provided.';

        $additionalInstructionsBlock = $additionalInstructions !== ''
            ? <<<TEXT
Additional business instructions:

{$additionalInstructions}
TEXT
            : 'No additional business instructions were provided.';

        return <<<PROMPT
You are a senior SEO strategist, customer-support content writer, and local travel-service expert.

Create useful frequently asked questions for a Dura Cabs page.

PAGE DETAILS

Topic: {$topic}
Focus keyword: {$focusKeyword}
Service type: {$serviceType}
{$locationLine}
Language: {$language}
Tone: {$tone}
Required FAQ count: {$faqCount}

BUSINESS CONTEXT

Brand name: Dura Cabs

Business category:
- Taxi booking
- One-way cab service
- Round-trip taxi
- Airport transfer
- Local taxi
- Tour travel
- Self-drive car
- Related mobility services

Primary market: India

FACTUAL SAFETY RULES

1. Do not invent prices, discounts, toll charges, taxes, distance, travel time, service availability, fleet size, customer counts, ratings, awards, or guarantees.
2. Do not invent cancellation, refund, payment, driver, vehicle, luggage, waiting-time, or booking policies.
3. Do not add phone numbers, email addresses, office addresses, or external links.
4. Do not state that every vehicle, driver, or booking has a feature unless supplied in the context.
5. Do not claim:
   - cheapest
   - number one
   - best in India
   - guaranteed lowest fare
   - always available
   - zero cancellation
6. Use cautious wording such as:
   - subject to availability
   - may vary by route or booking
   - customers should confirm during booking
   - applicable charges may depend on the trip
7. Do not make legal, insurance, medical, or absolute safety guarantees.
8. Do not use fake urgency.
9. Keep every answer factual and practical.
10. Do not repeat the same information across multiple questions.

FAQ QUALITY RULES

1. Generate exactly {$faqCount} FAQs.
2. Questions must reflect genuine customer search intent.
3. Include the focus keyword naturally where useful, but do not force it into every question.
4. Cover a balanced mix of useful topics such as:
   - what the service is
   - who can use it
   - how booking works
   - route or location relevance
   - vehicle selection
   - pricing factors
   - pickup information
   - payment or confirmation
   - changes or cancellation
   - what customers should verify before booking
5. Only include a topic when it is relevant to the specified service type.
6. Answers should normally be 35 to 80 words.
7. Use short paragraphs and simple language.
8. Do not answer only with yes or no.
9. Do not use promotional filler.
10. Avoid duplicate questions with slightly different wording.
11. Questions should be suitable for:
   - website FAQ sections
   - search snippets
   - voice search
   - FAQ structured data

SEO RULES

1. Keep questions natural and conversational.
2. Include related search phrases naturally.
3. Do not keyword-stuff.
4. Use the brand name only where useful.
5. Make answers independently understandable.
6. Do not refer to another FAQ answer.
7. Avoid vague phrases such as "as mentioned above".

REFERENCE CONTENT

{$existingContentBlock}

{$additionalInstructionsBlock}

OUTPUT FORMAT

Return only valid JSON.

Do not use Markdown code fences.
Do not include explanations before or after the JSON.
Do not include comments.

Use exactly this JSON structure:

{
  "faqs": [
    {
      "question": "string",
      "answer": "string"
    }
  ]
}

OUTPUT VALIDATION RULES

1. Return exactly {$faqCount} FAQ objects.
2. Every object must contain both:
   - question
   - answer
3. Do not return empty strings.
4. Do not return HTML.
5. Do not number questions inside the question text.
6. Do not prefix questions with "Q:".
7. Do not prefix answers with "A:".
8. Ensure all JSON strings are correctly escaped.
PROMPT;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(array $context): string
    {
        return $this->build($context);
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
            1,
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

    private function cleanLongContent(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
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

        return Str::limit(
            $value,
            6000,
            '...',
        );
    }
}