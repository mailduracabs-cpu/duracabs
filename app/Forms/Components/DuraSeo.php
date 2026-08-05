<?php

namespace App\Forms\Components;

use App\SEO\Services\SeoAnalysisService;
use Closure;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

class DuraSeo extends Section
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->heading('Dura SEO Control Center')
            ->description(
                'Search appearance, index readiness, sitemap eligibility, canonical, robots and content SEO ka live analysis.'
            )
            ->icon('heroicon-o-chart-bar-square')
            ->collapsible()
            ->compact()
            ->schema([
                Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])
                    ->schema([
                        Placeholder::make('dura_seo_score_card')
                            ->label('')
                            ->content(
                                fn (Get $get): HtmlString => $this->renderScoreCard(
                                    $this->getAnalysis($get),
                                ),
                            )
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 1,
                            ]),

                        Placeholder::make('dura_seo_statistics')
                            ->label('')
                            ->content(
                                fn (Get $get): HtmlString => $this->renderStatistics(
                                    $this->getAnalysis($get),
                                ),
                            )
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),
                    ]),

                Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])
                    ->schema([
                        Placeholder::make('dura_index_health')
                            ->label('')
                            ->content(
                                fn (Get $get): HtmlString => $this->renderIndexHealth(
                                    $this->getAnalysis($get),
                                ),
                            )
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),

                        Placeholder::make('dura_search_console')
                            ->label('')
                            ->content(
                                fn (Get $get): HtmlString => $this->renderSearchConsoleCard(
                                    $this->getAnalysis($get),
                                ),
                            )
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 1,
                            ]),
                    ]),

                Grid::make([
                    'default' => 1,
                    'md' => 2,
                ])
                    ->schema([
                        TextInput::make('focus_keyword')
    ->label('Focus Keyword')
    ->placeholder('Agra to Delhi taxi service')
    ->helperText(
        'Primary keyword used for SEO analysis.'
    )
    ->prefixIcon('heroicon-o-magnifying-glass')
    ->maxLength(150)
    ->live(debounce: 700),

TextInput::make('meta_title')
    ->label('SEO Title')
    ->placeholder('Agra to Delhi Taxi Service | Dura Cabs')
    ->required()
    ->maxLength(255)
    ->live(debounce: 700)
    ->helperText(
        fn (Get $get): string => $this->getTitleHelperText(
            (string) $get('meta_title'),
        ),
    )
    ->suffix(
        fn (Get $get): string => Str::length(
            trim((string) $get('meta_title')),
        ) . '/60',
    
                            ),
                    ]),

                Textarea::make('meta_description')
                    ->label('Meta Description')
                    ->placeholder(
                        'Book Agra to Delhi taxi service with Dura Cabs. Get affordable fares, verified drivers and easy online booking.'
                    )
                    ->required()
                    ->rows(3)
                    ->maxLength(255)
                    ->live(debounce: 700)
                    ->helperText(
                        fn (Get $get): string => $this->getDescriptionHelperText(
                            (string) $get('meta_description'),
                        ),
                    )
                    ->columnSpanFull(),


                Section::make('Manual JSON-LD Schema')
                    ->description(
                        'Migration ke bina schema aur FAQs existing seo_analysis JSON field me save honge. Manual JSON blank ho to frontend automatic schema use karega.'
                    )
                    ->icon('heroicon-o-code-bracket-square')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                Toggle::make('seo_analysis.schema.enabled')
                                    ->label('Enable Manual Schema')
                                    ->default(false)
                                    ->live(),

                                Select::make('seo_analysis.schema.type')
                                    ->label('Schema Type')
                                    ->options([
                                        'auto' => 'Auto',
                                        'Organization' => 'Organization',
                                        'WebSite' => 'WebSite',
                                        'LocalBusiness' => 'Local Business',
                                        'Service' => 'Service',
                                        'Product' => 'Product',
                                        'Article' => 'Article',
                                        'FAQPage' => 'FAQ Page',
                                        'BreadcrumbList' => 'Breadcrumb',
                                        'Review' => 'Review',
                                        'HowTo' => 'HowTo',
                                        'Event' => 'Event',
                                        'Custom' => 'Custom',
                                    ])
                                    ->default('auto')
                                    ->native(false)
                                    ->searchable()
                                    ->live(),
                            ]),

                        Textarea::make('seo_analysis.schema.json')
                            ->label('Manual JSON-LD')
                            ->placeholder(
                                "{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"Service\",\n  \"name\": \"Example Service\"\n}"
                            )
                            ->helperText(
                                'Sirf valid JSON object ya JSON array enter karein. <script> tag paste na karein.'
                            )
                            ->rows(16)
                            ->extraAttributes([
                                'class' => 'font-mono text-sm',
                                'spellcheck' => 'false',
                            ])
                            ->live(debounce: 900)
                            ->rules([
                                function (Get $get): Closure {
                                    return static function (
                                        string $attribute,
                                        mixed $value,
                                        Closure $fail
                                    ) use ($get): void {
                                        if (! (bool) $get('seo_analysis.schema.enabled')) {
                                            return;
                                        }

                                        $json = trim((string) $value);

                                        if ($json === '') {
                                            return;
                                        }

                                        try {
                                            $decoded = json_decode(
                                                $json,
                                                true,
                                                512,
                                                JSON_THROW_ON_ERROR
                                            );

                                            if (! is_array($decoded)) {
                                                $fail('Schema JSON object ya array hona chahiye.');
                                            }
                                        } catch (\JsonException $exception) {
                                            $fail(
                                                'Invalid JSON: ' . $exception->getMessage()
                                            );
                                        }
                                    };
                                },
                            ])
                            ->columnSpanFull(),

                        Repeater::make('seo_analysis.faqs')
                            ->label('FAQ Builder')
                            ->helperText(
                                'FAQs yahan save honge. Frontend inhe FAQPage schema me convert kar sakta hai.'
                            )
                            ->schema([
                                TextInput::make('question')
                                    ->label('Question')
                                    ->required()
                                    ->maxLength(500)
                                    ->columnSpanFull(),

                                Textarea::make('answer')
                                    ->label('Answer')
                                    ->required()
                                    ->rows(4)
                                    ->maxLength(3000)
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->defaultItems(0)
                            ->addActionLabel('Add FAQ')
                            ->reorderable()
                            ->collapsible()
                            ->columnSpanFull(),

                        Placeholder::make('dura_schema_preview')
                            ->label('Schema Validation Preview')
                            ->content(
                                fn (Get $get): HtmlString =>
                                    $this->renderSchemaPreview($get)
                            )
                            ->columnSpanFull(),
                    ]),

                Placeholder::make('dura_google_preview')
                    ->label('Google Search Preview')
                    ->content(
                        fn (Get $get): HtmlString => $this->renderGooglePreview($get),
                    )
                    ->columnSpanFull(),

                Placeholder::make('dura_seo_issues')
                    ->label('SEO Analysis')
                    ->content(
                        fn (Get $get): HtmlString => $this->renderIssues(
                            $this->getAnalysis($get),
                        ),
                    )
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Current Filament form state ko SEO service ke format me convert karta hai.
     *
     * @return array<string, mixed>
     */
    private function getAnalysis(Get $get): array
{
    try {
        return app(SeoAnalysisService::class)->analyzeToArray([
            'title' => trim((string) ($get('meta_title') ?: $get('name') ?: '')),

            'meta_title' => trim((string) ($get('meta_title') ?: $get('name') ?: '')),

            'name' => trim((string) $get('name')),

            'meta_description' => trim((string) $get('meta_description')),

            'slug' => trim((string) $get('slug')),

            'focus_keyword' => trim((string) $get('focus_keyword')),

            'description' => (string) (
                $get('description')
                ?: $get('content')
                ?: ''
            ),

            'page_url' => trim((string) (
                $get('seo_public_url')
                ?: $get('page_url')
                ?: $get('url')
                ?: ''
            )),

            'canonical_url' => trim((string) (
                $get('canonical_url')
                ?: $get('canonical')
                ?: $get('seo_public_url')
                ?: ''
            )),

            'robots' => trim((string) $get('robots')),

            'robots_index' => $get('robots_index'),

            'robots_follow' => $get('robots_follow'),

            'is_active' => $get('is_active') ?? true,

            'published_at' => $get('published_at'),
        ]);
    } catch (Throwable $exception) {
        report($exception);

        return $this->emptyAnalysis(
            message: 'SEO analysis service load nahi ho saki.',
        );
    }
}

    /**
     * @return array<string, mixed>
     */
    private function emptyAnalysis(?string $message = null): array
    {
        return [
            'score' => 0,
            'status' => 'poor',
            'status_label' => 'Not Analyzed',
            'status_color' => 'danger',
            'word_count' => 0,
            'reading_time' => 0,
            'keyword_density' => 0,
            'readability_score' => 0,
            'passed_percentage' => 0,

            'summary' => [
                'total' => 0,
                'passed' => 0,
                'errors' => 0,
                'warnings' => 0,
                'info' => 0,
            ],

            'issues' => $message === null
                ? []
                : [
                    [
                        'type' => 'error',
                        'title' => 'Analysis unavailable',
                        'message' => $message,
                        'suggestion' => 'Laravel log check karein aur SEO service configuration verify karein.',
                        'passed' => false,
                    ],
                ],

            'indexing' => [
                'index_ready' => false,
                'status' => 'blocked',
                'status_label' => 'Not Analyzed',
                'status_color' => 'danger',
                'page_url' => rtrim((string) config('app.url'), '/'),
                'canonical_url' => '',
                'canonical_status' => 'missing',
                'canonical_matches_page' => false,
                'robots' => 'index,follow',
                'robots_index' => true,
                'robots_follow' => true,
                'is_active' => true,
                'is_published' => true,
                'sitemap_eligible' => false,
                'blockers' => [],
                'warnings' => [],
                'checks' => [],
            ],

            'index_ready' => false,
            'index_status' => 'blocked',
            'index_status_label' => 'Not Analyzed',
            'index_status_color' => 'danger',
            'page_url' => rtrim((string) config('app.url'), '/'),
            'canonical_url' => '',
            'robots' => 'index,follow',
            'sitemap_eligible' => false,
        ];
    }

    /**
     * @param array<string, mixed> $analysis
     */
    private function renderIndexHealth(array $analysis): HtmlString
    {
        $indexing = is_array($analysis['indexing'] ?? null)
            ? $analysis['indexing']
            : [];

        $statusLabel = e((string) (
            $indexing['status_label']
            ?? 'Not Analyzed'
        ));

        $statusColor = match ((string) (
            $indexing['status_color']
            ?? 'danger'
        )) {
            'success' => '#16a34a',
            'warning' => '#d97706',
            'info' => '#2563eb',
            'gray' => '#64748b',
            default => '#dc2626',
        };

        $pageUrl = e((string) ($indexing['page_url'] ?? ''));
        $canonicalStatus = match ((string) (
            $indexing['canonical_status']
            ?? 'missing'
        )) {
            'self' => ['Valid', '#16a34a', '#dcfce7'],
            'different' => ['Different', '#92400e', '#fef3c7'],
            default => ['Missing', '#991b1b', '#fee2e2'],
        };

        $robots = e((string) (
            $indexing['robots']
            ?? 'index,follow'
        ));

        $robotsColor = ($indexing['robots_index'] ?? true)
            ? '#166534'
            : '#991b1b';

        $robotsBackground = ($indexing['robots_index'] ?? true)
            ? '#dcfce7'
            : '#fee2e2';

        $sitemapEligible = (bool) (
            $indexing['sitemap_eligible']
            ?? false
        );

        $sitemapLabel = $sitemapEligible
            ? 'Eligible'
            : 'Excluded';

        $sitemapColor = $sitemapEligible
            ? '#166534'
            : '#64748b';

        $sitemapBackground = $sitemapEligible
            ? '#dcfce7'
            : '#f1f5f9';

        $blockers = is_array($indexing['blockers'] ?? null)
            ? $indexing['blockers']
            : [];

        $warnings = is_array($indexing['warnings'] ?? null)
            ? $indexing['warnings']
            : [];

        $issuesHtml = '';

        foreach ($blockers as $blocker) {
            if (! is_array($blocker)) {
                continue;
            }

            $title = e((string) (
                $blocker['title']
                ?? 'Indexing blocker'
            ));

            $recommendation = e((string) (
                $blocker['recommendation']
                ?? ''
            ));

            $issuesHtml .= <<<HTML
            <div style="padding: 10px 12px; border: 1px solid #fecaca; border-radius: 9px; background: #fef2f2;">
                <div style="font-size: 12px; font-weight: 700; color: #991b1b;">✕ {$title}</div>
                <div style="margin-top: 3px; font-size: 11px; line-height: 1.45; color: #7f1d1d;">{$recommendation}</div>
            </div>
            HTML;
        }

        foreach ($warnings as $warning) {
            if (! is_array($warning)) {
                continue;
            }

            $title = e((string) (
                $warning['title']
                ?? 'SEO warning'
            ));

            $recommendation = e((string) (
                $warning['recommendation']
                ?? ''
            ));

            $issuesHtml .= <<<HTML
            <div style="padding: 10px 12px; border: 1px solid #fde68a; border-radius: 9px; background: #fffbeb;">
                <div style="font-size: 12px; font-weight: 700; color: #92400e;">! {$title}</div>
                <div style="margin-top: 3px; font-size: 11px; line-height: 1.45; color: #78350f;">{$recommendation}</div>
            </div>
            HTML;
        }

        if ($issuesHtml === '') {
            $issuesHtml = <<<HTML
            <div style="padding: 10px 12px; border: 1px solid #bbf7d0; border-radius: 9px; background: #f0fdf4; color: #166534; font-size: 12px; font-weight: 600;">
                ✓ No local indexing blockers or warnings detected.
            </div>
            HTML;
        }

        $canonicalLabel = e($canonicalStatus[0]);
        $canonicalColor = $canonicalStatus[1];
        $canonicalBackground = $canonicalStatus[2];

        $html = <<<HTML
        <div style="height: 100%; padding: 16px; border: 1px solid rgba(148, 163, 184, 0.25); border-radius: 14px; background: rgba(255, 255, 255, 0.02);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                <div>
                    <div style="font-size: 14px; font-weight: 800;">Index Readiness</div>
                    <div style="margin-top: 3px; color: #64748b; font-size: 11px;">Technical status before Google URL inspection</div>
                </div>

                <span style="display: inline-flex; padding: 6px 10px; border-radius: 9999px; background: {$statusColor}18; color: {$statusColor}; font-size: 12px; font-weight: 800;">
                    {$statusLabel}
                </span>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px;">
                <span style="padding: 6px 9px; border-radius: 9999px; background: {$sitemapBackground}; color: {$sitemapColor}; font-size: 11px; font-weight: 700;">
                    Sitemap: {$sitemapLabel}
                </span>

                <span style="padding: 6px 9px; border-radius: 9999px; background: {$canonicalBackground}; color: {$canonicalColor}; font-size: 11px; font-weight: 700;">
                    Canonical: {$canonicalLabel}
                </span>

                <span style="padding: 6px 9px; border-radius: 9999px; background: {$robotsBackground}; color: {$robotsColor}; font-size: 11px; font-weight: 700;">
                    Robots: {$robots}
                </span>
            </div>

            <div style="margin-top: 14px; padding: 10px 12px; border-radius: 9px; background: rgba(148, 163, 184, 0.10);">
                <div style="font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .04em;">Page URL</div>
                <div style="margin-top: 4px; color: #0f172a; font-size: 12px; overflow-wrap: anywhere;">{$pageUrl}</div>
            </div>

            <div style="display: grid; gap: 8px; margin-top: 14px;">
                {$issuesHtml}
            </div>
        </div>
        HTML;

        return new HtmlString($html);
    }

    /**
     * @param array<string, mixed> $analysis
     */
    private function renderSearchConsoleCard(array $analysis): HtmlString
    {
        $indexing = is_array($analysis['indexing'] ?? null)
            ? $analysis['indexing']
            : [];

        $pageUrl = trim((string) ($indexing['page_url'] ?? ''));
        $property = trim((string) config(
            'services.search_console.property',
            config('app.url'),
        ));

        $connection = $this->searchConsoleConnectionData();
        $connected = (bool) ($connection['connected'] ?? false);

        $statusLabel = $connected
            ? 'Connected'
            : 'Connection Required';

        $statusColor = $connected
            ? '#166534'
            : '#92400e';

        $statusBackground = $connected
            ? '#dcfce7'
            : '#fef3c7';

        $connectedAt = trim((string) ($connection['connected_at'] ?? ''));
        $connectedMeta = $connectedAt !== ''
            ? '<div style="margin-top: 3px; font-size: 10px; color: #64748b;">Connected: '
                . e($connectedAt)
                . '</div>'
            : '';

        $liveUrl = e($pageUrl !== '' ? $pageUrl : rtrim((string) config('app.url'), '/'));
        $connectUrl = e(route('search-console.connect'));
        $disconnectUrl = e(route('search-console.disconnect'));
        $csrf = e(csrf_token());

        $searchConsoleUrl = e(
            'https://search.google.com/search-console'
            . '?resource_id=' . urlencode($property)
        );

        $safeProperty = e($property);
        $copyPageUrl = e($pageUrl);

        $primaryAction = $connected
            ? <<<HTML
                <form method="POST" action="{$disconnectUrl}" style="margin: 0;">
                    <input type="hidden" name="_token" value="{$csrf}">
                    <button type="submit"
                        style="width: 100%; border: 0; cursor: pointer; padding: 9px 11px; border-radius: 8px; background: #dc2626; color: white; text-align: center; font-size: 12px; font-weight: 700;">
                        Disconnect Search Console
                    </button>
                </form>
            HTML
            : <<<HTML
                <a href="{$connectUrl}"
                    style="display: block; padding: 9px 11px; border-radius: 8px; background: #2563eb; color: white; text-decoration: none; text-align: center; font-size: 12px; font-weight: 700;">
                    Connect Google Search Console
                </a>
            HTML;

        $html = <<<HTML
        <div style="height: 100%; min-height: 260px; padding: 16px; border: 1px solid rgba(148, 163, 184, 0.25); border-radius: 14px; background: rgba(255, 255, 255, 0.02);">
            <div style="font-size: 14px; font-weight: 800;">Google Search Console</div>
            <div style="margin-top: 4px; color: #64748b; font-size: 11px; line-height: 1.5;">
                Connect the verified Google account once. Uske baad connection status admin panel me yahin dikhega.
            </div>

            <div style="margin-top: 14px; padding: 10px; border-radius: 9px; background: {$statusBackground};">
                <div style="font-size: 10px; color: #64748b;">CURRENT STATUS</div>
                <div style="margin-top: 4px; font-size: 13px; font-weight: 800; color: {$statusColor};">{$statusLabel}</div>
                {$connectedMeta}
                <div style="margin-top: 5px; font-size: 10px; color: #64748b; overflow-wrap: anywhere;">{$safeProperty}</div>
            </div>

            <div style="display: grid; gap: 8px; margin-top: 14px;">
                {$primaryAction}

                <a href="{$liveUrl}" target="_blank" rel="noopener noreferrer"
                    style="display: block; padding: 9px 11px; border-radius: 8px; background: #0f172a; color: white; text-decoration: none; text-align: center; font-size: 12px; font-weight: 700;">
                    Open Live Page
                </a>

                <button type="button"
                    onclick="navigator.clipboard.writeText('{$copyPageUrl}').then(() => { this.innerText = 'Page URL Copied'; setTimeout(() => this.innerText = 'Copy Page URL', 1600); })"
                    style="width: 100%; cursor: pointer; padding: 9px 11px; border-radius: 8px; border: 1px solid #cbd5e1; background: white; color: #0f172a; text-align: center; font-size: 12px; font-weight: 700;">
                    Copy Page URL
                </button>

                <a href="{$searchConsoleUrl}" target="_blank" rel="noopener noreferrer"
                    style="display: block; padding: 9px 11px; border-radius: 8px; border: 1px solid #cbd5e1; color: #0f172a; text-decoration: none; text-align: center; font-size: 12px; font-weight: 700;">
                    Open Search Console
                </a>
            </div>
        </div>
        HTML;

        return new HtmlString($html);
    }

    /**
     * @return array{connected: bool, connected_at?: string}
     */
    private function searchConsoleConnectionData(): array
    {
        $encrypted = Cache::get('google.search_console.oauth_tokens');

        if (! is_string($encrypted) || $encrypted === '') {
            return ['connected' => false];
        }

        try {
            $decoded = json_decode(
                Crypt::decryptString($encrypted),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );

            if (! is_array($decoded)) {
                return ['connected' => false];
            }

            $hasToken = filled($decoded['access_token'] ?? null)
                || filled($decoded['refresh_token'] ?? null);

            return [
                'connected' => $hasToken,
                'connected_at' => (string) ($decoded['connected_at'] ?? ''),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return ['connected' => false];
        }
    }

    /**
     * @param array<string, mixed> $analysis
     */
    private function renderScoreCard(array $analysis): HtmlString
    {
        $score = max(
            0,
            min(100, (int) ($analysis['score'] ?? 0)),
        );

        $statusLabel = e(
            (string) ($analysis['status_label'] ?? 'Not Analyzed'),
        );

        $status = (string) ($analysis['status'] ?? 'poor');

        $accentColor = match ($status) {
            'excellent' => '#16a34a',
            'good' => '#2563eb',
            'needs_improvement' => '#d97706',
            default => '#dc2626',
        };

        $trackColor = '#e5e7eb';

        $circleDegrees = (int) round(($score / 100) * 360);

        $html = <<<HTML
        <div
            style="
                height: 100%;
                min-height: 190px;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                border: 1px solid rgba(148, 163, 184, 0.25);
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.02);
            "
        >
            <div style="text-align: center;">
                <div
                    style="
                        width: 112px;
                        height: 112px;
                        margin: 0 auto;
                        border-radius: 9999px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background:
                            conic-gradient(
                                {$accentColor} {$circleDegrees}deg,
                                {$trackColor} {$circleDegrees}deg
                            );
                    "
                >
                    <div
                        style="
                            width: 88px;
                            height: 88px;
                            border-radius: 9999px;
                            background: white;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            flex-direction: column;
                            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.20);
                        "
                    >
                        <strong
                            style="
                                color: {$accentColor};
                                font-size: 30px;
                                line-height: 1;
                            "
                        >
                            {$score}
                        </strong>

                        <span
                            style="
                                color: #64748b;
                                font-size: 11px;
                                margin-top: 4px;
                            "
                        >
                            out of 100
                        </span>
                    </div>
                </div>

                <div
                    style="
                        margin-top: 14px;
                        font-size: 15px;
                        font-weight: 700;
                        color: {$accentColor};
                    "
                >
                    {$statusLabel}
                </div>

                <div
                    style="
                        margin-top: 4px;
                        color: #64748b;
                        font-size: 12px;
                    "
                >
                    Overall SEO Score
                </div>
            </div>
        </div>
        HTML;

        return new HtmlString($html);
    }

    /**
     * @param array<string, mixed> $analysis
     */
    private function renderStatistics(array $analysis): HtmlString
    {
        $wordCount = number_format(
            (int) ($analysis['word_count'] ?? 0),
        );

        $readingTime = (int) ($analysis['reading_time'] ?? 0);

        $keywordDensity = number_format(
            (float) ($analysis['keyword_density'] ?? 0),
            2,
        );

        $readabilityScore = max(
            0,
            min(
                100,
                (int) ($analysis['readability_score'] ?? 0),
            ),
        );

        $passedPercentage = max(
            0,
            min(
                100,
                (int) ($analysis['passed_percentage'] ?? 0),
            ),
        );

        $summary = is_array($analysis['summary'] ?? null)
            ? $analysis['summary']
            : [];

        $passed = (int) ($summary['passed'] ?? 0);
        $errors = (int) ($summary['errors'] ?? 0);
        $warnings = (int) ($summary['warnings'] ?? 0);
        $info = (int) ($summary['info'] ?? 0);

        $html = <<<HTML
        <div
            style="
                min-height: 190px;
                padding: 16px;
                border: 1px solid rgba(148, 163, 184, 0.25);
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.02);
            "
        >
            <div
                style="
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                    gap: 10px;
                "
            >
                {$this->statCard(
                    label: 'Word Count',
                    value: $wordCount,
                    description: 'Content words',
                )}

                {$this->statCard(
                    label: 'Reading Time',
                    value: $readingTime . ' min',
                    description: 'Approximate',
                )}

                {$this->statCard(
                    label: 'Keyword Density',
                    value: $keywordDensity . '%',
                    description: 'Recommended 0.5–2.5%',
                )}

                {$this->statCard(
                    label: 'Readability',
                    value: $readabilityScore . '/100',
                    description: 'Content clarity',
                )}
            </div>

            <div style="margin-top: 16px;">
                <div
                    style="
                        display: flex;
                        justify-content: space-between;
                        gap: 10px;
                        margin-bottom: 7px;
                        font-size: 12px;
                    "
                >
                    <span style="font-weight: 600;">
                        Passed checks
                    </span>

                    <span style="font-weight: 700;">
                        {$passedPercentage}%
                    </span>
                </div>

                <div
                    style="
                        width: 100%;
                        height: 8px;
                        overflow: hidden;
                        border-radius: 9999px;
                        background: #e5e7eb;
                    "
                >
                    <div
                        style="
                            width: {$passedPercentage}%;
                            height: 100%;
                            border-radius: 9999px;
                            background: #16a34a;
                        "
                    ></div>
                </div>
            </div>

            <div
                style="
                    display: flex;
                    flex-wrap: wrap;
                    gap: 8px;
                    margin-top: 14px;
                "
            >
                {$this->summaryBadge(
                    label: 'Passed',
                    count: $passed,
                    background: '#dcfce7',
                    foreground: '#166534',
                )}

                {$this->summaryBadge(
                    label: 'Errors',
                    count: $errors,
                    background: '#fee2e2',
                    foreground: '#991b1b',
                )}

                {$this->summaryBadge(
                    label: 'Warnings',
                    count: $warnings,
                    background: '#fef3c7',
                    foreground: '#92400e',
                )}

                {$this->summaryBadge(
                    label: 'Info',
                    count: $info,
                    background: '#dbeafe',
                    foreground: '#1e40af',
                )}
            </div>
        </div>
        HTML;

        return new HtmlString($html);
    }

    private function statCard(
        string $label,
        string $value,
        string $description,
    ): string {
        $safeLabel = e($label);
        $safeValue = e($value);
        $safeDescription = e($description);

        return <<<HTML
        <div
            style="
                padding: 12px;
                border-radius: 10px;
                background: rgba(148, 163, 184, 0.10);
                border: 1px solid rgba(148, 163, 184, 0.16);
            "
        >
            <div
                style="
                    font-size: 11px;
                    color: #64748b;
                "
            >
                {$safeLabel}
            </div>

            <div
                style="
                    margin-top: 4px;
                    font-size: 18px;
                    font-weight: 700;
                "
            >
                {$safeValue}
            </div>

            <div
                style="
                    margin-top: 2px;
                    font-size: 10px;
                    color: #94a3b8;
                "
            >
                {$safeDescription}
            </div>
        </div>
        HTML;
    }

    private function summaryBadge(
        string $label,
        int $count,
        string $background,
        string $foreground,
    ): string {
        $safeLabel = e($label);

        return <<<HTML
        <span
            style="
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 9px;
                border-radius: 9999px;
                background: {$background};
                color: {$foreground};
                font-size: 11px;
                font-weight: 700;
            "
        >
            {$safeLabel}: {$count}
        </span>
        HTML;
    }


    private function renderSchemaPreview(Get $get): HtmlString
    {
        $enabled = (bool) $get('seo_analysis.schema.enabled');
        $type = trim((string) (
            $get('seo_analysis.schema.type')
            ?: 'auto'
        ));
        $json = trim((string) $get('seo_analysis.schema.json'));
        $faqs = $get('seo_analysis.faqs');
        $faqCount = is_array($faqs)
            ? count(array_filter(
                $faqs,
                static fn (mixed $faq): bool =>
                    is_array($faq)
                    && filled($faq['question'] ?? null)
                    && filled($faq['answer'] ?? null)
            ))
            : 0;

        if (! $enabled) {
            return new HtmlString(
                '<div style="padding:12px;border:1px solid #cbd5e1;border-radius:10px;background:#f8fafc;color:#475569;font-size:12px;">'
                . 'Manual schema disabled hai. Frontend automatic page schema use karega.'
                . ($faqCount > 0
                    ? ' Saved FAQs: <strong>' . $faqCount . '</strong>.'
                    : '')
                . '</div>'
            );
        }

        if ($json === '') {
            return new HtmlString(
                '<div style="padding:12px;border:1px solid #fde68a;border-radius:10px;background:#fffbeb;color:#92400e;font-size:12px;">'
                . 'Manual schema enabled hai, lekin JSON blank hai. Type: <strong>'
                . e($type)
                . '</strong>. Blank rehne par automatic schema fallback use hoga.'
                . '</div>'
            );
        }

        try {
            $decoded = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (! is_array($decoded)) {
                throw new \JsonException('Root value object ya array nahi hai.');
            }

            $detectedType = data_get($decoded, '@type');

            if ($detectedType === null && array_is_list($decoded)) {
                $detectedType = collect($decoded)
                    ->pluck('@type')
                    ->filter()
                    ->implode(', ');
            }

            $detectedType = filled($detectedType)
                ? (string) $detectedType
                : $type;

            return new HtmlString(
                '<div style="padding:12px;border:1px solid #bbf7d0;border-radius:10px;background:#f0fdf4;color:#166534;font-size:12px;">'
                . '✓ Valid JSON-LD. Detected type: <strong>'
                . e($detectedType)
                . '</strong>. Saved FAQs: <strong>'
                . $faqCount
                . '</strong>.'
                . '</div>'
            );
        } catch (\JsonException $exception) {
            return new HtmlString(
                '<div style="padding:12px;border:1px solid #fecaca;border-radius:10px;background:#fef2f2;color:#991b1b;font-size:12px;">'
                . '✕ Invalid JSON: '
                . e($exception->getMessage())
                . '</div>'
            );
        }
    }

    private function renderGooglePreview(Get $get): HtmlString
    {
        $title = trim(
            (string) (
                $get('meta_title')
                ?: $get('name')
                ?: 'Page title will appear here'
            ),
        );

        $description = trim(
            (string) (
                $get('meta_description')
                ?: 'Meta description will appear here after you enter it.'
            ),
        );

        $slug = trim(
            (string) (
                $get('slug')
                ?: Str::slug((string) $get('name'))
            ),
        );

        $baseUrl = rtrim(
            (string) config('app.url', 'https://www.duracabs.com'),
            '/',
        );

        $displayUrl = $baseUrl . '/route/' . ltrim($slug, '/');

        $safeTitle = e(
            Str::limit($title, 70, '...'),
        );

        $safeDescription = e(
            Str::limit($description, 170, '...'),
        );

        $safeUrl = e($displayUrl);

        $html = <<<HTML
        <div
            style="
                max-width: 720px;
                padding: 18px;
                border: 1px solid rgba(148, 163, 184, 0.28);
                border-radius: 12px;
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
            "
        >
            <div
                style="
                    color: #202124;
                    font-size: 14px;
                    line-height: 1.4;
                    overflow-wrap: anywhere;
                "
            >
                {$safeUrl}
            </div>

            <div
                style="
                    margin-top: 5px;
                    color: #1a0dab;
                    font-size: 20px;
                    line-height: 1.3;
                    font-weight: 400;
                "
            >
                {$safeTitle}
            </div>

            <div
                style="
                    margin-top: 5px;
                    color: #4d5156;
                    font-size: 14px;
                    line-height: 1.55;
                "
            >
                {$safeDescription}
            </div>
        </div>
        HTML;

        return new HtmlString($html);
    }

    /**
     * @param array<string, mixed> $analysis
     */
    private function renderIssues(array $analysis): HtmlString
    {
        $issues = is_array($analysis['issues'] ?? null)
            ? $analysis['issues']
            : [];

        if ($issues === []) {
            return new HtmlString(
                <<<HTML
                <div
                    style="
                        padding: 16px;
                        border-radius: 10px;
                        border: 1px dashed rgba(148, 163, 184, 0.45);
                        color: #64748b;
                        font-size: 13px;
                    "
                >
                    SEO fields aur page content enter karne ke baad analysis yahan dikhega.
                </div>
                HTML,
            );
        }

        usort(
            $issues,
            static function (array $first, array $second): int {
                $weights = [
                    'error' => 1,
                    'warning' => 2,
                    'info' => 3,
                    'success' => 4,
                ];

                $firstType = $first['passed'] ?? false
                    ? 'success'
                    : (string) ($first['type'] ?? 'warning');

                $secondType = $second['passed'] ?? false
                    ? 'success'
                    : (string) ($second['type'] ?? 'warning');

                return ($weights[$firstType] ?? 9)
                    <=> ($weights[$secondType] ?? 9);
            },
        );

        $cards = '';

        foreach ($issues as $issue) {
            if (! is_array($issue)) {
                continue;
            }

            $passed = (bool) ($issue['passed'] ?? false);

            $type = $passed
                ? 'success'
                : (string) ($issue['type'] ?? 'warning');

            $title = e(
                (string) ($issue['title'] ?? 'SEO suggestion'),
            );

            $message = e(
                (string) ($issue['message'] ?? ''),
            );

            $suggestion = e(
                (string) ($issue['suggestion'] ?? ''),
            );

            [$icon, $background, $border, $foreground] = match ($type) {
                'error' => [
                    '✕',
                    '#fef2f2',
                    '#fecaca',
                    '#991b1b',
                ],

                'warning' => [
                    '!',
                    '#fffbeb',
                    '#fde68a',
                    '#92400e',
                ],

                'info' => [
                    'i',
                    '#eff6ff',
                    '#bfdbfe',
                    '#1e40af',
                ],

                default => [
                    '✓',
                    '#f0fdf4',
                    '#bbf7d0',
                    '#166534',
                ],
            };

            $suggestionHtml = $suggestion !== ''
                ? <<<HTML
                    <div
                        style="
                            margin-top: 7px;
                            color: {$foreground};
                            font-size: 12px;
                            line-height: 1.5;
                        "
                    >
                        <strong>Suggestion:</strong> {$suggestion}
                    </div>
                HTML
                : '';

            $cards .= <<<HTML
            <div
                style="
                    display: flex;
                    align-items: flex-start;
                    gap: 11px;
                    padding: 13px;
                    border: 1px solid {$border};
                    border-radius: 10px;
                    background: {$background};
                "
            >
                <div
                    style="
                        width: 25px;
                        height: 25px;
                        flex: 0 0 25px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 9999px;
                        background: {$foreground};
                        color: white;
                        font-size: 12px;
                        font-weight: 800;
                    "
                >
                    {$icon}
                </div>

                <div style="min-width: 0;">
                    <div
                        style="
                            color: {$foreground};
                            font-size: 13px;
                            font-weight: 700;
                            line-height: 1.4;
                        "
                    >
                        {$title}
                    </div>

                    <div
                        style="
                            margin-top: 3px;
                            color: #475569;
                            font-size: 12px;
                            line-height: 1.5;
                        "
                    >
                        {$message}
                    </div>

                    {$suggestionHtml}
                </div>
            </div>
            HTML;
        }

        $html = <<<HTML
        <div
            style="
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
                gap: 10px;
            "
        >
            {$cards}
        </div>
        HTML;

        return new HtmlString($html);
    }

    private function getTitleHelperText(string $title): string
    {
        $length = Str::length(trim($title));

        return match (true) {
            $length === 0 => 'SEO title add karein. Recommended length 30–60 characters.',
            $length < 30 => "Title chhota hai: {$length} characters. Recommended 30–60.",
            $length <= 60 => "Title length achhi hai: {$length} characters.",
            default => "Title lamba hai: {$length} characters. Google ise cut kar sakta hai.",
        };
    }

    private function getDescriptionHelperText(string $description): string
    {
        $length = Str::length(trim($description));

        return match (true) {
            $length === 0 => 'Meta description add karein. Recommended length 120–160 characters.',
            $length < 120 => "Description chhoti hai: {$length} characters. Recommended 120–160.",
            $length <= 160 => "Description length achhi hai: {$length} characters.",
            default => "Description lambi hai: {$length} characters. Google ise truncate kar sakta hai.",
        };
    }
}