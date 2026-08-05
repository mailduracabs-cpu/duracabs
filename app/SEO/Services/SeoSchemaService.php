<?php

declare(strict_types=1);

namespace App\SEO\Services;

use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use JsonException;

final class SeoSchemaService
{
    /**
     * Resolve a Product page schema.
     *
     * Manual JSON-LD stored inside seo_analysis.schema.json takes priority
     * only when it is enabled and valid. Otherwise the automatic schema is used.
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
     * @return array<int, array<string, mixed>>
     */
    public function pageSchemas(Page $page): array
    {
        $schemas = $page->all_json_ld;

        if (! is_array($schemas)) {
            return [];
        }

        return array_values(array_filter(
            $schemas,
            static fn (mixed $schema): bool =>
                is_array($schema) && $schema !== []
        ));
    }

    /**
     * Decode a JSON-LD object or an array of JSON-LD objects.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    public function decodeJsonLd(
        string $json,
        array $context = []
    ): array {
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

            return $decoded;
        } catch (JsonException $exception) {
            Log::warning('Invalid manual JSON-LD schema.', $context + [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}