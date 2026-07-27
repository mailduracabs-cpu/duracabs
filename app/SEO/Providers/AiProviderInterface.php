<?php

declare(strict_types=1);

namespace App\SEO\AI;

interface AiProviderInterface
{
    /**
     * AI provider se text generate karta hai.
     *
     * @param array<string, mixed> $options
     */
    public function generate(
        string $prompt,
        array $options = [],
    ): string;

    /**
     * Provider API use karne ke liye configured hai ya nahi.
     */
    public function isConfigured(): bool;

    /**
     * Provider ka unique identifier.
     *
     * Examples:
     * - gemini
     * - openai
     */
    public function name(): string;
}