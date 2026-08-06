<?php

declare(strict_types=1);

namespace App\SEO\Schemas;

use App\Models\WebsiteSetting;

final class OrganizationSchema
{
    public function __construct(
        private readonly WebsiteSetting $settings
    ) {
    }

    /**
     * Build the main Organization / TaxiService schema.
     *
     * @return array<string, mixed>
     */
    public function build(
        string $homeUrl,
        ?string $organizationId = null
    ): array {
        $homeUrl = rtrim($homeUrl, '/') . '/';

        $organizationId ??= $homeUrl . '#organization';

        $schema = $this->settings->organizationSchema(
            $homeUrl,
            $organizationId
        );

        if (! isset($schema['@type'])) {
            $schema['@type'] = 'Organization';
        }

        $schema['@id'] = $organizationId;
        $schema['url'] = $homeUrl;

        return $this->clean($schema);
    }

    /**
     * Build only an Organization reference.
     *
     * @return array{@id: string}
     */
    public function reference(
        string $homeUrl,
        ?string $organizationId = null
    ): array {
        $homeUrl = rtrim($homeUrl, '/') . '/';

        return [
            '@id' => $organizationId ?? $homeUrl . '#organization',
        ];
    }

    /**
     * Resolve the default organization identifier.
     */
    public function id(string $homeUrl): string
    {
        return rtrim($homeUrl, '/') . '/#organization';
    }

    /**
     * Recursively remove null, empty-string and empty-array values.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function clean(array $schema): array
    {
        $cleaned = [];

        foreach ($schema as $key => $value) {
            if (is_array($value)) {
                $value = $this->cleanArray($value);
            } elseif (is_string($value)) {
                $value = trim($value);
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $cleaned[$key] = $value;
        }

        return $cleaned;
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