<?php

namespace App\SEO\Services;

use App\SEO\DTO\SeoIssue;
use App\SEO\DTO\SeoResult;

class SeoScoreCalculator
{
    /**
     * Final SEO score calculate karke SeoResult me set karta hai.
     */
    public function calculate(SeoResult $result): int
    {
        $maximumScore = 100;
        $deduction = 0;

        foreach ($result->issues as $issue) {
            if (! $issue instanceof SeoIssue || $issue->passed) {
                continue;
            }

            $deduction += $this->getIssueDeduction($issue);
        }

        $score = max(0, min($maximumScore, $maximumScore - $deduction));

        $result->score = $score;

        return $score;
    }

    /**
     * Issue severity ke according marks deduct hote hain.
     */
    private function getIssueDeduction(SeoIssue $issue): int
    {
        return match ($issue->type) {
            'error' => 8,
            'warning' => 5,
            'info' => 2,
            default => 3,
        };
    }

    /**
     * Score ke basis par SEO status return karta hai.
     */
    public function getStatus(int $score): string
    {
        return match (true) {
            $score >= 90 => 'excellent',
            $score >= 75 => 'good',
            $score >= 50 => 'needs_improvement',
            default => 'poor',
        };
    }

    /**
     * Admin panel ke liye readable status label.
     */
    public function getStatusLabel(int $score): string
    {
        return match ($this->getStatus($score)) {
            'excellent' => 'Excellent',
            'good' => 'Good',
            'needs_improvement' => 'Needs Improvement',
            default => 'Poor',
        };
    }

    /**
     * Filament badge ya frontend indicator ke liye color.
     */
    public function getStatusColor(int $score): string
    {
        return match ($this->getStatus($score)) {
            'excellent' => 'success',
            'good' => 'primary',
            'needs_improvement' => 'warning',
            default => 'danger',
        };
    }

    /**
     * Passed checks ka percentage.
     */
    public function getPassedPercentage(SeoResult $result): int
    {
        $totalIssues = count($result->issues);

        if ($totalIssues === 0) {
            return 0;
        }

        $passedIssues = count(array_filter(
            $result->issues,
            fn ($issue): bool => $issue instanceof SeoIssue && $issue->passed,
        ));

        return (int) round(($passedIssues / $totalIssues) * 100);
    }

    /**
     * Issues ki summary generate karta hai.
     *
     * @return array{
     *     total: int,
     *     passed: int,
     *     errors: int,
     *     warnings: int,
     *     info: int
     * }
     */
    public function getSummary(SeoResult $result): array
    {
        $summary = [
            'total' => count($result->issues),
            'passed' => 0,
            'errors' => 0,
            'warnings' => 0,
            'info' => 0,
        ];

        foreach ($result->issues as $issue) {
            if (! $issue instanceof SeoIssue) {
                continue;
            }

            if ($issue->passed) {
                $summary['passed']++;

                continue;
            }

            match ($issue->type) {
                'error' => $summary['errors']++,
                'warning' => $summary['warnings']++,
                'info' => $summary['info']++,
                default => $summary['warnings']++,
            };
        }

        return $summary;
    }
}