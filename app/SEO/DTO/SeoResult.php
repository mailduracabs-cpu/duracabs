<?php

namespace App\SEO\DTO;

class SeoResult
{
    public int $score = 100;

    /** @var SeoIssue[] */
    public array $issues = [];

    public int $wordCount = 0;

    public int $readingTime = 0;

    public float $keywordDensity = 0;

    public function addIssue(SeoIssue $issue): void
    {
        $this->issues[] = $issue;

        if (! $issue->passed) {
            $this->score -= 5;
        }

        if ($this->score < 0) {
            $this->score = 0;
        }
    }
}