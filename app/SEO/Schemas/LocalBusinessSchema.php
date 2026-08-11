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

        /*
         * Reuse the safe Organization identity fields (name, address,
         * contact details, social profiles), then add place/business-only
         * properties to a separate LocalBusiness node.
         */
        $schema = $this->settings->organizationSchema(
            $homeUrl,
            $businessId
        );

        $schema['@type'] = 'LocalBusiness';
        $schema['@id'] = $businessId;
        $schema['url'] = $homeUrl;

        if (filled($this->settings->price_range)) {
            $schema['priceRange'] = trim(
                (string) $this->settings->price_range
            );
        }

        $geo = $this->settings->geoCoordinatesSchema();

        if (is_array($geo) && $geo !== []) {
            $schema['geo'] = $geo;
        }

        if (filled($this->settings->google_map_url)) {
            $schema['hasMap'] = trim(
                (string) $this->settings->google_map_url
            );
        }

        $openingHours = $this->settings->openingHoursSchema();

        if (is_array($openingHours) && $openingHours !== []) {
            $schema['openingHoursSpecification'] = $openingHours;
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