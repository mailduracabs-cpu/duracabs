<?php

namespace App\SEO\Services;

use App\SEO\Contracts\SeoRuleInterface;
use App\SEO\DTO\SeoResult;
use App\SEO\Rules\HeadingRule;
use App\SEO\Rules\KeywordRule;
use App\SEO\Rules\MetaRule;
use App\SEO\Rules\ReadabilityRule;
use App\SEO\Rules\SlugRule;
use App\SEO\Rules\TitleRule;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Throwable;

class SeoAnalysisService
{
    /**
     * @var array<int, SeoRuleInterface>
     */
    private array $rules;

    public function __construct(
        private readonly SeoScoreCalculator $scoreCalculator,
    ) {
        $this->rules = [
            app(TitleRule::class),
            app(MetaRule::class),
            app(SlugRule::class),
            app(KeywordRule::class),
            app(HeadingRule::class),
            app(ReadabilityRule::class),
        ];
    }

    public function analyze(array $data): SeoResult
    {
        $result = new SeoResult();

        foreach ($this->rules as $rule) {
            try {
                $rule->analyze($data, $result);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $this->scoreCalculator->calculate($result);

        return $result;
    }

    public function addRule(SeoRuleInterface $rule): self
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * @param array<int, SeoRuleInterface> $rules
     */
    public function setRules(array $rules): self
    {
        $this->rules = array_values(array_filter(
            $rules,
            fn (mixed $rule): bool => $rule instanceof SeoRuleInterface,
        ));

        return $this;
    }

    /**
     * @return array<int, SeoRuleInterface>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Run the existing content analysis and append local index-readiness data.
     *
     * Important:
     * - "index_ready" means the page is technically eligible for indexing.
     * - It does not claim that Google has already indexed the page.
     * - Actual Google status will be added later through Search Console.
     */
    public function analyzeToArray(array $data): array
    {
        $normalizedData = $this->normalizeData($data);
        $result = $this->analyze($normalizedData);
        $indexing = $this->analyzeIndexReadiness($data);

        return [
            'score' => $result->score,
            'status' => $this->scoreCalculator->getStatus($result->score),
            'status_label' => $this->scoreCalculator->getStatusLabel(
                $result->score
            ),
            'status_color' => $this->scoreCalculator->getStatusColor(
                $result->score
            ),

            'word_count' => $result->wordCount,
            'reading_time' => $result->readingTime,
            'keyword_density' => $result->keywordDensity,
            'readability_score' => $result->readabilityScore,

            'passed_percentage' => $this->scoreCalculator
                ->getPassedPercentage($result),

            'summary' => $this->scoreCalculator->getSummary($result),

            'issues' => array_map(
                static fn ($issue): array => [
                    'type' => $issue->type,
                    'title' => $issue->title,
                    'message' => $issue->message,
                    'suggestion' => $issue->suggestion,
                    'passed' => $issue->passed,
                ],
                $result->issues,
            ),

            'indexing' => $indexing,
            'index_ready' => $indexing['index_ready'],
            'index_status' => $indexing['status'],
            'index_status_label' => $indexing['status_label'],
            'index_status_color' => $indexing['status_color'],
            'page_url' => $indexing['page_url'],
            'canonical_url' => $indexing['canonical_url'],
            'robots' => $indexing['robots'],
            'sitemap_eligible' => $indexing['sitemap_eligible'],
        ];
    }

    /**
     * Analyze whether a page is locally ready for Google indexing.
     *
     * @return array{
     *     index_ready: bool,
     *     status: string,
     *     status_label: string,
     *     status_color: string,
     *     page_url: string,
     *     canonical_url: string,
     *     canonical_status: string,
     *     canonical_matches_page: bool,
     *     robots: string,
     *     robots_index: bool,
     *     robots_follow: bool,
     *     is_active: bool,
     *     is_published: bool,
     *     sitemap_eligible: bool,
     *     blockers: array<int, array<string, string>>,
     *     warnings: array<int, array<string, string>>,
     *     checks: array<string, bool>
     * }
     */
    public function analyzeIndexReadiness(array $data): array
    {
        $pageUrl = $this->resolvePageUrl($data);
        $canonicalUrl = $this->resolveCanonicalUrl($data, $pageUrl);
        $robots = $this->resolveRobots($data);

        $robotsIndex = $this->robotsAllowsIndexing($data, $robots);
        $robotsFollow = $this->robotsAllowsFollowing($data, $robots);
        $isActive = $this->resolveActiveStatus($data);
        $isPublished = $this->resolvePublishedStatus($data);
        $hasSlug = filled(trim((string) ($data['slug'] ?? '')));
        $hasTitle = filled(trim((string) (
            $data['meta_title']
            ?? $data['title']
            ?? $data['name']
            ?? ''
        )));
        $hasDescription = filled(trim((string) (
            $data['meta_description']
            ?? ''
        )));
        $hasContent = filled(trim(strip_tags((string) (
            $data['description']
            ?? $data['content']
            ?? ''
        ))));
        $hasCanonical = filled($canonicalUrl);
        $canonicalMatchesPage = $this->urlsMatch(
            $canonicalUrl,
            $pageUrl
        );

        $blockers = [];
        $warnings = [];

        if (! $isActive) {
            $blockers[] = $this->message(
                'inactive',
                'Page is inactive',
                'Activate the page before adding it to the sitemap.'
            );
        }

        if (! $isPublished) {
            $blockers[] = $this->message(
                'not_published',
                'Page is not published',
                'Publish the page or set a valid publish date.'
            );
        }

        if (! $robotsIndex) {
            $blockers[] = $this->message(
                'noindex',
                'Robots directive blocks indexing',
                'Change the robots setting to index,follow when this page is ready.'
            );
        }

        if (! $hasSlug) {
            $blockers[] = $this->message(
                'missing_slug',
                'Page URL is missing',
                'Add a valid and unique slug.'
            );
        }

        if (! $hasTitle) {
            $blockers[] = $this->message(
                'missing_title',
                'SEO title is missing',
                'Add a concise and unique SEO title.'
            );
        }

        if (! $hasContent) {
            $blockers[] = $this->message(
                'missing_content',
                'Page content is missing',
                'Add useful, original content before requesting indexing.'
            );
        }

        if (! $hasDescription) {
            $warnings[] = $this->message(
                'missing_meta_description',
                'Meta description is missing',
                'Add a clear meta description to improve the search snippet.'
            );
        }

        if (! $hasCanonical) {
            $warnings[] = $this->message(
                'missing_canonical',
                'Canonical URL is missing',
                'Set the page URL as the canonical URL unless another canonical is intended.'
            );
        } elseif (! $canonicalMatchesPage) {
            $warnings[] = $this->message(
                'canonical_mismatch',
                'Canonical points to another URL',
                'Confirm that this page should canonicalize to the configured URL.'
            );
        }

        if (! $robotsFollow) {
            $warnings[] = $this->message(
                'nofollow',
                'Links are marked nofollow',
                'Use follow unless there is a specific reason not to crawl links on this page.'
            );
        }

        $indexReady = $blockers === [];
        $sitemapEligible = $indexReady && $robotsIndex;

        [$status, $statusLabel, $statusColor] = $this->indexStatus(
            indexReady: $indexReady,
            robotsIndex: $robotsIndex,
            isActive: $isActive,
            isPublished: $isPublished,
            warningCount: count($warnings)
        );

        return [
            'index_ready' => $indexReady,
            'status' => $status,
            'status_label' => $statusLabel,
            'status_color' => $statusColor,

            'page_url' => $pageUrl,
            'canonical_url' => $canonicalUrl,
            'canonical_status' => ! $hasCanonical
                ? 'missing'
                : ($canonicalMatchesPage ? 'self' : 'different'),
            'canonical_matches_page' => $canonicalMatchesPage,

            'robots' => $robots,
            'robots_index' => $robotsIndex,
            'robots_follow' => $robotsFollow,

            'is_active' => $isActive,
            'is_published' => $isPublished,
            'sitemap_eligible' => $sitemapEligible,

            'blockers' => $blockers,
            'warnings' => $warnings,

            'checks' => [
                'has_slug' => $hasSlug,
                'has_title' => $hasTitle,
                'has_meta_description' => $hasDescription,
                'has_content' => $hasContent,
                'has_canonical' => $hasCanonical,
                'canonical_matches_page' => $canonicalMatchesPage,
                'robots_allow_index' => $robotsIndex,
                'robots_allow_follow' => $robotsFollow,
                'is_active' => $isActive,
                'is_published' => $isPublished,
            ],
        ];
    }

    /**
     * Compact local status for Filament table columns and badges.
     */
    public function indexBadge(array $data): array
    {
        $analysis = $this->analyzeIndexReadiness($data);

        return [
            'status' => $analysis['status'],
            'label' => $analysis['status_label'],
            'color' => $analysis['status_color'],
            'ready' => $analysis['index_ready'],
            'sitemap_eligible' => $analysis['sitemap_eligible'],
        ];
    }

    private function normalizeData(array $data): array
    {
        return [
            'title' => $data['meta_title']
                ?? $data['title']
                ?? $data['name']
                ?? '',

            'meta_title' => $data['meta_title']
                ?? $data['title']
                ?? $data['name']
                ?? '',

            'name' => $data['name'] ?? '',

            'meta_description' => $data['meta_description'] ?? '',

            'slug' => $data['slug'] ?? '',

            'focus_keyword' => $data['focus_keyword'] ?? '',

            'description' => $data['description']
                ?? $data['content']
                ?? '',

            'content' => $data['content']
                ?? $data['description']
                ?? '',
        ];
    }

    private function resolvePageUrl(array $data): string
    {
        $explicitUrl = trim((string) (
            $data['page_url']
            ?? $data['url']
            ?? ''
        ));

        if ($explicitUrl !== '') {
            return $this->normalizeUrl($explicitUrl);
        }

        $slug = trim((string) ($data['slug'] ?? ''), '/');
        $baseUrl = rtrim(
            (string) config(
                'services.search_console.property',
                config('app.url')
            ),
            '/'
        );

        if ($slug === '') {
            return $baseUrl;
        }

        return $this->normalizeUrl($baseUrl . '/' . $slug);
    }

    private function resolveCanonicalUrl(
        array $data,
        string $pageUrl
    ): string {
        $canonical = trim((string) (
            $data['canonical_url']
            ?? $data['canonical']
            ?? ''
        ));

        if ($canonical === '') {
            // Fallback to the resolved public page URL.
            return $pageUrl;
        }

        if (Str::startsWith($canonical, ['/'])) {
            return $this->normalizeUrl(
                rtrim((string) config('app.url'), '/')
                . '/'
                . ltrim($canonical, '/')
            );
        }

        return $this->normalizeUrl($canonical);
    }

    private function resolveRobots(array $data): string
    {
        $explicitRobots = trim(strtolower((string) (
            $data['robots']
            ?? ''
        )));

        if ($explicitRobots !== '') {
            return str_replace(' ', '', $explicitRobots);
        }

        $index = $this->booleanValue(
            $data['robots_index'] ?? true,
            true
        );

        $follow = $this->booleanValue(
            $data['robots_follow'] ?? true,
            true
        );

        return ($index ? 'index' : 'noindex')
            . ','
            . ($follow ? 'follow' : 'nofollow');
    }

    private function robotsAllowsIndexing(
        array $data,
        string $robots
    ): bool {
        if (array_key_exists('robots_index', $data)) {
            return $this->booleanValue(
                $data['robots_index'],
                true
            );
        }

        return ! Str::contains($robots, 'noindex');
    }

    private function robotsAllowsFollowing(
        array $data,
        string $robots
    ): bool {
        if (array_key_exists('robots_follow', $data)) {
            return $this->booleanValue(
                $data['robots_follow'],
                true
            );
        }

        return ! Str::contains($robots, 'nofollow');
    }

    private function resolveActiveStatus(array $data): bool
    {
        foreach (['is_active', 'active', 'published'] as $key) {
            if (array_key_exists($key, $data)) {
                return $this->booleanValue($data[$key], true);
            }
        }

        return true;
    }

    private function resolvePublishedStatus(array $data): bool
    {
        if (! array_key_exists('published_at', $data)) {
            return true;
        }

        $publishedAt = $data['published_at'];

        if ($publishedAt === null || $publishedAt === '') {
            return true;
        }

        try {
            if ($publishedAt instanceof CarbonInterface) {
                return $publishedAt->isPast()
                    || $publishedAt->isCurrentSecond();
            }

            return now()->greaterThanOrEqualTo(
                (string) $publishedAt
            );
        } catch (Throwable) {
            return false;
        }
    }

    private function urlsMatch(
        string $first,
        string $second
    ): bool {
        if ($first === '' || $second === '') {
            return false;
        }

        return rtrim($this->normalizeUrl($first), '/')
            === rtrim($this->normalizeUrl($second), '/');
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (! Str::startsWith($url, ['http://', 'https://'])) {
            $url = 'https://' . ltrim($url, '/');
        }

        return rtrim($url, '/');
    }

    private function booleanValue(
        mixed $value,
        bool $default
    ): bool {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }

        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? $default;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function indexStatus(
        bool $indexReady,
        bool $robotsIndex,
        bool $isActive,
        bool $isPublished,
        int $warningCount
    ): array {
        if (! $isActive) {
            return ['inactive', 'Inactive', 'gray'];
        }

        if (! $isPublished) {
            return ['scheduled', 'Not Published', 'warning'];
        }

        if (! $robotsIndex) {
            return ['noindex', 'Noindex', 'danger'];
        }

        if (! $indexReady) {
            return ['blocked', 'Needs Attention', 'danger'];
        }

        if ($warningCount > 0) {
            return ['ready_with_warnings', 'Index Ready', 'warning'];
        }

        return ['ready', 'Index Ready', 'success'];
    }

    /**
     * @return array{code: string, title: string, recommendation: string}
     */
    private function message(
        string $code,
        string $title,
        string $recommendation
    ): array {
        return [
            'code' => $code,
            'title' => $title,
            'recommendation' => $recommendation,
        ];
    }
}