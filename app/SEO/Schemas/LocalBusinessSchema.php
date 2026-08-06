<?php

declare(strict_types=1);

namespace App\SEO\Schemas;

use App\Models\WebsiteSetting;

final class LocalBusinessSchema
{
    public function __construct(
        private readonly WebsiteSetting $settings
    ) {
    }

    /**
     * Build LocalBusiness / TaxiService schema.
     *
     * @return array<string,mixed>
     */
    public function build(
        string $homeUrl,
        ?string $businessId = null
    ): array {
        $homeUrl = rtrim($homeUrl, '/') . '/';

        $businessId ??= $homeUrl . '#local-business';

        $schema = $this->settings->organizationSchema(
            $homeUrl,
            $businessId
        );

        $schema['@id'] = $businessId;

        /*
        |--------------------------------------------------------------------------
        | Force proper schema type
        |--------------------------------------------------------------------------
        */

        if (
            ! isset($schema['@type'])
            || empty($schema['@type'])
        ) {
            $schema['@type'] = [
                'LocalBusiness',
                'TaxiService',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Ensure url exists
        |--------------------------------------------------------------------------
        */

        $schema['url'] = $homeUrl;

        /*
        |--------------------------------------------------------------------------
        | Area Served
        |--------------------------------------------------------------------------
        */

        if (! isset($schema['areaServed'])) {

            $schema['areaServed'] = [
                [
                    '@type' => 'Country',
                    'name' => 'India',
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Available Languages
        |--------------------------------------------------------------------------
        */

        if (! isset($schema['availableLanguage'])) {

            $schema['availableLanguage'] = [
                'en',
                'hi',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Price Range
        |--------------------------------------------------------------------------
        */

        if (
            empty($schema['priceRange'])
        ) {
            $schema['priceRange'] = '₹₹';
        }

        return $this->clean($schema);
    }

    /**
     * Return only @id reference.
     *
     * @return array<string,string>
     */
    public function reference(
        string $homeUrl
    ): array {

        return [
            '@id' => $this->id($homeUrl),
        ];
    }

    /**
     * Resolve LocalBusiness id.
     */
    public function id(
        string $homeUrl
    ): string {

        return rtrim($homeUrl, '/')
            . '/#local-business';
    }

    /**
     * Remove null values recursively.
     *
     * @param array<string,mixed> $schema
     *
     * @return array<string,mixed>
     */
    private function clean(
        array $schema
    ): array {

        return $this->cleanArray($schema);
    }

    /**
     * @param array<mixed> $items
     *
     * @return array<mixed>
     */
    private function cleanArray(
        array $items
    ): array {

        $list = array_is_list($items);

        $clean = [];

        foreach ($items as $key => $value) {

            if (is_array($value)) {
                $value = $this->cleanArray($value);
            }

            if (is_string($value)) {
                $value = trim($value);
            }

            if (
                $value === null
                || $value === ''
                || $value === []
            ) {
                continue;
            }

            if ($list) {
                $clean[] = $value;
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}