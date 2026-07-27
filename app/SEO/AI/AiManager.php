<?php

declare(strict_types=1);

namespace App\SEO\AI;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use RuntimeException;
use Throwable;

final class AiManager
{
    public function __construct(
        private readonly Application $app,
    ) {
    }

    /**
     * Selected/default provider se content generate karta hai.
     *
     * @param array<string, mixed> $options
     */
    public function generate(
        string $prompt,
        ?string $provider = null,
        array $options = [],
    ): string {
        $driver = $this->provider($provider);

        if (! $driver->isConfigured()) {
            throw new RuntimeException(
                sprintf(
                    "AI provider [%s] configured nahi hai.",
                    $driver->name(),
                ),
            );
        }

        return $driver->generate(
            prompt: $prompt,
            options: $options,
        );
    }

    /**
     * Provider instance resolve karta hai.
     */
    public function provider(
        ?string $name = null,
    ): AiProviderInterface {
        $name = $this->normalizeProviderName(
            $name ?: $this->getDefaultProvider(),
        );

        $providerConfig = config(
            "seo-ai.providers.{$name}",
        );

        if (! is_array($providerConfig)) {
            throw new RuntimeException(
                "AI provider [{$name}] config me available nahi hai.",
            );
        }

        $driverClass = $providerConfig['driver'] ?? null;

        if (
            ! is_string($driverClass)
            || trim($driverClass) === ''
        ) {
            throw new RuntimeException(
                "AI provider [{$name}] ka driver configured nahi hai.",
            );
        }

        if (! class_exists($driverClass)) {
            throw new RuntimeException(
                "AI provider driver class [{$driverClass}] nahi mili.",
            );
        }

        try {
            $driver = $this->app->make($driverClass);
        } catch (BindingResolutionException $exception) {
            throw new RuntimeException(
                "AI provider [{$name}] resolve nahi ho saka: "
                . $exception->getMessage(),
                previous: $exception,
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "AI provider [{$name}] load nahi ho saka: "
                . $exception->getMessage(),
                previous: $exception,
            );
        }

        if (! $driver instanceof AiProviderInterface) {
            throw new RuntimeException(
                "AI provider driver [{$driverClass}] ko "
                . AiProviderInterface::class
                . ' implement karna chahiye.',
            );
        }

        return $driver;
    }

    /**
     * Configured default provider ka naam.
     */
    public function getDefaultProvider(): string
    {
        $provider = config(
            'seo-ai.default',
            'gemini',
        );

        if (! is_scalar($provider)) {
            return 'gemini';
        }

        $provider = $this->normalizeProviderName(
            (string) $provider,
        );

        return $provider !== ''
            ? $provider
            : 'gemini';
    }

    /**
     * Default provider configured hai ya nahi.
     */
    public function isConfigured(
        ?string $provider = null,
    ): bool {
        try {
            return $this->provider($provider)->isConfigured();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Saare declared providers return karta hai.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getProviders(): array
    {
        $providers = config(
            'seo-ai.providers',
            [],
        );

        return is_array($providers)
            ? $providers
            : [];
    }

    /**
     * Provider dropdown ke liye options.
     *
     * @return array<string, string>
     */
    public function getProviderOptions(): array
    {
        $options = [];

        foreach ($this->getProviders() as $name => $config) {
            if (! is_string($name)) {
                continue;
            }

            $normalizedName = $this->normalizeProviderName(
                $name,
            );

            if ($normalizedName === '') {
                continue;
            }

            $options[$normalizedName] = match ($normalizedName) {
                'gemini' => 'Google Gemini',
                'openai' => 'OpenAI',
                default => ucwords(
                    str_replace(
                        ['-', '_'],
                        ' ',
                        $normalizedName,
                    ),
                ),
            };
        }

        return $options;
    }

    /**
     * Sirf configured providers return karta hai.
     *
     * @return array<string, string>
     */
    public function getConfiguredProviderOptions(): array
    {
        $options = [];

        foreach ($this->getProviderOptions() as $name => $label) {
            if ($this->isConfigured($name)) {
                $options[$name] = $label;
            }
        }

        return $options;
    }

    /**
     * Default provider ka object.
     */
    public function defaultProvider(): AiProviderInterface
    {
        return $this->provider(
            $this->getDefaultProvider(),
        );
    }

    /**
     * Default provider identifier.
     */
    public function defaultProviderName(): string
    {
        return $this->getDefaultProvider();
    }

    /**
     * Provider exists karta hai ya nahi.
     */
    public function hasProvider(string $name): bool
    {
        $name = $this->normalizeProviderName($name);

        if ($name === '') {
            return false;
        }

        return array_key_exists(
            $name,
            $this->getProviders(),
        );
    }

    /**
     * Default provider fail hone par configured fallback providers try karta hai.
     *
     * @param array<string, mixed> $options
     */
    public function generateWithFallback(
        string $prompt,
        ?string $provider = null,
        array $options = [],
    ): string {
        $primaryProvider = $this->normalizeProviderName(
            $provider ?: $this->getDefaultProvider(),
        );

        $providersToTry = [$primaryProvider];

        $fallbackProviders = config(
            'seo-ai.fallback_providers',
            [],
        );

        if (is_array($fallbackProviders)) {
            foreach ($fallbackProviders as $fallbackProvider) {
                if (! is_scalar($fallbackProvider)) {
                    continue;
                }

                $fallbackProvider = $this->normalizeProviderName(
                    (string) $fallbackProvider,
                );

                if (
                    $fallbackProvider !== ''
                    && ! in_array(
                        $fallbackProvider,
                        $providersToTry,
                        true,
                    )
                ) {
                    $providersToTry[] = $fallbackProvider;
                }
            }
        }

        $errors = [];

        foreach ($providersToTry as $providerName) {
            try {
                return $this->generate(
                    prompt: $prompt,
                    provider: $providerName,
                    options: $options,
                );
            } catch (Throwable $exception) {
                report($exception);

                $errors[] = sprintf(
                    '%s: %s',
                    $providerName,
                    $exception->getMessage(),
                );
            }
        }

        throw new RuntimeException(
            'Koi bhi AI provider request complete nahi kar saka. '
            . implode(' | ', $errors),
        );
    }

    private function normalizeProviderName(
        string $name,
    ): string {
        return strtolower(
            trim($name),
        );
    }
}