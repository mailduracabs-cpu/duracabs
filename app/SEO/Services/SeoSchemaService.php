<?php

declare(strict_types=1);

namespace App\SEO\Services;

use App\Models\Page;
use App\Models\Product;
use App\Models\WebsiteSetting;
use App\SEO\Schemas\BreadcrumbSchema;
use App\SEO\Schemas\FaqSchema;
use App\SEO\Schemas\LocalBusinessSchema;
use App\SEO\Schemas\OrganizationSchema;
use App\SEO\Schemas\WebPageSchema;
use App\SEO\Schemas\WebsiteSchema;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

final class SeoSchemaService
{
    public function __construct(
        private readonly OrganizationSchema $organizationSchema,
        private readonly WebsiteSchema $websiteSchema,
        private readonly LocalBusinessSchema $localBusinessSchema,
        private readonly WebPageSchema $webPageSchema,
        private readonly BreadcrumbSchema $breadcrumbSchema,
        private readonly FaqSchema $faqSchema
    ) {
    }

    /**
     * Build the global website schema graph.
     *
     * This graph contains:
     *
     * - Organization / TaxiService
     * - WebSite
     * - Optional homepage WebPage
     * - Optional SearchAction
     *
     * LocalBusiness is not added separately by default because the existing
     * WebsiteSetting::organizationSchema() may already use TaxiService or
     * LocalBusiness as its @type. Adding both with duplicate business data
     * could create unnecessary duplicate entities.
     *
     * @return array<string, mixed>
     */
    public function globalGraph(
        ?WebsiteSetting $settings = null,
        ?string $homeUrl = null,
        bool $includeHomepage = false,
        ?string $pageTitle = null,
        ?string $pageDescription = null,
        ?string $pageImage = null,
        ?string $searchTarget = null,
        string $searchQueryParameter = 'q'
    ): array {
        $settings ??= WebsiteSetting::current();
        $homeUrl = $this->normaliseHomeUrl($homeUrl);

        $organizationId = $homeUrl . '#organization';
        $websiteId = $homeUrl . '#website';

        $organizationBuilder = new OrganizationSchema($settings);
        $websiteBuilder = new WebsiteSchema($settings);

        $organization = $organizationBuilder->build(
            homeUrl: $homeUrl,
            organizationId: $organizationId
        );

        $website = $websiteBuilder->build(
            homeUrl: $homeUrl,
            websiteId: $websiteId,
            organizationId: $organizationId,
            searchTarget: $searchTarget,
            searchQueryParameter: $searchQueryParameter
        );

        $graph = SchemaGraph::make()
            ->add($organization)
            ->add($website);

        if ($includeHomepage) {
            $title = $this->firstFilledString([
                $pageTitle,
                $settings->default_meta_title,
                $settings->site_name,
                config('app.name', 'Dura Cabs'),
            ]);

            $description = $this->firstFilledString([
                $pageDescription,
                $settings->default_meta_description,
                $settings->business_description,
            ]);

            $imageObject = filled($pageImage)
                ? $this->webPageSchema->imageObject(
                    imageUrl: (string) $pageImage,
                    pageUrl: $homeUrl,
                    caption: $title
                )
                : null;

            if (is_array($imageObject) && $imageObject !== []) {
                $graph->add($imageObject);
            }

            $homepage = $this->webPageSchema->build(
                url: $homeUrl,
                name: $title,
                description: $description,
                type: 'WebPage',
                webPageId: $homeUrl . '#webpage',
                websiteId: $websiteId,
                organizationId: $organizationId,
                primaryImage: $imageObject,
                aboutId: $organizationId
            );

            $graph->add($homepage);
        }

        return $graph->toArray();
    }

    /**
     * Build an optional LocalBusiness graph.
     *
     * Use this only when a separate local branch/location entity is required.
     * Do not output it globally alongside the same TaxiService entity unless
     * both represent genuinely different entities.
     *
     * @return array<string, mixed>
     */
    public function localBusinessGraph(
        ?WebsiteSetting $settings = null,
        ?string $homeUrl = null,
        ?string $businessId = null
    ): array {
        $settings ??= WebsiteSetting::current();
        $homeUrl = $this->normaliseHomeUrl($homeUrl);

        $builder = new LocalBusinessSchema($settings);

        return SchemaGraph::make()
            ->add($builder->build(
                homeUrl: $homeUrl,
                businessId: $businessId
            ))
            ->toArray();
    }

    /**
     * Build a generic page-level schema graph.
     *
     * The global Organization and WebSite entities are referenced by @id,
     * but are not repeated inside this page graph.
     *
     * @param array<int, array<string, mixed>> $breadcrumbs
     * @param array<int, array<string, mixed>> $faqs
     * @param array<int, array<string, mixed>> $additionalSchemas
     * @param array<int, string>|null $keywords
     *
     * @return array<string, mixed>
     */
    public function pageGraph(
        string $url,
        string $title,
        ?string $description = null,
        string $pageType = 'WebPage',
        ?string $imageUrl = null,
        array $breadcrumbs = [],
        array $faqs = [],
        array $additionalSchemas = [],
        ?array $keywords = null,
        ?string $datePublished = null,
        ?string $dateModified = null,
        ?string $homeUrl = null
    ): array {
        $url = $this->normalisePageUrl($url);
        $homeUrl = $this->normaliseHomeUrl($homeUrl);

        $organizationId = $homeUrl . '#organization';
        $websiteId = $homeUrl . '#website';

        $graph = SchemaGraph::make();

        $breadcrumb = $breadcrumbs !== []
            ? $this->breadcrumbSchema->build(
                pageUrl: $url,
                items: $breadcrumbs
            )
            : [];

        if ($breadcrumb !== []) {
            $graph->add($breadcrumb);
        }

        $faq = $faqs !== []
            ? $this->faqSchema->build(
                pageUrl: $url,
                faqs: $faqs
            )
            : [];

        if ($faq !== []) {
            $graph->add($faq);
        }

        $imageObject = filled($imageUrl)
            ? $this->webPageSchema->imageObject(
                imageUrl: (string) $imageUrl,
                pageUrl: $url,
                caption: $title
            )
            : [];

        if ($imageObject !== []) {
            $graph->add($imageObject);
        }

        $mainEntities = [];

        if ($faq !== []) {
            $mainEntities[] = [
                '@id' => (string) $faq['@id'],
            ];
        }

        $webPage = $this->webPageSchema->build(
            url: $url,
            name: $title,
            description: $description,
            type: $pageType,
            websiteId: $websiteId,
            organizationId: $organizationId,
            datePublished: $datePublished,
            dateModified: $dateModified,
            keywords: $keywords,
            primaryImage: $imageObject !== [] ? $imageObject : null,
            breadcrumb: $breadcrumb !== [] ? $breadcrumb : null,
            mainEntities: $mainEntities,
            aboutId: $organizationId
        );

        $graph->add($webPage);
        $graph->addMany($this->normaliseSchemas($additionalSchemas));

        return $graph->toArray();
    }

    /**
     * Build a route-page schema graph.
     *
     * The supplied service schema is preserved and connected to the page.
     *
     * @param array<string, mixed> $serviceSchema
     * @param array<int, array<string, mixed>> $faqs
     * @param array<int, array<string, mixed>> $additionalSchemas
     *
     * @return array<string, mixed>
     */
    public function routeGraph(
        string $url,
        string $title,
        string $routeName,
        array $serviceSchema,
        ?string $description = null,
        ?string $imageUrl = null,
        array $faqs = [],
        array $additionalSchemas = [],
        ?string $routesUrl = null,
        ?string $homeUrl = null,
        ?string $datePublished = null,
        ?string $dateModified = null
    ): array {
        $url = $this->normalisePageUrl($url);
        $homeUrl = $this->normaliseHomeUrl($homeUrl);
        $routesUrl ??= url('/routes');

        $organizationId = $homeUrl . '#organization';
        $websiteId = $homeUrl . '#website';

        $graph = SchemaGraph::make();

        $breadcrumb = $this->breadcrumbSchema->routePage(
            pageUrl: $url,
            routeName: $routeName,
            routesUrl: $routesUrl,
            homeUrl: $homeUrl
        );

        $graph->add($breadcrumb);

        $faqSchema = $this->faqSchema->build(
            pageUrl: $url,
            faqs: $faqs
        );

        if ($faqSchema !== []) {
            $graph->add($faqSchema);
        }

        $imageObject = filled($imageUrl)
            ? $this->webPageSchema->imageObject(
                imageUrl: (string) $imageUrl,
                pageUrl: $url,
                caption: $title
            )
            : [];

        if ($imageObject !== []) {
            $graph->add($imageObject);
        }

        $serviceSchema = $this->preparePageEntity(
            schema: $serviceSchema,
            url: $url,
            defaultType: 'Service',
            defaultIdSuffix: '#service',
            organizationId: $organizationId,
            imageObject: $imageObject
        );

        if ($serviceSchema !== []) {
            $graph->add($serviceSchema);
        }

        $mainEntities = [];

        if ($serviceSchema !== []) {
            $mainEntities[] = [
                '@id' => (string) $serviceSchema['@id'],
            ];
        }

        if ($faqSchema !== []) {
            $mainEntities[] = [
                '@id' => (string) $faqSchema['@id'],
            ];
        }

        $webPage = $this->webPageSchema->build(
            url: $url,
            name: $title,
            description: $description,
            type: 'WebPage',
            websiteId: $websiteId,
            organizationId: $organizationId,
            datePublished: $datePublished,
            dateModified: $dateModified,
            primaryImage: $imageObject !== [] ? $imageObject : null,
            breadcrumb: $breadcrumb,
            mainEntities: $mainEntities,
            aboutId: $serviceSchema['@id'] ?? $organizationId
        );

        $graph->add($webPage);
        $graph->addMany($this->normaliseSchemas($additionalSchemas));

        return $graph->toArray();
    }

    /**
     * Build a product/item page graph.
     *
     * Manual Product JSON-LD still receives priority when enabled and valid.
     *
     * @param array<string, mixed> $automaticProductSchema
     * @param array<int, array<string, mixed>> $breadcrumbs
     * @param array<int, array<string, mixed>> $faqs
     * @param array<int, array<string, mixed>> $additionalSchemas
     *
     * @return array<string, mixed>
     */
    public function productGraph(
        Product $product,
        string $url,
        string $title,
        array $automaticProductSchema,
        ?string $description = null,
        ?string $imageUrl = null,
        array $breadcrumbs = [],
        array $faqs = [],
        array $additionalSchemas = [],
        ?string $homeUrl = null
    ): array {
        $url = $this->normalisePageUrl($url);
        $homeUrl = $this->normaliseHomeUrl($homeUrl);

        $organizationId = $homeUrl . '#organization';
        $websiteId = $homeUrl . '#website';

        $graph = SchemaGraph::make();

        $breadcrumb = $breadcrumbs !== []
            ? $this->breadcrumbSchema->build(
                pageUrl: $url,
                items: $breadcrumbs
            )
            : $this->breadcrumbSchema->productPage(
                pageUrl: $url,
                productName: $title,
                homeUrl: $homeUrl
            );

        if ($breadcrumb !== []) {
            $graph->add($breadcrumb);
        }

        $faqSchema = $this->faqSchema->build(
            pageUrl: $url,
            faqs: $faqs
        );

        if ($faqSchema !== []) {
            $graph->add($faqSchema);
        }

        $imageObject = filled($imageUrl)
            ? $this->webPageSchema->imageObject(
                imageUrl: (string) $imageUrl,
                pageUrl: $url,
                caption: $title
            )
            : [];

        if ($imageObject !== []) {
            $graph->add($imageObject);
        }

        $resolvedProductSchema = $this->resolveProductSchema(
            product: $product,
            automaticSchema: $automaticProductSchema
        );

        $resolvedProductSchemas = $this->normaliseSchemas(
            $resolvedProductSchema
        );

        foreach ($resolvedProductSchemas as $index => $schema) {
            $resolvedProductSchemas[$index] = $this->preparePageEntity(
                schema: $schema,
                url: $url,
                defaultType: 'Product',
                defaultIdSuffix: '#product',
                organizationId: $organizationId,
                imageObject: $imageObject
            );
        }

        $resolvedProductSchemas = $this->normaliseSchemas(
            $resolvedProductSchemas
        );

        $graph->addMany($resolvedProductSchemas);

        $mainEntities = [];

        foreach ($resolvedProductSchemas as $schema) {
            if (filled($schema['@id'] ?? null)) {
                $mainEntities[] = [
                    '@id' => (string) $schema['@id'],
                ];
            }
        }

        if ($faqSchema !== []) {
            $mainEntities[] = [
                '@id' => (string) $faqSchema['@id'],
            ];
        }

        $webPage = $this->webPageSchema->build(
            url: $url,
            name: $title,
            description: $description,
            type: 'ItemPage',
            websiteId: $websiteId,
            organizationId: $organizationId,
            primaryImage: $imageObject !== [] ? $imageObject : null,
            breadcrumb: $breadcrumb !== [] ? $breadcrumb : null,
            mainEntities: $mainEntities,
            aboutId: $resolvedProductSchemas[0]['@id'] ?? $organizationId
        );

        $graph->add($webPage);
        $graph->addMany($this->normaliseSchemas($additionalSchemas));

        return $graph->toArray();
    }

    /**
     * Resolve a Product page schema.
     *
     * Manual JSON-LD stored inside seo_analysis.schema.json takes priority
     * only when it is enabled and valid. Otherwise the automatic schema is used.
     *
     * Existing method signature is preserved for backward compatibility.
     *
     * @param array<string, mixed> $automaticSchema
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    public function resolveProductSchema(
        Product $product,
        array $automaticSchema
    ): array {
        $seoAnalysis = is_array($product->seo_analysis)
            ? $product->seo_analysis
            : [];

        $enabled = (bool) data_get(
            $seoAnalysis,
            'schema.enabled',
            false
        );

        $manualJson = trim((string) data_get(
            $seoAnalysis,
            'schema.json',
            ''
        ));

        if (! $enabled || $manualJson === '') {
            return $automaticSchema;
        }

        $decoded = $this->decodeJsonLd(
            json: $manualJson,
            context: [
                'model' => Product::class,
                'record_id' => $product->getKey(),
                'slug' => $product->slug,
            ]
        );

        return $decoded !== []
            ? $decoded
            : $automaticSchema;
    }

    /**
     * Return all valid Page schemas already prepared by the Page model.
     *
     * Existing method signature is preserved for backward compatibility.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pageSchemas(Page $page): array
    {
        try {
            $schemas = $page->all_json_ld;
        } catch (Throwable $exception) {
            Log::warning('Unable to resolve Page JSON-LD schemas.', [
                'model' => Page::class,
                'record_id' => $page->getKey(),
                'message' => $exception->getMessage(),
            ]);

            return [];
        }

        if (! is_array($schemas)) {
            return [];
        }

        return $this->normaliseSchemas($schemas);
    }

    /**
     * Convert page model schemas into one @graph structure.
     *
     * @return array<string, mixed>
     */
    public function pageModelGraph(Page $page): array
    {
        return SchemaGraph::make()
            ->addMany($this->pageSchemas($page))
            ->toArray();
    }

    /**
     * Decode a JSON-LD object or an array of JSON-LD objects.
     *
     * The outer @context + @graph format is also supported. When supplied,
     * only the schemas inside @graph are returned.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    public function decodeJsonLd(
        string $json,
        array $context = []
    ): array {
        $json = trim($json);

        if ($json === '') {
            return [];
        }

        try {
            $decoded = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (! is_array($decoded) || $decoded === []) {
                return [];
            }

            if (
                isset($decoded['@graph'])
                && is_array($decoded['@graph'])
            ) {
                return $this->normaliseSchemas(
                    $decoded['@graph']
                );
            }

            if ($this->isSchemaObject($decoded)) {
                return $this->cleanArray($decoded);
            }

            return $this->normaliseSchemas($decoded);
        } catch (JsonException $exception) {
            Log::warning('Invalid manual JSON-LD schema.', $context + [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Encode a graph or schema collection into safe JSON.
     */
    public function toJson(
        array $schemas,
        bool $pretty = false
    ): string {
        $graph = SchemaGraph::make();

        if (
            isset($schemas['@graph'])
            && is_array($schemas['@graph'])
        ) {
            $graph->addMany(
                $this->normaliseSchemas($schemas['@graph'])
            );
        } else {
            $graph->addMany(
                $this->normaliseSchemas($schemas)
            );
        }

        return $graph->toSafeJson($pretty);
    }

    /**
     * Render schemas as one JSON-LD script tag.
     */
    public function render(
        array $schemas,
        bool $pretty = false
    ): string {
        $graph = SchemaGraph::make();

        if (
            isset($schemas['@graph'])
            && is_array($schemas['@graph'])
        ) {
            $graph->addMany(
                $this->normaliseSchemas($schemas['@graph'])
            );
        } else {
            $graph->addMany(
                $this->normaliseSchemas($schemas)
            );
        }

        return $graph->render($pretty);
    }

    /**
     * Validate a graph or schema collection.
     *
     * @return array{
     *     valid: bool,
     *     errors: array<int, string>,
     *     warnings: array<int, string>,
     *     schema_count: int,
     *     schema_version: string
     * }
     */
    public function validate(array $schemas): array
    {
        $graph = SchemaGraph::make();

        if (
            isset($schemas['@graph'])
            && is_array($schemas['@graph'])
        ) {
            $graph->addMany(
                $this->normaliseSchemas($schemas['@graph'])
            );
        } else {
            $graph->addMany(
                $this->normaliseSchemas($schemas)
            );
        }

        return $graph->validate();
    }

    /**
     * Convert one schema object, a schema list or an @graph wrapper into a
     * clean list of schema objects.
     *
     * @param array<mixed> $schemas
     * @return array<int, array<string, mixed>>
     */
    public function normaliseSchemas(array $schemas): array
    {
        if ($schemas === []) {
            return [];
        }

        if (
            isset($schemas['@graph'])
            && is_array($schemas['@graph'])
        ) {
            $schemas = $schemas['@graph'];
        }

        if ($this->isSchemaObject($schemas)) {
            $schemas = [$schemas];
        }

        $normalised = [];

        foreach ($schemas as $schema) {
            if (! is_array($schema)) {
                continue;
            }

            if (! $this->isSchemaObject($schema)) {
                continue;
            }

            $schema = $this->cleanArray($schema);

            if ($schema === []) {
                continue;
            }

            $normalised[] = $schema;
        }

        return array_values($normalised);
    }

    /**
     * Prepare a page-specific schema entity.
     *
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $imageObject
     * @return array<string, mixed>
     */
    private function preparePageEntity(
        array $schema,
        string $url,
        string $defaultType,
        string $defaultIdSuffix,
        string $organizationId,
        array $imageObject = []
    ): array {
        if ($schema === []) {
            return [];
        }

        unset($schema['@context']);

        if (! filled($schema['@type'] ?? null)) {
            $schema['@type'] = $defaultType;
        }

        if (! filled($schema['@id'] ?? null)) {
            $schema['@id'] = $url . $defaultIdSuffix;
        }

        if (! filled($schema['url'] ?? null)) {
            $schema['url'] = $url;
        }

        if (! isset($schema['provider'])) {
            $schema['provider'] = [
                '@id' => $organizationId,
            ];
        }

        if (
            $imageObject !== []
            && ! isset($schema['image'])
        ) {
            $schema['image'] = [
                '@id' => (string) $imageObject['@id'],
            ];
        }

        return $this->cleanArray($schema);
    }

    /**
     * Determine whether an array represents one schema object.
     *
     * @param array<mixed> $value
     */
    private function isSchemaObject(array $value): bool
    {
        if (! array_key_exists('@type', $value)) {
            return false;
        }

        $type = $value['@type'];

        if (is_string($type)) {
            return trim($type) !== '';
        }

        if (is_array($type)) {
            foreach ($type as $item) {
                if (is_string($item) && trim($item) !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Resolve the first non-empty string.
     *
     * @param array<int, mixed> $values
     */
    private function firstFilledString(array $values): string
    {
        foreach ($values as $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);

            if ($value !== '') {
                return $value;
            }
        }

        return 'Dura Cabs';
    }

    /**
     * Resolve and normalise the website homepage URL.
     */
    private function normaliseHomeUrl(?string $homeUrl): string
    {
        $homeUrl = filled($homeUrl)
            ? trim((string) $homeUrl)
            : url('/');

        return rtrim($homeUrl, '/') . '/';
    }

    /**
     * Normalise an absolute or relative page URL.
     */
    private function normalisePageUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return $this->normaliseHomeUrl(null);
        }

        if (
            ! str_starts_with($url, 'http://')
            && ! str_starts_with($url, 'https://')
        ) {
            $url = url('/' . ltrim($url, '/'));
        }

        $parts = parse_url($url);
        $path = is_array($parts)
            ? (string) ($parts['path'] ?? '/')
            : '/';

        if ($path === '' || $path === '/') {
            return rtrim($url, '/') . '/';
        }

        return rtrim($url, '/');
    }

    /**
     * Recursively remove null, blank and empty-array values.
     *
     * Boolean false and numeric zero are intentionally preserved.
     *
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