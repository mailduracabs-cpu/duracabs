<?php

declare(strict_types=1);

namespace App\SEO\Schemas;

use App\Models\WebsiteSetting;

final class WebsiteSchema
{
    public function __construct(
        private readonly WebsiteSetting $settings
    ) {
    }

    /**
     * Build the global WebSite schema.
     *
     * @return array<string, mixed>
     */
    public function build(
        string $homeUrl,
        ?string $websiteId = null,
        ?string $organizationId = null,
        ?string $searchTarget = null,
        string $searchQueryParameter = 'q'
    ): array {
        $homeUrl = rtrim($homeUrl, '/') . '/';

        $websiteId ??= $homeUrl . '#website';
        $organizationId ??= $homeUrl . '#organization';

        $schema = $this->settings->websiteSchema(
            $homeUrl,
            $websiteId,
            $organizationId
        );

        $schema['@type'] = 'WebSite';
        $schema['@id'] = $websiteId;
        $schema['url'] = $homeUrl;
        $schema['publisher'] = [
            '@id' => $organizationId,
        ];

        if ($searchTarget !== null && trim($searchTarget) !== '') {
            $schema['potentialAction'] = $this->searchAction(
                target: $searchTarget,
                queryParameter: $searchQueryParameter
            );
        }

        return $this->clean($schema);
    }

    /**
     * Build a WebSite reference.
     *
     * @return array{@id: string}
     */
    public function reference(
        string $homeUrl,
        ?string $websiteId = null
    ): array {
        return [
            '@id' => $websiteId ?? $this->id($homeUrl),
        ];
    }

    /**
     * Resolve the default WebSite schema identifier.
     */
    public function id(string $homeUrl): string
    {
        return rtrim($homeUrl, '/') . '/#website';
    }

    /**
     * Build an optional SearchAction.
     *
     * The target may be supplied in either format:
     *
     * https://example.com/search?q={search_term_string}
     *
     * or:
     *
     * https://example.com/search
     *
     * In the second case, the query placeholder is added automatically.
     *
     * @return array<string, mixed>
     */
    public function searchAction(
        string $target,
        string $queryParameter = 'q'
    ): array {
        $target = trim($target);
        $queryParameter = trim($queryParameter);

        if ($queryParameter === '') {
            $queryParameter = 'q';
        }

        if (! str_contains($target, '{search_term_string}')) {
            $separator = str_contains($target, '?') ? '&' : '?';

            $target .= $separator
                . rawurlencode($queryParameter)
                . '={search_term_string}';
        }

        return [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $target,
            ],
            'query-input' => sprintf(
                'required name=%s',
                $queryParameter
            ),
        ];
    }

    /**
     * Recursively remove null, blank and empty-array values.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function clean(array $schema): array
    {
        return $this->cleanArray($schema);
    }

    /**
     * @param array<mixed> $values
     * @return array<mixed>
     */
    private function cleanArray(array $values): array
    {
        $isList = array_is_list($values);
        $cleaned = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $value = $this->cleanArray($value);
            } elseif (is_string($value)) {
                $value = trim($value);
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            if ($isList) {
                $cleaned[] = $value;
            } else {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }
}