<?php

namespace App\SEO\Rules;

use App\SEO\Contracts\SeoRuleInterface;
use App\SEO\DTO\SeoIssue;
use App\SEO\DTO\SeoResult;
use Illuminate\Support\Str;

class HeadingRule implements SeoRuleInterface
{
    public function analyze(array $data, SeoResult $result): void
    {
        $description = (string) ($data['description'] ?? '');
        $focusKeyword = trim((string) ($data['focus_keyword'] ?? ''));

        if (trim(strip_tags($description)) === '') {
            $result->addIssue(new SeoIssue(
                type: 'error',
                title: 'Content headings analyze nahi ho sake',
                message: 'Page content empty hai.',
                suggestion: 'Page content aur headings add karein.',
                passed: false,
            ));

            return;
        }

        $h1Headings = $this->extractHeadings($description, 'h1');
        $h2Headings = $this->extractHeadings($description, 'h2');
        $h3Headings = $this->extractHeadings($description, 'h3');

        /*
        |--------------------------------------------------------------------------
        | H1 Analysis
        |--------------------------------------------------------------------------
        */

        if (count($h1Headings) > 1) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Multiple H1 headings mili hain',
                message: count($h1Headings) . ' H1 headings detect hui hain.',
                suggestion: 'Page content me sirf ek main H1 rakhein. Filament page title already H1 ho sakta hai.',
                passed: false,
            ));
        } elseif (count($h1Headings) === 1) {
            $result->addIssue(new SeoIssue(
                type: 'info',
                title: 'Content me H1 heading mili hai',
                message: 'Rich content ke andar ek H1 detect hua hai.',
                suggestion: 'Check karein ki frontend page title ke saath duplicate H1 na ban raha ho.',
                passed: true,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Content me duplicate H1 nahi hai',
                message: 'Rich content ke andar H1 heading detect nahi hui.',
                suggestion: 'Main page title ko frontend par H1 ke roop me render karein.',
                passed: true,
            ));
        }

        /*
        |--------------------------------------------------------------------------
        | H2 Analysis
        |--------------------------------------------------------------------------
        */

        if ($h2Headings === []) {
            $result->addIssue(new SeoIssue(
                type: 'error',
                title: 'H2 headings missing hain',
                message: 'Content me koi H2 heading detect nahi hui.',
                suggestion: 'Fare, distance, route, booking aur FAQ ke liye descriptive H2 headings add karein.',
                passed: false,
            ));
        } elseif (count($h2Headings) < 2) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'H2 headings kam hain',
                message: 'Content me sirf ' . count($h2Headings) . ' H2 heading detect hui.',
                suggestion: 'Content ko readable sections me divide karne ke liye kam se kam 2–4 H2 headings rakhein.',
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'H2 structure achha hai',
                message: count($h2Headings) . ' H2 headings detect hui hain.',
                suggestion: 'Heading structure ko logical rakhein.',
                passed: true,
            ));
        }

        /*
        |--------------------------------------------------------------------------
        | Focus Keyword in Headings
        |--------------------------------------------------------------------------
        */

        if ($focusKeyword === '') {
            $result->addIssue(new SeoIssue(
                type: 'info',
                title: 'Heading keyword analysis pending',
                message: 'Focus keyword set nahi kiya gaya hai.',
                suggestion: 'Heading analysis ke liye focus keyword add karein.',
                passed: false,
            ));
        } else {
            $searchableHeadings = array_merge(
                $h1Headings,
                $h2Headings,
                $h3Headings,
            );

            $keywordInHeading = collect($searchableHeadings)->contains(
                fn (string $heading): bool => Str::contains(
                    Str::lower($heading),
                    Str::lower($focusKeyword),
                ),
            );

            if (! $keywordInHeading) {
                $result->addIssue(new SeoIssue(
                    type: 'warning',
                    title: 'Focus keyword heading me nahi hai',
                    message: "'{$focusKeyword}' kisi H1, H2 ya H3 heading me detect nahi hua.",
                    suggestion: "Kam se kam ek natural heading me '{$focusKeyword}' add karein.",
                    passed: false,
                ));
            } else {
                $result->addIssue(new SeoIssue(
                    type: 'success',
                    title: 'Focus keyword heading me hai',
                    message: "'{$focusKeyword}' heading structure me detect hua.",
                    suggestion: 'Keyword ko unnecessary headings me repeat na karein.',
                    passed: true,
                ));
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Empty and Duplicate Headings
        |--------------------------------------------------------------------------
        */

        $allHeadings = array_merge(
            $h1Headings,
            $h2Headings,
            $h3Headings,
        );

        $duplicateHeadings = $this->findDuplicateHeadings($allHeadings);

        if ($duplicateHeadings !== []) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Duplicate headings mili hain',
                message: 'Duplicate headings: ' . implode(', ', $duplicateHeadings),
                suggestion: 'Har heading ko unique aur section-specific banayein.',
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Headings unique hain',
                message: 'Duplicate H1, H2 ya H3 headings detect nahi hui.',
                suggestion: 'Koi change zaroori nahi hai.',
                passed: true,
            ));
        }

        /*
        |--------------------------------------------------------------------------
        | Heading Length
        |--------------------------------------------------------------------------
        */

        $longHeadings = array_values(array_filter(
            $allHeadings,
            fn (string $heading): bool => Str::length($heading) > 80,
        ));

        if ($longHeadings !== []) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Kuch headings bahut lambi hain',
                message: count($longHeadings) . ' heading 80 characters se zyada lambi hai.',
                suggestion: 'Headings ko concise, clear aur easy-to-scan rakhein.',
                passed: false,
            ));
        } elseif ($allHeadings !== []) {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Heading length sahi hai',
                message: 'Sabhi detected headings readable length me hain.',
                suggestion: 'Koi change zaroori nahi hai.',
                passed: true,
            ));
        }

        /*
        |--------------------------------------------------------------------------
        | H3 Hierarchy
        |--------------------------------------------------------------------------
        */

        if ($h3Headings !== [] && $h2Headings === []) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Heading hierarchy incorrect hai',
                message: 'H3 headings mili hain, lekin H2 heading available nahi hai.',
                suggestion: 'H3 ko relevant H2 section ke neeche use karein.',
                passed: false,
            ));
        } elseif ($h3Headings !== []) {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'H3 headings available hain',
                message: count($h3Headings) . ' H3 headings detect hui hain.',
                suggestion: 'Ensure karein ki har H3 kisi relevant H2 section ke andar ho.',
                passed: true,
            ));
        }
    }

    /**
     * @return array<int, string>
     */
    private function extractHeadings(string $html, string $tag): array
    {
        preg_match_all(
            '/<' . preg_quote($tag, '/') . '\b[^>]*>(.*?)<\/' . preg_quote($tag, '/') . '>/is',
            $html,
            $matches,
        );

        return collect($matches[1] ?? [])
            ->map(fn (string $heading): string => $this->cleanText($heading))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $headings
     * @return array<int, string>
     */
    private function findDuplicateHeadings(array $headings): array
    {
        $counts = [];

        foreach ($headings as $heading) {
            $key = Str::lower(trim($heading));

            if ($key === '') {
                continue;
            }

            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return collect($counts)
            ->filter(fn (int $count): bool => $count > 1)
            ->keys()
            ->values()
            ->all();
    }

    private function cleanText(string $html): string
    {
        $text = html_entity_decode(
            strip_tags($html),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }
}