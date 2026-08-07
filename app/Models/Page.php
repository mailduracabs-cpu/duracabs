<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand_id',
        'slug',
        'description',
        'content_type',
        'self_drive_settings',
        'excerpt',
        'author_name',
        'reading_time',

        'meta_title',
        'meta_description',
        'meta_keywords',
        'focus_keyword',
        'secondary_keywords',
        'canonical_url',
        'robots',
        'seo_score',
        'readability_score',

        'image',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',

        'schema',
        'schema_type',
        'faq_schema',
        'breadcrumb_schema',
        'custom_schema',

        'links',
        'link_products',
        'cta',
        'internal_links',
        'related_pages',
        'related_products',
        'related_blogs',

        'published_at',
        'updated_by',
    ];

    protected $casts = [
        'self_drive_settings' => 'array',
        'links' => 'array',
        'link_products' => 'array',

        'secondary_keywords' => 'array',
        'schema' => 'array',
        'faq_schema' => 'array',
        'breadcrumb_schema' => 'array',
        'custom_schema' => 'array',

        'cta' => 'array',
        'internal_links' => 'array',
        'related_pages' => 'array',
        'related_products' => 'array',
        'related_blogs' => 'array',

        'seo_score' => 'integer',
        'readability_score' => 'integer',
        'reading_time' => 'integer',

        'published_at' => 'datetime',
    ];

    protected $attributes = [
        'content_type' => 'page',
        'self_drive_settings' => '[]',
        'robots' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        'schema_type' => 'WebPage',
        'seo_score' => 0,
        'readability_score' => 0,
    ];

    protected $appends = [
        'public_path',
        'public_url',
        'resolved_canonical_url',
    ];

    protected static function booted(): void
    {
        static::saving(function (Page $page): void {
            if (blank($page->slug) && filled($page->name)) {
                $page->slug = Str::slug((string) $page->name);
            }

            if (filled($page->description)) {
                $plainContent = trim(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        strip_tags((string) $page->description),
                    ) ?? '',
                );

                $wordCount = Str::wordCount($plainContent);

                $page->reading_time = max(
                    1,
                    (int) ceil($wordCount / 200),
                );

                if (blank($page->excerpt)) {
                    $page->excerpt = Str::limit(
                        $plainContent,
                        180,
                        '…',
                    );
                }
            }

            if (blank($page->meta_title)) {
                $page->meta_title = Str::limit(
                    (string) $page->name,
                    60,
                    '',
                );
            }

            if (blank($page->meta_description)) {
                $page->meta_description = Str::limit(
                    (string) (
                        $page->excerpt
                        ?: strip_tags((string) $page->description)
                    ),
                    160,
                    '',
                );
            }

            if (blank($page->focus_keyword)) {
                $page->focus_keyword = $page->name;
            }

            if (blank($page->canonical_url) && filled($page->slug)) {
                $page->canonical_url = $page->public_url;
            }
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by',
        );
    }

    public function getPublicPathAttribute(): string
    {
        $slug = ltrim((string) $this->slug, '/');

        return match ((string) $this->content_type) {
            'blog' => '/blog/' . $slug,
            'tour_package' => '/tour/' . $slug,
            default => '/pages/' . $slug,
        };
    }

    public function getPublicUrlAttribute(): string
    {
        return self::productionBaseUrl() . $this->public_path;
    }

    public static function productionBaseUrl(): string
    {
        return rtrim(
            (string) config(
                'services.search_console.property',
                config('app.url')
            ),
            '/'
        );
    }

    public function getSeoTitleAttribute(): string
    {
        return trim(
            (string) (
                $this->meta_title
                ?: $this->name
                ?: config('app.name')
            ),
        );
    }

    public function getSeoDescriptionAttribute(): string
    {
        $description = $this->meta_description
            ?: $this->excerpt
            ?: strip_tags((string) $this->description);

        return Str::limit(
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    (string) $description,
                ) ?? '',
            ),
            160,
            '',
        );
    }

    public function getResolvedCanonicalUrlAttribute(): string
    {
        if (filled($this->canonical_url)) {
            return rtrim((string) $this->canonical_url, '/');
        }

        return rtrim($this->public_url, '/');
    }

    public function getResolvedImageUrlAttribute(): ?string
    {
        return $this->resolveMediaUrl($this->image);
    }

    public function getResolvedOgTitleAttribute(): string
    {
        return trim(
            (string) (
                $this->og_title
                ?: $this->seo_title
            ),
        );
    }

    public function getResolvedOgDescriptionAttribute(): string
    {
        return trim(
            (string) (
                $this->og_description
                ?: $this->seo_description
            ),
        );
    }

    public function getResolvedOgImageAttribute(): ?string
    {
        return $this->resolveMediaUrl(
            $this->og_image
                ?: $this->image,
        );
    }

    public function getResolvedTwitterTitleAttribute(): string
    {
        return trim(
            (string) (
                $this->twitter_title
                ?: $this->og_title
                ?: $this->seo_title
            ),
        );
    }

    public function getResolvedTwitterDescriptionAttribute(): string
    {
        return trim(
            (string) (
                $this->twitter_description
                ?: $this->og_description
                ?: $this->seo_description
            ),
        );
    }

    public function getResolvedTwitterImageAttribute(): ?string
    {
        return $this->resolveMediaUrl(
            $this->twitter_image
                ?: $this->og_image
                ?: $this->image,
        );
    }

    /**
     * @return array<int, array{question:string, answer:string}>
     */
    public function getFaqItemsAttribute(): array
    {
        $faqs = $this->faq_schema;

        if (! is_array($faqs)) {
            return [];
        }

        return collect($faqs)
            ->filter(
                fn (mixed $faq): bool => is_array($faq)
                    && filled($faq['question'] ?? null)
                    && filled($faq['answer'] ?? null),
            )
            ->map(
                fn (array $faq): array => [
                    'question' => trim((string) $faq['question']),
                    'answer' => trim((string) $faq['answer']),
                ],
            )
            ->values()
            ->all();
    }

    /**
     * One FAQPage node without an outer @context wrapper.
     *
     * @return array<string, mixed>|null
     */
    public function getFaqJsonLdAttribute(): ?array
    {
        if ($this->faq_items === []) {
            return null;
        }

        $canonicalUrl = $this->resolved_canonical_url;

        return [
            '@type' => 'FAQPage',
            '@id' => $canonicalUrl . '#faq',
            'url' => $canonicalUrl . '#frequently-asked-questions',
            'mainEntity' => collect($this->faq_items)
                ->map(
                    fn (array $faq): array => [
                        '@type' => 'Question',
                        'name' => $faq['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => strip_tags($faq['answer']),
                        ],
                    ],
                )
                ->values()
                ->all(),
        ];
    }

    /**
     * One BreadcrumbList node without an outer @context wrapper.
     *
     * @return array<string, mixed>|null
     */
    public function getBreadcrumbJsonLdAttribute(): ?array
    {
        $canonicalUrl = $this->resolved_canonical_url;
        $breadcrumbs = $this->breadcrumb_schema;

        if (! is_array($breadcrumbs) || $breadcrumbs === []) {
            return [
                '@type' => 'BreadcrumbList',
                '@id' => $canonicalUrl . '#breadcrumb',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => rtrim(url('/'), '/') . '/',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $this->name,
                        'item' => $canonicalUrl,
                    ],
                ],
            ];
        }

        $items = collect($breadcrumbs)
            ->filter(
                fn (mixed $item): bool => is_array($item)
                    && filled($item['name'] ?? null)
                    && filled($item['url'] ?? $item['item'] ?? null),
            )
            ->values()
            ->map(
                fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => trim((string) $item['name']),
                    'item' => trim((string) ($item['url'] ?? $item['item'])),
                ],
            )
            ->all();

        if ($items === []) {
            return null;
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id' => $canonicalUrl . '#breadcrumb',
            'itemListElement' => $items,
        ];
    }

    /**
     * Primary WebPage/Article node without an outer @context wrapper.
     *
     * @return array<string, mixed>
     */
    public function getPrimaryJsonLdAttribute(): array
    {
        $schemaType = $this->resolvedSchemaType();
        $canonicalUrl = $this->resolved_canonical_url;
        $homeUrl = rtrim(url('/'), '/') . '/';
        $organizationId = $homeUrl . '#organization';
        $websiteId = $homeUrl . '#website';
        $pageId = $canonicalUrl . '#webpage';
        $imageId = $canonicalUrl . '#primaryimage';

        $schema = [
            '@type' => $schemaType,
            '@id' => $pageId,
            'url' => $canonicalUrl,
            'name' => $this->seo_title,
            'description' => $this->seo_description,
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
            'isAccessibleForFree' => true,
            'isPartOf' => [
                '@id' => $websiteId,
            ],
            'mainEntityOfPage' => [
                '@id' => $pageId,
            ],
            'publisher' => [
                '@id' => $organizationId,
            ],
            'breadcrumb' => [
                '@id' => $canonicalUrl . '#breadcrumb',
            ],
            'about' => filled($this->brand?->name)
                ? [
                    '@type' => 'Place',
                    'name' => $this->brand->name,
                ]
                : [
                    '@type' => 'Thing',
                    'name' => $this->name,
                ],
        ];

        if ($this->faq_items !== []) {
            $schema['mainEntity'] = [
                '@id' => $canonicalUrl . '#faq',
            ];
        }

        if (in_array($schemaType, ['Article', 'BlogPosting', 'NewsArticle'], true)) {
            $schema['headline'] = $this->seo_title;
        }

        if (filled($this->resolved_image_url)) {
            $schema['image'] = [
                '@id' => $imageId,
            ];

            $schema['primaryImageOfPage'] = [
                '@id' => $imageId,
            ];
        }

        if (filled($this->author_name)) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $this->author_name,
            ];
        } elseif (in_array($schemaType, ['Article', 'BlogPosting', 'NewsArticle'], true)) {
            $schema['author'] = [
                '@id' => $organizationId,
            ];
        }

        if ($this->published_at !== null) {
            $schema['datePublished'] = $this->published_at->toIso8601String();
        }

        if ($this->updated_at !== null) {
            $schema['dateModified'] = $this->updated_at->toIso8601String();
        }

        return $this->cleanSchema($schema);
    }

    /**
     * Standalone ImageObject node referenced by the primary page node.
     *
     * @return array<string, mixed>|null
     */
    public function getImageJsonLdAttribute(): ?array
    {
        if (blank($this->resolved_image_url)) {
            return null;
        }

        $canonicalUrl = $this->resolved_canonical_url;

        return [
            '@type' => 'ImageObject',
            '@id' => $canonicalUrl . '#primaryimage',
            'url' => $this->resolved_image_url,
            'contentUrl' => $this->resolved_image_url,
            'caption' => $this->seo_title,
            'representativeOfPage' => true,
        ];
    }

    /**
     * Clean schema nodes ready for SchemaGraph/SeoSchemaService.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllJsonLdAttribute(): array
    {
        $schemas = [
            $this->primary_json_ld,
            $this->image_json_ld,
            $this->breadcrumb_json_ld,
            $this->faq_json_ld,
        ];

        foreach ($this->manualSchemaNodes() as $schema) {
            $schemas[] = $schema;
        }

        $cleaned = [];
        $seenIds = [];

        foreach ($schemas as $schema) {
            if (! is_array($schema) || $schema === []) {
                continue;
            }

            unset($schema['@context']);

            $schema = $this->cleanSchema($schema);

            if (! filled($schema['@type'] ?? null)) {
                continue;
            }

            $schemaId = is_string($schema['@id'] ?? null)
                ? trim((string) $schema['@id'])
                : '';

            if ($schemaId !== '') {
                if (isset($seenIds[$schemaId])) {
                    continue;
                }

                $seenIds[$schemaId] = true;
            }

            $cleaned[] = $schema;
        }

        return array_values($cleaned);
    }

    public function isIndexable(): bool
    {
        return ! Str::contains(
            strtolower((string) $this->robots),
            'noindex',
        );
    }

    public function isPublished(): bool
    {
        return $this->published_at === null
            || $this->published_at->isPast();
    }

    private function resolvedSchemaType(): string
    {
        $type = trim((string) $this->schema_type);

        if ($type !== '') {
            return $type;
        }

        return match ((string) $this->content_type) {
            'blog' => 'BlogPosting',
            default => 'WebPage',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function manualSchemaNodes(): array
    {
        $nodes = [];

        foreach ([$this->schema, $this->custom_schema] as $value) {
            foreach ($this->normaliseSchemaValue($value) as $schema) {
                $nodes[] = $schema;
            }
        }

        return $nodes;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normaliseSchemaValue(mixed $value): array
    {
        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return [];
            }

            try {
                $value = json_decode(
                    $value,
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (JsonException) {
                return [];
            }
        }

        if (! is_array($value) || $value === []) {
            return [];
        }

        if (isset($value['@graph']) && is_array($value['@graph'])) {
            $value = $value['@graph'];
        }

        if (array_key_exists('@type', $value)) {
            return [$value];
        }

        if (! array_is_list($value)) {
            return [];
        }

        return array_values(
            array_filter(
                $value,
                fn (mixed $schema): bool => is_array($schema)
                    && filled($schema['@type'] ?? null),
            ),
        );
    }

    /**
     * @param array<mixed> $values
     * @return array<mixed>
     */
    private function cleanSchema(array $values): array
    {
        $isList = array_is_list($values);
        $cleaned = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $value = $this->cleanSchema($value);
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

    private function resolveMediaUrl(mixed $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = trim((string) $path);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, ['/storage/', 'storage/'])) {
            return url('/' . ltrim($path, '/'));
        }

        return Storage::disk('public')->url(
            ltrim($path, '/'),
        );
    }
}