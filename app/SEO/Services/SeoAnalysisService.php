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

    public function analyzeToArray(array $data): array
    {
        $normalizedData = [
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

        $result = $this->analyze($normalizedData);

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
        ];
    }
}