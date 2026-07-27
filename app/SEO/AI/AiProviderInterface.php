<?php

declare(strict_types=1);

namespace App\SEO\AI;

interface AiProviderInterface
{
    /**
     * Generate SEO content from the supplied prompt.
     *
     * @param array<string, mixed> $options
     */
    public function generate(string $prompt, array $options = []): string;

    /**
     * Provider name.
     */
    public function name(): string;

    /**
     * Whether the provider is configured correctly.
     */
    public function isConfigured(): bool;
}