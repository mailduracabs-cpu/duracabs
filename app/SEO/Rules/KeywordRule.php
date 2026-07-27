<?php

namespace App\SEO\Rules;

use App\SEO\Contracts\SeoRuleInterface;
use App\SEO\DTO\SeoIssue;
use App\SEO\DTO\SeoResult;
use Illuminate\Support\Str;

class KeywordRule implements SeoRuleInterface
{
    public function analyze(array $data, SeoResult $result): void
    {
        $focusKeyword = trim((string) ($data['focus_keyword'] ?? ''));
        $description = (string) ($data['description'] ?? '');

        $plainText = $this->normalizeText($description);

        $wordCount = $this->countWords($plainText);

        $result->wordCount = $wordCount;
        $result->readingTime = $wordCount > 0
            ? max(1, (int) ceil($wordCount / 200))
            : 0;

        if ($focusKeyword === '') {
            $result->keywordDensity = 0;

            $result->addIssue(new SeoIssue(
                type: 'error',
                title: 'Focus keyword missing',
                message: 'Primary focus keyword set nahi kiya gaya hai.',
                suggestion: 'Page ka main search keyword focus keyword field me add karein.',
                passed: false,
            ));

            return;
        }

        if ($plainText === '') {
            $result->keywordDensity = 0;

            $result->addIssue(new SeoIssue(
                type: 'error',
                title: 'Page content missing',
                message: 'SEO analysis ke liye page content available nahi hai.',
                suggestion: 'Detailed aur useful page content add karein.',
                passed: false,
            ));

            return;
        }

        $keywordOccurrences = $this->countKeywordOccurrences(
            text: $plainText,
            keyword: $focusKeyword,
        );

        $keywordWordCount = max(1, $this->countWords($focusKeyword));

        $keywordDensity = $wordCount > 0
            ? (($keywordOccurrences * $keywordWordCount) / $wordCount) * 100
            : 0;

        $result->keywordDensity = round($keywordDensity, 2);

        if ($keywordOccurrences === 0) {
            $result->addIssue(new SeoIssue(
                type: 'error',
                title: 'Focus keyword content me nahi hai',
                message: "'{$focusKeyword}' content me detect nahi hua.",
                suggestion: "Focus keyword '{$focusKeyword}' ko content me naturally include karein.",
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Focus keyword content me available hai',
                message: "Focus keyword {$keywordOccurrences} baar mila.",
                suggestion: 'Keyword ko natural context me hi use karein.',
                passed: true,
            ));
        }

        if ($keywordDensity < 0.5) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Keyword density bahut kam hai',
                message: "Current keyword density {$result->keywordDensity}% hai.",
                suggestion: 'Keyword ko introduction, headings aur relevant paragraphs me naturally use karein.',
                passed: false,
            ));
        } elseif ($keywordDensity > 2.5) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Keyword stuffing ka risk hai',
                message: "Current keyword density {$result->keywordDensity}% hai.",
                suggestion: 'Repeated keyword usage kam karein aur related terms ka use karein.',
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Keyword density achhi hai',
                message: "Current keyword density {$result->keywordDensity}% hai.",
                suggestion: 'Current keyword usage natural range me hai.',
                passed: true,
            ));
        }

        $firstParagraph = $this->extractFirstParagraph($description);

        if ($firstParagraph === '') {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'First paragraph missing',
                message: 'Content ka opening paragraph detect nahi hua.',
                suggestion: 'Page ki shuruaat ek clear introduction paragraph se karein.',
                passed: false,
            ));
        } elseif (! Str::contains(
            Str::lower($firstParagraph),
            Str::lower($focusKeyword),
        )) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Focus keyword first paragraph me nahi hai',
                message: "'{$focusKeyword}' opening paragraph me detect nahi hua.",
                suggestion: "Focus keyword '{$focusKeyword}' ko first paragraph me naturally add karein.",
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Focus keyword first paragraph me hai',
                message: 'Opening paragraph me focus keyword available hai.',
                suggestion: 'Koi change zaroori nahi hai.',
                passed: true,
            ));
        }

        if ($wordCount < 300) {
            $result->addIssue(new SeoIssue(
                type: 'error',
                title: 'Content bahut chhota hai',
                message: "Current content me sirf {$wordCount} words hain.",
                suggestion: 'Kam se kam 600 useful words ka detailed content add karein.',
                passed: false,
            ));
        } elseif ($wordCount < 600) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Content length improve ho sakti hai',
                message: "Current content me {$wordCount} words hain.",
                suggestion: 'Fare, distance, travel time, route, FAQ aur booking details add karein.',
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Content length achhi hai',
                message: "Current content me {$wordCount} words hain.",
                suggestion: 'Content quality aur accuracy maintain rakhein.',
                passed: true,
            ));
        }
    }

    private function normalizeText(string $html): string
    {
        $text = html_entity_decode(
            strip_tags($html),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }

    private function countWords(string $text): int
    {
        if (trim($text) === '') {
            return 0;
        }

        preg_match_all('/[\p{L}\p{N}]+(?:[\'’-][\p{L}\p{N}]+)*/u', $text, $matches);

        return count($matches[0] ?? []);
    }

    private function countKeywordOccurrences(string $text, string $keyword): int
    {
        $normalizedText = Str::lower($text);
        $normalizedKeyword = Str::lower(trim($keyword));

        if ($normalizedKeyword === '') {
            return 0;
        }

        return substr_count($normalizedText, $normalizedKeyword);
    }

    private function extractFirstParagraph(string $html): string
    {
        if (preg_match('/<p\b[^>]*>(.*?)<\/p>/is', $html, $matches) === 1) {
            return $this->normalizeText((string) ($matches[1] ?? ''));
        }

        $plainText = $this->normalizeText($html);

        if ($plainText === '') {
            return '';
        }

        $paragraphs = preg_split('/(?:\r?\n){2,}/u', $plainText);

        return trim((string) ($paragraphs[0] ?? $plainText));
    }
}