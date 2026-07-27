<?php

namespace App\SEO\Rules;

use App\SEO\Contracts\SeoRuleInterface;
use App\SEO\DTO\SeoIssue;
use App\SEO\DTO\SeoResult;
use Illuminate\Support\Str;

class ReadabilityRule implements SeoRuleInterface
{
    public function analyze(array $data, SeoResult $result): void
    {
        $description = (string) ($data['description'] ?? '');
        $plainText = $this->normalizeText($description);

        if ($plainText === '') {
            $result->addIssue(new SeoIssue(
                type: 'error',
                title: 'Readability analyze nahi ho saki',
                message: 'Page content empty hai.',
                suggestion: 'Readable aur properly structured content add karein.',
                passed: false,
            ));

            return;
        }

        $sentences = $this->extractSentences($plainText);
        $paragraphs = $this->extractParagraphs($description);
        $words = $this->extractWords($plainText);

        $sentenceCount = count($sentences);
        $paragraphCount = count($paragraphs);
        $wordCount = count($words);

        $averageSentenceLength = $sentenceCount > 0
            ? round($wordCount / $sentenceCount, 1)
            : 0;

        $longSentences = array_values(array_filter(
            $sentences,
            fn (string $sentence): bool => count($this->extractWords($sentence)) > 25,
        ));

        $veryLongSentences = array_values(array_filter(
            $sentences,
            fn (string $sentence): bool => count($this->extractWords($sentence)) > 35,
        ));

        $longParagraphs = array_values(array_filter(
            $paragraphs,
            fn (string $paragraph): bool => count($this->extractWords($paragraph)) > 120,
        ));

        $passiveVoiceCount = $this->countPassiveVoicePatterns($sentences);
        $transitionWordCount = $this->countTransitionWords($sentences);
        $difficultWordCount = $this->countDifficultWords($words);

        $readabilityScore = 100;

        if ($averageSentenceLength > 25) {
            $readabilityScore -= 20;
        } elseif ($averageSentenceLength > 20) {
            $readabilityScore -= 10;
        }

        if ($sentenceCount > 0) {
            $longSentencePercentage = (count($longSentences) / $sentenceCount) * 100;

            if ($longSentencePercentage > 35) {
                $readabilityScore -= 20;
            } elseif ($longSentencePercentage > 20) {
                $readabilityScore -= 10;
            }
        }

        if ($paragraphCount > 0) {
            $longParagraphPercentage = (count($longParagraphs) / $paragraphCount) * 100;

            if ($longParagraphPercentage > 40) {
                $readabilityScore -= 15;
            } elseif ($longParagraphPercentage > 20) {
                $readabilityScore -= 8;
            }
        }

        if ($sentenceCount > 0) {
            $passiveVoicePercentage = ($passiveVoiceCount / $sentenceCount) * 100;

            if ($passiveVoicePercentage > 20) {
                $readabilityScore -= 10;
            }
        }

        if ($sentenceCount >= 5) {
            $transitionPercentage = ($transitionWordCount / $sentenceCount) * 100;

            if ($transitionPercentage < 20) {
                $readabilityScore -= 10;
            }
        }

        if ($wordCount > 0) {
            $difficultWordPercentage = ($difficultWordCount / $wordCount) * 100;

            if ($difficultWordPercentage > 20) {
                $readabilityScore -= 10;
            }
        }

        $readabilityScore = max(0, min(100, $readabilityScore));

        $result->readabilityScore = $readabilityScore;

        $this->analyzeSentenceLength(
            result: $result,
            averageSentenceLength: $averageSentenceLength,
            longSentences: $longSentences,
            veryLongSentences: $veryLongSentences,
            sentenceCount: $sentenceCount,
        );

        $this->analyzeParagraphs(
            result: $result,
            paragraphs: $paragraphs,
            longParagraphs: $longParagraphs,
        );

        $this->analyzePassiveVoice(
            result: $result,
            passiveVoiceCount: $passiveVoiceCount,
            sentenceCount: $sentenceCount,
        );

        $this->analyzeTransitionWords(
            result: $result,
            transitionWordCount: $transitionWordCount,
            sentenceCount: $sentenceCount,
        );

        $this->analyzeDifficultWords(
            result: $result,
            difficultWordCount: $difficultWordCount,
            wordCount: $wordCount,
        );

        $this->addOverallReadabilityIssue(
            result: $result,
            readabilityScore: $readabilityScore,
        );
    }

    private function analyzeSentenceLength(
        SeoResult $result,
        float $averageSentenceLength,
        array $longSentences,
        array $veryLongSentences,
        int $sentenceCount,
    ): void {
        if ($sentenceCount === 0) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Sentences detect nahi hui',
                message: 'Content me clear sentence structure detect nahi hua.',
                suggestion: 'Content me proper punctuation aur complete sentences use karein.',
                passed: false,
            ));

            return;
        }

        if ($averageSentenceLength > 25) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Sentences bahut lambi hain',
                message: "Average sentence length {$averageSentenceLength} words hai.",
                suggestion: 'Long sentences ko 2 ya 3 chhote sentences me divide karein.',
                passed: false,
            ));
        } elseif ($averageSentenceLength > 20) {
            $result->addIssue(new SeoIssue(
                type: 'info',
                title: 'Sentence length improve ho sakti hai',
                message: "Average sentence length {$averageSentenceLength} words hai.",
                suggestion: 'Important sentences ko short aur direct banayein.',
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Sentence length readable hai',
                message: "Average sentence length {$averageSentenceLength} words hai.",
                suggestion: 'Current sentence style maintain rakhein.',
                passed: true,
            ));
        }

        if ($veryLongSentences !== []) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Very long sentences mili hain',
                message: count($veryLongSentences) . ' sentences 35 words se zyada lambi hain.',
                suggestion: 'In sentences ko short aur easy-to-understand parts me divide karein.',
                passed: false,
            ));
        }
    }

    private function analyzeParagraphs(
        SeoResult $result,
        array $paragraphs,
        array $longParagraphs,
    ): void {
        if ($paragraphs === []) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Paragraph structure missing hai',
                message: 'Content me clear paragraphs detect nahi hue.',
                suggestion: 'Content ko short paragraphs me divide karein.',
                passed: false,
            ));

            return;
        }

        if ($longParagraphs !== []) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Paragraphs bahut lambe hain',
                message: count($longParagraphs) . ' paragraphs me 120 se zyada words hain.',
                suggestion: 'Long paragraphs ko 2–3 short sections me split karein.',
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Paragraph structure achha hai',
                message: count($paragraphs) . ' readable paragraphs detect hue.',
                suggestion: 'Short paragraph style maintain rakhein.',
                passed: true,
            ));
        }
    }

    private function analyzePassiveVoice(
        SeoResult $result,
        int $passiveVoiceCount,
        int $sentenceCount,
    ): void {
        if ($sentenceCount === 0) {
            return;
        }

        $percentage = round(($passiveVoiceCount / $sentenceCount) * 100, 1);

        if ($percentage > 20) {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Passive voice zyada hai',
                message: "{$percentage}% sentences me passive voice pattern mila.",
                suggestion: 'Possible ho to active voice use karein, jaise “We provide cabs”.',
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Passive voice controlled hai',
                message: "{$percentage}% sentences me passive voice pattern mila.",
                suggestion: 'Current writing tone maintain rakhein.',
                passed: true,
            ));
        }
    }

    private function analyzeTransitionWords(
        SeoResult $result,
        int $transitionWordCount,
        int $sentenceCount,
    ): void {
        if ($sentenceCount < 5) {
            return;
        }

        $percentage = round(($transitionWordCount / $sentenceCount) * 100, 1);

        if ($percentage < 20) {
            $result->addIssue(new SeoIssue(
                type: 'info',
                title: 'Transition words kam hain',
                message: "Sirf {$percentage}% sentences me transition words mile.",
                suggestion: 'Jaise “however”, “therefore”, “also”, “because”, “iske alawa” use karein.',
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Transition words achhe hain',
                message: "{$percentage}% sentences me transition words detect hue.",
                suggestion: 'Natural flow maintain rakhein.',
                passed: true,
            ));
        }
    }

    private function analyzeDifficultWords(
        SeoResult $result,
        int $difficultWordCount,
        int $wordCount,
    ): void {
        if ($wordCount === 0) {
            return;
        }

        $percentage = round(($difficultWordCount / $wordCount) * 100, 1);

        if ($percentage > 20) {
            $result->addIssue(new SeoIssue(
                type: 'info',
                title: 'Complex words zyada hain',
                message: "{$percentage}% words difficult length ke hain.",
                suggestion: 'Complex terms ko simple aur customer-friendly words se replace karein.',
                passed: false,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Language easy hai',
                message: "Sirf {$percentage}% words complex detect hue.",
                suggestion: 'Simple language maintain rakhein.',
                passed: true,
            ));
        }
    }

    private function addOverallReadabilityIssue(
        SeoResult $result,
        int $readabilityScore,
    ): void {
        if ($readabilityScore >= 80) {
            $result->addIssue(new SeoIssue(
                type: 'success',
                title: 'Readability excellent hai',
                message: "Readability score {$readabilityScore}/100 hai.",
                suggestion: 'Current writing quality maintain rakhein.',
                passed: true,
            ));
        } elseif ($readabilityScore >= 60) {
            $result->addIssue(new SeoIssue(
                type: 'info',
                title: 'Readability achhi hai',
                message: "Readability score {$readabilityScore}/100 hai.",
                suggestion: 'Long sentences aur paragraphs ko thoda short karein.',
                passed: true,
            ));
        } else {
            $result->addIssue(new SeoIssue(
                type: 'warning',
                title: 'Readability improve karni hogi',
                message: "Readability score {$readabilityScore}/100 hai.",
                suggestion: 'Short sentences, headings, lists aur simple language use karein.',
                passed: false,
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

    /**
     * @return array<int, string>
     */
    private function extractSentences(string $text): array
    {
        $sentences = preg_split(
            '/(?<=[.!?।])\s+/u',
            trim($text),
            -1,
            PREG_SPLIT_NO_EMPTY,
        );

        return array_values(array_filter(
            $sentences ?: [],
            fn (string $sentence): bool => Str::length(trim($sentence)) >= 3,
        ));
    }

    /**
     * @return array<int, string>
     */
    private function extractParagraphs(string $html): array
    {
        preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $html, $matches);

        $paragraphs = collect($matches[1] ?? [])
            ->map(fn (string $paragraph): string => $this->normalizeText($paragraph))
            ->filter()
            ->values()
            ->all();

        if ($paragraphs !== []) {
            return $paragraphs;
        }

        $plainText = $this->normalizeText($html);

        if ($plainText === '') {
            return [];
        }

        return [$plainText];
    }

    /**
     * @return array<int, string>
     */
    private function extractWords(string $text): array
    {
        preg_match_all(
            '/[\p{L}\p{N}]+(?:[\'’-][\p{L}\p{N}]+)*/u',
            $text,
            $matches,
        );

        return $matches[0] ?? [];
    }

    private function countPassiveVoicePatterns(array $sentences): int
    {
        $patterns = [
            '/\b(is|are|was|were|be|been|being)\s+\w+(ed|en)\b/iu',
            '/\b(has been|have been|had been)\s+\w+(ed|en)\b/iu',
            '/\bkiya gaya\b/iu',
            '/\bdiya gaya\b/iu',
            '/\bkaha gaya\b/iu',
            '/\bbanaya gaya\b/iu',
        ];

        $count = 0;

        foreach ($sentences as $sentence) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $sentence) === 1) {
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }

    private function countTransitionWords(array $sentences): int
    {
        $transitionWords = [
            'also',
            'although',
            'because',
            'besides',
            'but',
            'finally',
            'first',
            'furthermore',
            'however',
            'moreover',
            'next',
            'therefore',
            'thus',
            'meanwhile',
            'instead',
            'similarly',
            'additionally',
            'for example',
            'for instance',
            'in addition',
            'as a result',
            'iske alawa',
            'isliye',
            'lekin',
            'kyunki',
            'saath hi',
            'udaharan ke liye',
            'ant me',
        ];

        $count = 0;

        foreach ($sentences as $sentence) {
            $normalizedSentence = Str::lower($sentence);

            foreach ($transitionWords as $transitionWord) {
                if (Str::contains($normalizedSentence, $transitionWord)) {
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }

    private function countDifficultWords(array $words): int
    {
        return count(array_filter(
            $words,
            fn (string $word): bool => Str::length($word) >= 12,
        ));
    }
}