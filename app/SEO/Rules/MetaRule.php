<?php

namespace App\SEO\Rules;

use App\SEO\Contracts\SeoRuleInterface;
use App\SEO\DTO\SeoIssue;
use App\SEO\DTO\SeoResult;
use Illuminate\Support\Str;

class MetaRule implements SeoRuleInterface
{
    public function analyze(array $data, SeoResult $result): void
    {
        $metaDescription = trim((string) ($data['meta_description'] ?? ''));
        $focusKeyword = trim((string) ($data['focus_keyword'] ?? ''));

        if ($metaDescription === '') {
            $result->addIssue(new SeoIssue(
                type: 'error',
                title: 'Meta description missing',
                message: 'Meta description empty hai.',
                suggestion: '150–160 characters ki meta description likhiye.',
                passed: false,
            ));

            return;
        }

        $length = Str::length($metaDescription);

        if ($length < 120) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Meta description bahut chhoti hai',
                message: "Current length {$length} characters hai.",
                suggestion: 'Meta description ko 150–160 characters tak badhayein.',
                passed: false,
            ));
        } elseif ($length > 160) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Meta description bahut lambi hai',
                message: "Current length {$length} characters hai.",
                suggestion: 'Meta description ko 160 characters ke andar rakhein.',
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Meta description length sahi hai',
                message: "Length {$length} characters hai.",
                suggestion: 'Koi change zaroori nahi hai.',
                passed: true,
            ));
        }

        if ($focusKeyword === '') {
            $result->addIssue(new SeoIssue(
                type: 'info',
                title: 'Focus keyword missing',
                message: 'Focus keyword set nahi hai.',
                suggestion: 'Focus keyword add karein.',
                passed: false,
            ));

            return;
        }

        if (! Str::contains(
            Str::lower($metaDescription),
            Str::lower($focusKeyword)
        )) {
            $result->addIssue(new SeoIssue(
                type: 'error',
                title: 'Focus keyword meta description me nahi hai',
                message: "'{$focusKeyword}' keyword meta description me nahi mila.",
                suggestion: "Meta description me '{$focusKeyword}' naturally include karein.",
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Focus keyword meta description me hai',
                message: "'{$focusKeyword}' keyword detect hua.",
                suggestion: 'Koi change zaroori nahi hai.',
                passed: true,
            ));
        }

        $ctaWords = [
            'book',
            'call',
            'contact',
            'hire',
            'book now',
            'reserve',
            'get quote',
            'book cab',
            'book taxi',
        ];

        $hasCTA = false;

        foreach ($ctaWords as $word) {
            if (Str::contains(Str::lower($metaDescription), $word)) {
                $hasCTA = true;
                break;
            }
        }

        if (! $hasCTA) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'CTA missing',
                message: 'Meta description me Call To Action nahi mila.',
                suggestion: 'Jaise "Book Now", "Call Now", "Get Quote" add karein.',
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'CTA available',
                message: 'Meta description me Call To Action mila.',
                suggestion: 'Koi change zaroori nahi hai.',
                passed: true,
            ));
        }
    }
}