<?php

declare(strict_types=1);

namespace App\Forms\Components;

use App\Models\Page;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

final class ContentWriter extends Section
{
    protected string $contentField = 'description';

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->heading('Dura Content Writer')
            ->description(
                'Content, SEO, AI suggestions, FAQ, social metadata aur schema ko ek hi reusable editor se manage karein.',
            )
            ->icon('heroicon-o-pencil-square')
            ->collapsible()
            ->schema([
                Tabs::make('content_writer_tabs')
                    ->persistTabInQueryString('content-writer-tab')
                    ->columnSpanFull()
                    ->tabs([
                        $this->contentTab(),
                        $this->seoTab(),
                        $this->aiWriterTab(),
                        $this->faqTab(),
                        $this->socialTab(),
                        $this->schemaTab(),
                        $this->linksTab(),
                        $this->previewTab(),
                    ]),
            ]);
    }

    public function contentField(string $field): static
    {
        $this->contentField = $field;

        return $this;
    }

    private function contentTab(): Tab
    {
        return Tab::make('Content')
            ->icon('heroicon-o-document-text')
            ->schema([
                Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])
                    ->schema([
                        Select::make('content_type')
                            ->label('Content Type')
                            ->options([
                                'page' => 'Page',
                                'product' => 'Product',
                                'blog' => 'Blog Article',
                                'landing_page' => 'Landing Page',
                                'route_page' => 'Taxi Route Page',
                                'city_page' => 'City Page',
                                'service_page' => 'Service Page',
                                'tour_package' => 'Tour Package',
                            ])
                            ->default('page')
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(
                                fn (Get $get, Set $set): mixed => self::autoFillLinksAndCta(
                                    $get,
                                    $set,
                                ),
                            ),

                        TextInput::make('author_name')
                            ->label('Author Name')
                            ->placeholder('Example: Dura Cabs Team')
                            ->maxLength(255),

                        TextInput::make('reading_time')
                            ->label('Reading Time')
                            ->numeric()
                            ->suffix('minutes')
                            ->minValue(1)
                            ->helperText(
                                'Blank chhodne par model content se automatically calculate karega.',
                            ),
                    ]),

                Textarea::make('excerpt')
                    ->label('Excerpt / Short Summary')
                    ->placeholder(
                        'Page ka short introduction ya search/social preview summary.',
                    )
                    ->rows(3)
                    ->maxLength(500)
                    ->live(onBlur: true)
                    ->columnSpanFull(),

                RichEditor::make($this->contentField)
                    ->label('Main Content')
                    ->required()
                    ->live(onBlur: true)
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('content-writer')
                    ->fileAttachmentsVisibility('public')
                    ->toolbarButtons([
                        'attachFiles',
                        'blockquote',
                        'bold',
                        'bulletList',
                        'codeBlock',
                        'h1',
                        'h2',
                        'h3',
                        'italic',
                        'link',
                        'orderedList',
                        'redo',
                        'strike',
                        'underline',
                        'undo',
                    ])
                    ->columnSpanFull(),

                Grid::make([
                    'default' => 1,
                    'md' => 3,
                ])
                    ->schema([
                        Placeholder::make('content_word_count')
                            ->label('Word Count')
                            ->content(
                                fn (Get $get): string => number_format(
                                    $this->wordCount(
                                        $get($this->contentField),
                                    ),
                                ),
                            ),

                        Placeholder::make('content_reading_time_preview')
                            ->label('Estimated Reading Time')
                            ->content(
                                function (Get $get): string {
                                    $wordCount = $this->wordCount(
                                        $get($this->contentField),
                                    );

                                    if ($wordCount === 0) {
                                        return '0 minutes';
                                    }

                                    return max(
                                        1,
                                        (int) ceil($wordCount / 200),
                                    ) . ' minutes';
                                },
                            ),

                        Placeholder::make('content_paragraph_count')
                            ->label('Paragraphs')
                            ->content(
                                fn (Get $get): string => (string) $this->paragraphCount(
                                    $get($this->contentField),
                                ),
                            ),
                    ]),
            ]);
    }

    private function seoTab(): Tab
    {
        return Tab::make('SEO')
            ->icon('heroicon-o-magnifying-glass')
            ->schema([
                Grid::make([
                    'default' => 1,
                    'lg' => 2,
                ])
                    ->schema([
                        TextInput::make('focus_keyword')
                            ->label('Focus Keyword')
                            ->placeholder('Example: Agra to Delhi taxi')
                            ->maxLength(150)
                            ->live(onBlur: true),

                        TagsInput::make('secondary_keywords')
                            ->label('Secondary Keywords')
                            ->placeholder('Keyword add karein')
                            ->separator(',')
                            ->reorderable(),

                        TextInput::make('meta_title')
                            ->label('SEO Title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->helperText(
                                fn (Get $get): string => $this->lengthHelper(
                                    value: $get('meta_title'),
                                    recommendedMinimum: 30,
                                    recommendedMaximum: 60,
                                ),
                            ),

                        Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->live(onBlur: true)
                            ->helperText(
                                fn (Get $get): string => $this->lengthHelper(
                                    value: $get('meta_description'),
                                    recommendedMinimum: 120,
                                    recommendedMaximum: 160,
                                ),
                            ),

                        TextInput::make('canonical_url')
                            ->label('Canonical URL')
                            ->url()
                            ->placeholder(
                                'https://duracabs.com/agra-to-delhi-taxi',
                            )
                            ->columnSpanFull(),

                        Select::make('robots')
                            ->label('Search Engine Robots')
                            ->options([
                                'index,follow' => 'Index, Follow',
                                'index,nofollow' => 'Index, No Follow',
                                'noindex,follow' => 'No Index, Follow',
                                'noindex,nofollow' => 'No Index, No Follow',
                            ])
                            ->default('index,follow')
                            ->native(false),

                        TextInput::make('meta_keywords')
                            ->label('Legacy Meta Keywords')
                            ->placeholder(
                                'agra taxi, delhi taxi, cab booking',
                            )
                            ->helperText(
                                'Google ranking ke liye zaroori nahi, lekin legacy compatibility ke liye rakha gaya hai.',
                            ),
                    ]),

                Section::make('Live SEO Summary')
                    ->icon('heroicon-o-chart-bar')
                    ->compact()
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 3,
                        ])
                            ->schema([
                                Placeholder::make('live_seo_score')
                                    ->label('SEO Score')
                                    ->content(
                                        fn (Get $get): HtmlString => $this->renderScore(
                                            $this->calculateSeoScore($get),
                                        ),
                                    ),

                                Placeholder::make('live_readability_score')
                                    ->label('Readability')
                                    ->content(
                                        fn (Get $get): HtmlString => $this->renderScore(
                                            $this->calculateReadabilityScore(
                                                $get($this->contentField),
                                            ),
                                        ),
                                    ),

                                Placeholder::make('keyword_density')
                                    ->label('Keyword Density')
                                    ->content(
                                        fn (Get $get): string => $this->keywordDensity(
                                            content: $get($this->contentField),
                                            keyword: $get('focus_keyword'),
                                        ),
                                    ),
                            ]),

                        Placeholder::make('seo_recommendations')
                            ->label('SEO Suggestions')
                            ->content(
                                fn (Get $get): HtmlString => $this->renderSeoSuggestions(
                                    $get,
                                ),
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private function aiWriterTab(): Tab
    {
        return Tab::make('AI Writer')
            ->icon('heroicon-o-sparkles')
            ->schema([
                DuraSeoAiWriter::make('dura_seo_ai_writer')
                    ->columnSpanFull(),
            ]);
    }

    private function faqTab(): Tab
    {
        return Tab::make('FAQ')
            ->icon('heroicon-o-question-mark-circle')
            ->schema([
                Repeater::make('faq_schema')
                    ->label('Frequently Asked Questions')
                    ->addActionLabel('Add FAQ')
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(
                        fn (array $state): ?string => filled(
                            $state['question'] ?? null,
                        )
                            ? Str::limit(
                                (string) $state['question'],
                                70,
                            )
                            : 'New FAQ',
                    )
                    ->schema([
                        TextInput::make('question')
                            ->label('Question')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull(),

                        RichEditor::make('answer')
                            ->label('Answer')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'undo',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),

                Placeholder::make('faq_schema_status')
                    ->label('FAQ Schema')
                    ->content(
                        function (Get $get): string {
                            $faqs = $get('faq_schema');

                            if (! is_array($faqs) || $faqs === []) {
                                return 'No FAQ added';
                            }

                            $validFaqs = collect($faqs)
                                ->filter(
                                    fn (mixed $faq): bool => is_array($faq)
                                        && filled($faq['question'] ?? null)
                                        && filled($faq['answer'] ?? null),
                                )
                                ->count();

                            return "{$validFaqs} valid FAQ items ready for FAQPage schema";
                        },
                    ),
            ]);
    }

    private function socialTab(): Tab
    {
        return Tab::make('Social')
            ->icon('heroicon-o-share')
            ->schema([
                Section::make('Open Graph / Facebook')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'lg' => 2,
                        ])
                            ->schema([
                                TextInput::make('og_title')
                                    ->label('Open Graph Title')
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->placeholder(
                                        'Blank hone par SEO title use hoga.',
                                    ),

                                Textarea::make('og_description')
                                    ->label('Open Graph Description')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->live(onBlur: true)
                                    ->placeholder(
                                        'Blank hone par meta description use hogi.',
                                    ),

                                FileUpload::make('og_image')
                                    ->label('Open Graph Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('seo/social/open-graph')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Twitter / X')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'lg' => 2,
                        ])
                            ->schema([
                                TextInput::make('twitter_title')
                                    ->label('Twitter Title')
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->placeholder(
                                        'Blank hone par Open Graph ya SEO title use hoga.',
                                    ),

                                Textarea::make('twitter_description')
                                    ->label('Twitter Description')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->live(onBlur: true)
                                    ->placeholder(
                                        'Blank hone par Open Graph ya meta description use hogi.',
                                    ),

                                FileUpload::make('twitter_image')
                                    ->label('Twitter Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('seo/social/twitter')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    private function schemaTab(): Tab
    {
        return Tab::make('Schema')
            ->icon('heroicon-o-code-bracket-square')
            ->schema([
                Grid::make([
                    'default' => 1,
                    'lg' => 2,
                ])
                    ->schema([
                        Select::make('schema_type')
                            ->label('Primary Schema Type')
                            ->options([
                                'WebPage' => 'WebPage',
                                'Article' => 'Article',
                                'BlogPosting' => 'Blog Posting',
                                'Product' => 'Product',
                                'Service' => 'Service',
                                'LocalBusiness' => 'Local Business',
                                'TaxiService' => 'Taxi Service',
                                'TouristTrip' => 'Tourist Trip',
                                'AboutPage' => 'About Page',
                                'ContactPage' => 'Contact Page',
                            ])
                            ->default('WebPage')
                            ->required()
                            ->dehydrateStateUsing(
                                fn (?string $state): string => filled($state)
                                    ? $state
                                    : 'WebPage',
                            )
                            ->searchable()
                            ->native(false),

                        Toggle::make('table_of_contents')
                            ->label('Show Table of Contents')
                            ->helperText(
                                'Frontend me headings se table of contents generate ki ja sakti hai.',
                            )
                            ->default(false),

                        Repeater::make('breadcrumb_schema')
                            ->label('Custom Breadcrumbs')
                            ->addActionLabel('Add Breadcrumb')
                            ->defaultItems(0)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('url')
                                    ->label('URL')
                                    ->required()
                                    ->url(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),

                        Textarea::make('custom_schema')
                            ->label('Custom JSON-LD Schema')
                            ->rows(12)
                            ->helperText(
                                'Valid JSON enter karein. Blank hone par automatic schema use hoga.',
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private function linksTab(): Tab
    {
        return Tab::make('Links & CTA')
            ->icon('heroicon-o-link')
            ->schema([
                Actions::make([
                    Action::make('autoFillLinksAndCta')
                        ->label('Auto Fill Links & CTA')
                        ->icon('heroicon-o-sparkles')
                        ->color('primary')
                        ->action(
                            fn (Get $get, Set $set): mixed => self::autoFillLinksAndCta(
                                $get,
                                $set,
                                true,
                            ),
                        ),
                ])
                    ->fullWidth(),

                Placeholder::make('links_cta_auto_fill_status')
                    ->label('Automatic Linking')
                    ->content(
                        fn (Get $get): string => filled($get('slug'))
                            ? 'Page name, slug, page type aur city ke basis par Links & CTA automatically fill honge. Manual values ko normal auto-fill overwrite nahi karega.'
                            : 'Page Name / Slug enter karte hi Links & CTA auto-fill ho jayenge.',
                    )
                    ->columnSpanFull(),

                Repeater::make('internal_links')
                    ->label('Internal Links')
                    ->addActionLabel('Add Internal Link')
                    ->reorderable()
                    ->collapsible()
                    ->schema([
                        TextInput::make('name')
                            ->label('Link Text')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('url')
                            ->label('URL')
                            ->required()
                            ->maxLength(1000),

                        TextInput::make('title')
                            ->label('Link Title')
                            ->maxLength(255),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Repeater::make('cta')
                    ->label('Call To Action')
                    ->addActionLabel('Add CTA')
                    ->maxItems(5)
                    ->collapsible()
                    ->schema([
                        TextInput::make('title')
                            ->label('CTA Title')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('CTA Description')
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('button_text')
                            ->label('Button Text')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('button_url')
                            ->label('Button URL')
                            ->required()
                            ->maxLength(1000),

                        Select::make('style')
                            ->label('Style')
                            ->options([
                                'primary' => 'Primary',
                                'secondary' => 'Secondary',
                                'success' => 'Success',
                                'warning' => 'Warning',
                                'dark' => 'Dark',
                            ])
                            ->default('primary')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Repeater::make('related_pages')
                    ->label('Related Pages')
                    ->addActionLabel('Add Related Page')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required(),

                        TextInput::make('url')
                            ->label('URL')
                            ->required(),
                    ])
                    ->columns(2),

                Repeater::make('related_products')
                    ->label('Related Products')
                    ->addActionLabel('Add Related Product')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required(),

                        TextInput::make('url')
                            ->label('URL'),

                        TextInput::make('price')
                            ->label('Price')
                            ->numeric(),
                    ])
                    ->columns(3),

                Repeater::make('related_blogs')
                    ->label('Related Blogs')
                    ->addActionLabel('Add Related Blog')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required(),

                        TextInput::make('url')
                            ->label('URL')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    private function previewTab(): Tab
    {
        return Tab::make('Preview')
            ->icon('heroicon-o-eye')
            ->schema([
                Placeholder::make('google_search_preview')
                    ->label('Google Search Preview')
                    ->content(
                        fn (Get $get): HtmlString => $this->renderGooglePreview(
                            $get,
                        ),
                    )
                    ->columnSpanFull(),

                Placeholder::make('social_card_preview')
                    ->label('Social Preview')
                    ->content(
                        fn (Get $get): HtmlString => $this->renderSocialPreview(
                            $get,
                        ),
                    )
                    ->columnSpanFull(),

                Actions::make([
                    Action::make('copySeoTitleToSocial')
                        ->label('SEO Data Social Fields Me Copy Karein')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(
                            function (Get $get, Set $set): void {
                                $metaTitle = trim(
                                    (string) $get('meta_title'),
                                );

                                $metaDescription = trim(
                                    (string) $get('meta_description'),
                                );

                                if (blank($get('og_title'))) {
                                    $set('og_title', $metaTitle);
                                }

                                if (blank($get('og_description'))) {
                                    $set(
                                        'og_description',
                                        $metaDescription,
                                    );
                                }

                                if (blank($get('twitter_title'))) {
                                    $set(
                                        'twitter_title',
                                        $metaTitle,
                                    );
                                }

                                if (
                                    blank(
                                        $get('twitter_description'),
                                    )
                                ) {
                                    $set(
                                        'twitter_description',
                                        $metaDescription,
                                    );
                                }
                            },
                        ),
                ])
                    ->fullWidth(),
            ]);
    }

    public static function autoFillLinksAndCta(
        Get $get,
        Set $set,
        bool $force = false,
    ): void {
        $name = trim((string) $get('name'));
        $slug = trim((string) $get('slug'));
        $contentType = trim((string) ($get('content_type') ?: 'page'));
        $brandId = $get('brand_id');

        if ($name === '' || $slug === '') {
            return;
        }

        $pageUrl = self::publicUrlFor($contentType, $slug);

        $existingInternalLinks = $get('internal_links');

        if ($force || ! is_array($existingInternalLinks) || $existingInternalLinks === []) {
            $query = Page::query()
                ->select(['id', 'name', 'slug', 'content_type', 'brand_id'])
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->where('slug', '!=', $slug);

            if (filled($brandId)) {
                $query->orderByRaw(
                    'CASE WHEN brand_id = ? THEN 0 ELSE 1 END',
                    [(int) $brandId],
                );
            }

            $related = $query
                ->latest('updated_at')
                ->limit(6)
                ->get();

            $internalLinks = $related
                ->map(
                    fn (Page $page): array => [
                        'name' => trim((string) $page->name),
                        'url' => self::publicUrlFor(
                            (string) ($page->content_type ?: 'page'),
                            (string) $page->slug,
                        ),
                        'title' => trim((string) $page->name),
                    ],
                )
                ->filter(fn (array $item): bool => $item['name'] !== '')
                ->values()
                ->all();

            $set('internal_links', $internalLinks);

            if ($force || ! is_array($get('related_pages')) || $get('related_pages') === []) {
                $set(
                    'related_pages',
                    collect($internalLinks)
                        ->take(4)
                        ->map(
                            fn (array $item): array => [
                                'name' => $item['name'],
                                'url' => $item['url'],
                            ],
                        )
                        ->values()
                        ->all(),
                );
            }
        }

        $existingCta = $get('cta');

        if ($force || ! is_array($existingCta) || $existingCta === []) {
            $ctaCopy = self::ctaCopyFor($contentType, $name);

            $set('cta', [[
                'title' => $ctaCopy['title'],
                'description' => $ctaCopy['description'],
                'button_text' => $ctaCopy['button_text'],
                'button_url' => $pageUrl,
                'style' => 'primary',
            ]]);
        }
    }

    private static function publicUrlFor(string $contentType, string $slug): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $cleanSlug = ltrim(trim($slug), '/');

        $prefix = match ($contentType) {
            'blog' => 'blog',
            'tour_package' => 'tour',
            default => 'pages',
        };

        return $baseUrl . '/' . $prefix . '/' . $cleanSlug;
    }

    /**
     * @return array{title: string, description: string, button_text: string}
     */
    private static function ctaCopyFor(string $contentType, string $name): array
    {
        return match ($contentType) {
            'route_page' => [
                'title' => 'Book ' . $name,
                'description' => 'Check available cabs, current fare and travel options for this route.',
                'button_text' => 'Check Fare & Book',
            ],
            'service_page' => [
                'title' => 'Find Self Drive Cars',
                'description' => 'Select your pickup date and time to check available self drive cars.',
                'button_text' => 'Search Cars',
            ],
            'tour_package' => [
                'title' => 'Plan ' . $name,
                'description' => 'Check trip details and continue with your tour booking enquiry.',
                'button_text' => 'View Tour & Book',
            ],
            'blog' => [
                'title' => 'Plan Your Trip with Dura Cabs',
                'description' => 'Use Dura Cabs to check suitable travel options for your journey.',
                'button_text' => 'Book a Cab',
            ],
            default => [
                'title' => 'Book ' . $name,
                'description' => 'Check available travel options and continue with your Dura Cabs booking.',
                'button_text' => 'Book Now',
            ],
        };
    }

    private function calculateSeoScore(Get $get): int
    {
        $score = 0;

        $title = trim((string) $get('meta_title'));
        $description = trim(
            (string) $get('meta_description'),
        );
        $slug = trim((string) $get('slug'));
        $keyword = trim((string) $get('focus_keyword'));
        $content = $this->plainText(
            $get($this->contentField),
        );
        $canonicalUrl = trim(
            (string) $get('canonical_url'),
        );
        $faqs = $get('faq_schema');
        $internalLinks = $get('internal_links');

        if (Str::length($title) >= 30 && Str::length($title) <= 60) {
            $score += 15;
        }

        if (
            Str::length($description) >= 120
            && Str::length($description) <= 160
        ) {
            $score += 15;
        }

        if (
            $keyword !== ''
            && Str::contains(
                Str::lower($title),
                Str::lower($keyword),
            )
        ) {
            $score += 15;
        }

        if (
            $keyword !== ''
            && Str::contains(
                Str::lower($description),
                Str::lower($keyword),
            )
        ) {
            $score += 10;
        }

        if (
            $keyword !== ''
            && Str::contains(
                Str::lower($slug),
                Str::slug($keyword),
            )
        ) {
            $score += 10;
        }

        if (
            $keyword !== ''
            && Str::contains(
                Str::lower($content),
                Str::lower($keyword),
            )
        ) {
            $score += 10;
        }

        if ($this->wordCount($content) >= 300) {
            $score += 10;
        }

        if ($canonicalUrl !== '') {
            $score += 5;
        }

        if (
            is_array($internalLinks)
            && count($internalLinks) > 0
        ) {
            $score += 5;
        }

        if (is_array($faqs) && count($faqs) > 0) {
            $score += 5;
        }

        return min(100, $score);
    }

    private function calculateReadabilityScore(
        mixed $content,
    ): int {
        $plainText = $this->plainText($content);
        $wordCount = $this->wordCount($plainText);

        if ($wordCount === 0) {
            return 0;
        }

        $score = 100;

        $sentences = preg_split(
            '/[.!?]+/',
            $plainText,
            -1,
            PREG_SPLIT_NO_EMPTY,
        ) ?: [];

        $paragraphs = $this->paragraphCount($content);

        $averageSentenceLength = count($sentences) > 0
            ? $wordCount / count($sentences)
            : $wordCount;

        if ($averageSentenceLength > 25) {
            $score -= 25;
        } elseif ($averageSentenceLength > 20) {
            $score -= 15;
        }

        if ($paragraphs > 0) {
            $averageParagraphLength = $wordCount / $paragraphs;

            if ($averageParagraphLength > 150) {
                $score -= 25;
            } elseif ($averageParagraphLength > 100) {
                $score -= 15;
            }
        }

        if ($wordCount < 300) {
            $score -= 20;
        }

        if (
            ! Str::contains(
                Str::lower($plainText),
                [
                    'however',
                    'therefore',
                    'also',
                    'because',
                    'lekin',
                    'isliye',
                    'saath hi',
                ],
            )
        ) {
            $score -= 10;
        }

        return max(0, min(100, $score));
    }

    private function renderScore(int $score): HtmlString
    {
        $status = match (true) {
            $score >= 80 => 'Excellent',
            $score >= 60 => 'Good',
            $score >= 40 => 'Needs Improvement',
            default => 'Poor',
        };

        $safeStatus = e($status);

        return new HtmlString(
            <<<HTML
            <div
                style="
                    padding: 14px;
                    border: 1px solid rgba(148, 163, 184, 0.28);
                    border-radius: 12px;
                "
            >
                <div
                    style="
                        font-size: 26px;
                        font-weight: 800;
                        line-height: 1;
                    "
                >
                    {$score}/100
                </div>

                <div
                    style="
                        margin-top: 6px;
                        color: #64748b;
                        font-size: 12px;
                    "
                >
                    {$safeStatus}
                </div>
            </div>
            HTML,
        );
    }

    private function renderSeoSuggestions(
        Get $get,
    ): HtmlString {
        $suggestions = [];

        $title = trim((string) $get('meta_title'));
        $description = trim(
            (string) $get('meta_description'),
        );
        $keyword = trim((string) $get('focus_keyword'));
        $slug = trim((string) $get('slug'));
        $content = $this->plainText(
            $get($this->contentField),
        );
        $internalLinks = $get('internal_links');
        $faqs = $get('faq_schema');

        if ($keyword === '') {
            $suggestions[] = [
                'type' => 'error',
                'message' => 'Focus keyword add karein.',
            ];
        }

        if (Str::length($title) < 30) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'SEO title bahut chhota hai.',
            ];
        } elseif (Str::length($title) > 60) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'SEO title 60 characters se zyada hai.',
            ];
        } else {
            $suggestions[] = [
                'type' => 'success',
                'message' => 'SEO title length achhi hai.',
            ];
        }

        if (Str::length($description) < 120) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'Meta description ko kam se kam 120 characters karein.',
            ];
        } elseif (Str::length($description) > 160) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'Meta description 160 characters se zyada hai.',
            ];
        } else {
            $suggestions[] = [
                'type' => 'success',
                'message' => 'Meta description length achhi hai.',
            ];
        }

        if (
            $keyword !== ''
            && ! Str::contains(
                Str::lower($title),
                Str::lower($keyword),
            )
        ) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'Focus keyword SEO title me add karein.',
            ];
        }

        if (
            $keyword !== ''
            && ! Str::contains(
                Str::lower($description),
                Str::lower($keyword),
            )
        ) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'Focus keyword meta description me add karein.',
            ];
        }

        if (
            $keyword !== ''
            && ! Str::contains(
                Str::lower($slug),
                Str::slug($keyword),
            )
        ) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'Focus keyword slug me naturally use karein.',
            ];
        }

        if ($this->wordCount($content) < 300) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'Content kam se kam 300 words ka rakhein.',
            ];
        } else {
            $suggestions[] = [
                'type' => 'success',
                'message' => 'Content length basic SEO requirement pass karti hai.',
            ];
        }

        if (
            ! is_array($internalLinks)
            || count($internalLinks) === 0
        ) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'Kam se kam ek internal link add karein.',
            ];
        }

        if (! is_array($faqs) || count($faqs) === 0) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'Relevant FAQs add karne par rich results improve ho sakte hain.',
            ];
        }

        $items = collect($suggestions)
            ->map(function (array $suggestion): string {
                $symbol = match ($suggestion['type']) {
                    'success' => '✓',
                    'error' => '✕',
                    default => '!',
                };

                $message = e(
                    (string) $suggestion['message'],
                );

                return <<<HTML
                <div
                    style="
                        display: flex;
                        gap: 9px;
                        align-items: flex-start;
                        padding: 8px 0;
                        border-bottom: 1px solid rgba(148, 163, 184, 0.14);
                    "
                >
                    <strong>{$symbol}</strong>
                    <span>{$message}</span>
                </div>
                HTML;
            })
            ->implode('');

        return new HtmlString(
            <<<HTML
            <div
                style="
                    padding: 10px 14px;
                    border: 1px solid rgba(148, 163, 184, 0.28);
                    border-radius: 12px;
                    font-size: 13px;
                "
            >
                {$items}
            </div>
            HTML,
        );
    }

    private function renderGooglePreview(
        Get $get,
    ): HtmlString {
        $title = trim(
            (string) (
                $get('meta_title')
                ?: $get('name')
                ?: config('app.name')
            ),
        );

        $description = trim(
            (string) (
                $get('meta_description')
                ?: $get('excerpt')
                ?: ''
            ),
        );

        $slug = trim((string) $get('slug'));

        $url = trim(
            (string) (
                $get('canonical_url')
                ?: url($slug)
            ),
        );

        $safeTitle = e(
            Str::limit($title, 65, '…'),
        );

        $safeDescription = e(
            Str::limit($description, 165, '…'),
        );

        $safeUrl = e($url);

        return new HtmlString(
            <<<HTML
            <div
                style="
                    max-width: 680px;
                    padding: 20px;
                    border: 1px solid rgba(148, 163, 184, 0.30);
                    border-radius: 14px;
                    background: #ffffff;
                "
            >
                <div
                    style="
                        color: #202124;
                        font-size: 14px;
                    "
                >
                    {$safeUrl}
                </div>

                <div
                    style="
                        margin-top: 5px;
                        color: #1a0dab;
                        font-size: 21px;
                        line-height: 1.25;
                    "
                >
                    {$safeTitle}
                </div>

                <div
                    style="
                        margin-top: 6px;
                        color: #4d5156;
                        font-size: 14px;
                        line-height: 1.55;
                    "
                >
                    {$safeDescription}
                </div>
            </div>
            HTML,
        );
    }

    private function renderSocialPreview(
        Get $get,
    ): HtmlString {
        $title = trim(
            (string) (
                $get('og_title')
                ?: $get('meta_title')
                ?: $get('name')
                ?: config('app.name')
            ),
        );

        $description = trim(
            (string) (
                $get('og_description')
                ?: $get('meta_description')
                ?: $get('excerpt')
                ?: ''
            ),
        );

        $slug = trim((string) $get('slug'));

        $url = trim(
            (string) (
                $get('canonical_url')
                ?: url($slug)
            ),
        );

        $safeTitle = e(
            Str::limit($title, 75, '…'),
        );

        $safeDescription = e(
            Str::limit($description, 180, '…'),
        );

        $safeUrl = e($url);

        return new HtmlString(
            <<<HTML
            <div
                style="
                    max-width: 620px;
                    overflow: hidden;
                    border: 1px solid rgba(148, 163, 184, 0.35);
                    border-radius: 14px;
                "
            >
                <div
                    style="
                        min-height: 220px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: rgba(148, 163, 184, 0.12);
                        color: #64748b;
                        font-size: 13px;
                    "
                >
                    Social preview image
                </div>

                <div
                    style="
                        padding: 15px;
                    "
                >
                    <div
                        style="
                            color: #64748b;
                            font-size: 11px;
                            text-transform: uppercase;
                        "
                    >
                        {$safeUrl}
                    </div>

                    <div
                        style="
                            margin-top: 5px;
                            font-size: 17px;
                            font-weight: 700;
                        "
                    >
                        {$safeTitle}
                    </div>

                    <div
                        style="
                            margin-top: 6px;
                            color: #64748b;
                            font-size: 13px;
                            line-height: 1.5;
                        "
                    >
                        {$safeDescription}
                    </div>
                </div>
            </div>
            HTML,
        );
    }

    private function lengthHelper(
        mixed $value,
        int $recommendedMinimum,
        int $recommendedMaximum,
    ): string {
        $length = Str::length(
            trim((string) $value),
        );

        return "{$length} characters — recommended {$recommendedMinimum}–{$recommendedMaximum}";
    }

    private function keywordDensity(
        mixed $content,
        mixed $keyword,
    ): string {
        $content = Str::lower(
            $this->plainText($content),
        );

        $keyword = Str::lower(
            trim((string) $keyword),
        );

        $wordCount = $this->wordCount($content);

        if (
            $keyword === ''
            || $content === ''
            || $wordCount === 0
        ) {
            return '0%';
        }

        $occurrences = substr_count(
            $content,
            $keyword,
        );

        $keywordWords = max(
            1,
            $this->wordCount($keyword),
        );

        $density = (
            ($occurrences * $keywordWords)
            / $wordCount
        ) * 100;

        return number_format($density, 2) . '%';
    }

    private function plainText(mixed $content): string
    {
        $content = html_entity_decode(
            strip_tags((string) $content),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $content,
            ) ?? '',
        );
    }

    private function wordCount(mixed $content): int
    {
        $plainText = $this->plainText($content);

        if ($plainText === '') {
            return 0;
        }

        return str_word_count($plainText);
    }

    private function paragraphCount(
        mixed $content,
    ): int {
        $content = trim((string) $content);

        if ($content === '') {
            return 0;
        }

        $paragraphMatches = preg_match_all(
            '/<p\b[^>]*>.*?<\/p>/is',
            $content,
        );

        if (is_int($paragraphMatches) && $paragraphMatches > 0) {
            return $paragraphMatches;
        }

        $plainText = strip_tags(
            str_replace(
                ['<br>', '<br/>', '<br />'],
                "\n",
                $content,
            ),
        );

        $paragraphs = preg_split(
            '/\n\s*\n/',
            trim($plainText),
            -1,
            PREG_SPLIT_NO_EMPTY,
        );

        return max(
            1,
            count($paragraphs ?: []),
        );
    }
}