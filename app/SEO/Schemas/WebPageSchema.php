<?php

declare(strict_types=1);

namespace App\SEO\Schemas;

final class WebPageSchema
{
    /**
     * Build a generic WebPage-compatible schema.
     *
     * Supported types include:
     *
     * WebPage
     * AboutPage
     * ContactPage
     * CollectionPage
     * FAQPage
     * ProfilePage
     * SearchResultsPage
     * CheckoutPage
     *
     * @param array<int, string>|null $keywords
     * @param array<string, mixed>|null $primaryImage
     * @param array<string, mixed>|null $breadcrumb
     * @param array<int, array<string, mixed>> $mainEntities
     *
     * @return array<string, mixed>
     */
    public function build(
        string $url,
        string $name,
        ?string $description = null,
        string $type = 'WebPage',
        ?string $webPageId = null,
        ?string $websiteId = null,
        ?string $organizationId = null,
        ?string $language = null,
        ?string $datePublished = null,
        ?string $dateModified = null,
        ?array $keywords = null,
        ?array $primaryImage = null,
        ?array $breadcrumb = null,
        array $mainEntities = [],
        ?string $aboutId = null,
        ?string $mentionsId = null,
        bool $isAccessibleForFree = true
    ): array {
        $url = $this->normaliseUrl($url);
        $name = trim($name);
        $description = $this->cleanText($description);

        $webPageId ??= $url . '#webpage';
        $language ??= str_replace('_', '-', app()->getLocale());

        $schema = [
            '@type' => $this->normaliseType($type),
            '@id' => $webPageId,
            'url' => $url,
            'name' => $name,
            'description' => $description,
            'inLanguage' => $language,
            'isAccessibleForFree' => $isAccessibleForFree,
            'isPartOf' => $websiteId !== null
                ? ['@id' => trim($websiteId)]
                : null,
            'about' => $aboutId !== null
                ? ['@id' => trim($aboutId)]
                : null,
            'mentions' => $mentionsId !== null
                ? ['@id' => trim($mentionsId)]
                : null,
            'publisher' => $organizationId !== null
                ? ['@id' => trim($organizationId)]
                : null,
            'datePublished' => $this->normaliseDate($datePublished),
            'dateModified' => $this->normaliseDate($dateModified),
            'keywords' => $this->normaliseKeywords($keywords),
            'breadcrumb' => $this->normaliseReference($breadcrumb),
            'primaryImageOfPage' => $this->normaliseReference($primaryImage),
            'image' => $this->normaliseImage($primaryImage),
            'mainEntity' => $this->normaliseEntities($mainEntities),
        ];

        return $this->cleanArray($schema);
    }

    /**
     * Build a basic WebPage schema.
     *
     * @return array<string, mixed>
     */
    public function basic(
        string $url,
        string $name,
        ?string $description = null,
        ?string $websiteId = null,
        ?string $organizationId = null,
        ?string $imageUrl = null
    ): array {
        $primaryImage = filled($imageUrl)
            ? $this->imageObject(
                imageUrl: (string) $imageUrl,
                pageUrl: $url,
                caption: $name
            )
            : null;

        return $this->build(
            url: $url,
            name: $name,
            description: $description,
            type: 'WebPage',
            websiteId: $websiteId,
            organizationId: $organizationId,
            primaryImage: $primaryImage,
            aboutId: $organizationId
        );
    }

    /**
     * Build an AboutPage schema.
     *
     * @return array<string, mixed>
     */
    public function aboutPage(
        string $url,
        string $name,
        ?string $description = null,
        ?string $websiteId = null,
        ?string $organizationId = null,
        ?array $breadcrumb = null,
        ?array $primaryImage = null
    ): array {
        return $this->build(
            url: $url,
            name: $name,
            description: $description,
            type: 'AboutPage',
            websiteId: $websiteId,
            organizationId: $organizationId,
            primaryImage: $primaryImage,
            breadcrumb: $breadcrumb,
            aboutId: $organizationId
        );
    }

    /**
     * Build a ContactPage schema.
     *
     * @return array<string, mixed>
     */
    public function contactPage(
        string $url,
        string $name,
        ?string $description = null,
        ?string $websiteId = null,
        ?string $organizationId = null,
        ?array $breadcrumb = null,
        ?array $primaryImage = null
    ): array {
        return $this->build(
            url: $url,
            name: $name,
            description: $description,
            type: 'ContactPage',
            websiteId: $websiteId,
            organizationId: $organizationId,
            primaryImage: $primaryImage,
            breadcrumb: $breadcrumb,
            aboutId: $organizationId
        );
    }

    /**
     * Build a CollectionPage schema.
     *
     * @param array<int, array<string, mixed>> $mainEntities
     *
     * @return array<string, mixed>
     */
    public function collectionPage(
        string $url,
        string $name,
        ?string $description = null,
        ?string $websiteId = null,
        ?string $organizationId = null,
        ?array $breadcrumb = null,
        array $mainEntities = [],
        ?array $primaryImage = null
    ): array {
        return $this->build(
            url: $url,
            name: $name,
            description: $description,
            type: 'CollectionPage',
            websiteId: $websiteId,
            organizationId: $organizationId,
            primaryImage: $primaryImage,
            breadcrumb: $breadcrumb,
            mainEntities: $mainEntities,
            aboutId: $organizationId
        );
    }

    /**
     * Build an ImageObject suitable for WebPage primaryImageOfPage.
     *
     * @return array<string, mixed>
     */
    public function imageObject(
        string $imageUrl,
        string $pageUrl,
        ?string $caption = null,
        ?int $width = null,
        ?int $height = null,
        ?string $contentType = null,
        ?string $creditText = null
    ): array {
        $imageUrl = trim($imageUrl);
        $pageUrl = $this->normaliseUrl($pageUrl);

        if ($imageUrl === '') {
            return [];
        }

        return $this->cleanArray([
            '@type' => 'ImageObject',
            '@id' => $pageUrl . '#primaryimage',
            'url' => $imageUrl,
            'contentUrl' => $imageUrl,
            'caption' => $this->cleanText($caption),
            'width' => $width,
            'height' => $height,
            'encodingFormat' => $this->cleanText($contentType),
            'creditText' => $this->cleanText($creditText),
        ]);
    }

    /**
     * Build only a WebPage @id reference.
     *
     * @return array{@id: string}
     */
    public function reference(
        string $url,
        ?string $webPageId = null
    ): array {
        return [
            '@id' => $webPageId ?? $this->id($url),
        ];
    }

    /**
     * Resolve the default WebPage identifier.
     */
    public function id(string $url): string
    {
        return $this->normaliseUrl($url) . '#webpage';
    }

    /**
     * Convert an ImageObject into an @id reference where possible.
     *
     * @param array<string, mixed>|null $image
     *
     * @return array<string, mixed>|null
     */
    private function normaliseReference(?array $image): ?array
    {
        if ($image === null || $image === []) {
            return null;
        }

        if (
            isset($image['@id'])
            && is_string($image['@id'])
            && trim($image['@id']) !== ''
        ) {
            return [
                '@id' => trim($image['@id']),
            ];
        }

        return $this->cleanArray($image);
    }

    /**
     * Return the full image object.
     *
     * @param array<string, mixed>|null $image
     *
     * @return array<string, mixed>|null
     */
    private function normaliseImage(?array $image): ?array
    {
        if ($image === null || $image === []) {
            return null;
        }

        return $this->cleanArray($image);
    }

    /**
     * @param array<int, array<string, mixed>> $entities
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function normaliseEntities(array $entities): ?array
    {
        $entities = collect($entities)
            ->filter(fn (mixed $entity): bool =>
                is_array($entity) && $entity !== []
            )
            ->map(fn (array $entity): array =>
                $this->cleanArray($entity)
            )
            ->filter()
            ->values()
            ->all();

        return $entities !== [] ? $entities : null;
    }

    /**
     * @param array<int, string>|null $keywords
     */
    private function normaliseKeywords(?array $keywords): ?string
    {
        if ($keywords === null) {
            return null;
        }

        $normalised = collect($keywords)
            ->map(fn (mixed $keyword): string =>
                trim((string) $keyword)
            )
            ->filter()
            ->unique(fn (string $keyword): string =>
                mb_strtolower($keyword)
            )
            ->values()
            ->implode(', ');

        return $normalised !== '' ? $normalised : null;
    }

    /**
     * Resolve a safe WebPage-compatible type.
     */
    private function normaliseType(string $type): string
    {
        $allowed = [
            'WebPage',
            'AboutPage',
            'ContactPage',
            'CollectionPage',
            'FAQPage',
            'ProfilePage',
            'SearchResultsPage',
            'CheckoutPage',
            'ItemPage',
            'MedicalWebPage',
            'QAPage',
            'RealEstateListing',
        ];

        $type = trim($type);

        return in_array($type, $allowed, true)
            ? $type
            : 'WebPage';
    }

    /**
     * Normalise a page URL without removing a root trailing slash.
     */
    private function normaliseUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return rtrim(url('/'), '/') . '/';
        }

        $parts = parse_url($url);

        if (
            is_array($parts)
            && isset($parts['scheme'], $parts['host'])
        ) {
            $path = $parts['path'] ?? '/';

            if ($path === '' || $path === '/') {
                return rtrim($url, '/') . '/';
            }
        }

        return rtrim($url, '/');
    }

    /**
     * Normalise a date to ISO 8601 where possible.
     */
    private function normaliseDate(?string $date): ?string
    {
        $date = $this->cleanText($date);

        if ($date === null) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($date)->toIso8601String();
        } catch (\Throwable) {
            return $date;
        }
    }

    /**
     * Clean a nullable text value.
     */
    private function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * Recursively remove null, blank and empty-array values.
     *
     * @param array<mixed> $values
     *
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

            if (
                $value === null
                || $value === ''
                || $value === []
            ) {
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