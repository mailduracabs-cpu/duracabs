<?php

namespace App\SEO\Rules;

use App\SEO\Contracts\SeoRuleInterface;
use App\SEO\DTO\SeoIssue;
use App\SEO\DTO\SeoResult;
use Illuminate\Support\Str;

class TitleRule implements SeoRuleInterface
{
    public function analyze(array $data, SeoResult $result): void
    {
        $title = trim((string)(
    $data['meta_title']
    ?? $data['title']
    ?? $data['name']
    ?? ''
));
        $focusKeyword = trim((string) ($data['focus_keyword'] ?? ''));

        if ($title === '') {
            $result->addIssue(new SeoIssue(
                type: 'error',
                title: 'SEO title missing',
                message: 'SEO title empty hai.',
                suggestion: '50 se 60 characters ka SEO title add karein.',
                passed: false,
            ));

            return;
        }

        $titleLength = Str::length($title);

        if ($titleLength < 30) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'SEO title bahut chhota hai',
                message: "Current title {$titleLength} characters ka hai.",
                suggestion: 'SEO title ko kam se kam 30 characters tak badhayein.',
                passed: false,
            ));
        } elseif ($titleLength > 60) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'SEO title bahut lamba hai',
                message: "Current title {$titleLength} characters ka hai aur Google me cut ho sakta hai.",
                suggestion: 'SEO title ko 60 characters ke andar rakhein.',
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'SEO title length achhi hai',
                message: "SEO title {$titleLength} characters ka hai.",
                suggestion: 'Koi change zaroori nahi hai.',
                passed: true,
            ));
        }

        if ($focusKeyword === '') {
            $result->addIssue(new SeoIssue(
                type: 'info',
                title: 'Focus keyword missing',
                message: 'Focus keyword set nahi kiya gaya hai.',
                suggestion: 'Page ka primary keyword focus keyword field me add karein.',
                passed: false,
            ));

            return;
        }

        if (! Str::contains(
            Str::lower($title),
            Str::lower($focusKeyword),
        )) {
            $result->addIssue(new SeoIssue(
                type: 'error',
                title: 'Focus keyword SEO title me nahi hai',
                message: "SEO title me '{$focusKeyword}' keyword nahi mila.",
                suggestion: "SEO title me '{$focusKeyword}' naturally include karein.",
                passed: false,
            ));

            return;
        }

        $result->addIssue(new SeoIssue(
            type: 'success',
            title: 'Focus keyword SEO title me hai',
            message: "SEO title me '{$focusKeyword}' keyword available hai.",
            suggestion: 'Koi change zaroori nahi hai.',
            passed: true,
        ));

        if (! Str::startsWith(
            Str::lower($title),
            Str::lower($focusKeyword),
        )) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Focus keyword title ke beginning me nahi hai',
                message: 'Keyword title me hai, lekin shuruaat ke paas nahi hai.',
                suggestion: "Possible ho to title ko '{$focusKeyword}' se start karein.",
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Focus keyword title ke beginning me hai',
                message: 'Primary keyword SEO title ke starting position par hai.',
                suggestion: 'Koi change zaroori nahi hai.',
                passed: true,
            ));
        }
    }
}