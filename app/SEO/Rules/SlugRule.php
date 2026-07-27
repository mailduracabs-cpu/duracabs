<?php

namespace App\SEO\Rules;

use App\SEO\Contracts\SeoRuleInterface;
use App\SEO\DTO\SeoIssue;
use App\SEO\DTO\SeoResult;
use Illuminate\Support\Str;

class SlugRule implements SeoRuleInterface
{
    public function analyze(array $data, SeoResult $result): void
    {
        $slug = trim((string) ($data['slug'] ?? ''));
        $focusKeyword = trim((string) ($data['focus_keyword'] ?? ''));

        if ($slug === '') {
            $result->addIssue(new SeoIssue(
                type: 'error',
                title: 'Slug missing',
                message: 'Page URL slug empty hai.',
                suggestion: 'Short aur keyword-friendly slug add karein.',
                passed: false,
            ));

            return;
        }

        $normalizedSlug = Str::slug($slug);

        if ($slug !== $normalizedSlug) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Slug format sahi nahi hai',
                message: 'Slug me spaces, uppercase letters ya unsupported characters ho sakte hain.',
                suggestion: "Slug ko '{$normalizedSlug}' me convert karein.",
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Slug format sahi hai',
                message: 'Slug lowercase aur hyphen-separated format me hai.',
                suggestion: 'Koi change zaroori nahi hai.',
                passed: true,
            ));
        }

        $slugLength = Str::length($slug);

        if ($slugLength > 75) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Slug bahut lamba hai',
                message: "Current slug {$slugLength} characters ka hai.",
                suggestion: 'Slug ko short aur descriptive rakhein, preferably 75 characters ke andar.',
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Slug length sahi hai',
                message: "Current slug {$slugLength} characters ka hai.",
                suggestion: 'Koi change zaroori nahi hai.',
                passed: true,
            ));
        }

        $stopWords = [
            'a',
            'an',
            'and',
            'are',
            'as',
            'at',
            'be',
            'by',
            'for',
            'from',
            'in',
            'is',
            'it',
            'of',
            'on',
            'or',
            'that',
            'the',
            'to',
            'with',
        ];

        $slugWords = array_values(array_filter(explode('-', $normalizedSlug)));

        $usedStopWords = array_values(array_intersect($slugWords, $stopWords));

        if ($usedStopWords !== []) {
            $result->addIssue(new SeoIssue(
                type: 'info',
                title: 'Slug me unnecessary words hain',
                message: 'Slug me ye common words mile: ' . implode(', ', $usedStopWords),
                suggestion: 'Possible ho to unnecessary stop words remove karke slug short karein.',
                passed: false,
            ));
        }

        if ($focusKeyword === '') {
            $result->addIssue(new SeoIssue(
                type: 'info',
                title: 'Focus keyword missing',
                message: 'Focus keyword set nahi kiya gaya hai.',
                suggestion: 'Slug analysis ke liye focus keyword add karein.',
                passed: false,
            ));

            return;
        }

        $keywordSlug = Str::slug($focusKeyword);

        if (! Str::contains($normalizedSlug, $keywordSlug)) {
            $result->addIssue(new SeoIssue(
                type: 'error',
                title: 'Focus keyword slug me nahi hai',
                message: "'{$focusKeyword}' keyword page slug me detect nahi hua.",
                suggestion: "Suggested slug: '{$keywordSlug}'. Existing route details ko zarurat ke hisaab se saath rakhein.",
                passed: false,
            ));

            return;
        }

        $result->addIssue(new SeoIssue(
            type: 'success',
            title: 'Focus keyword slug me hai',
            message: "'{$focusKeyword}' keyword URL slug me available hai.",
            suggestion: 'Koi change zaroori nahi hai.',
            passed: true,
        ));
    }
}