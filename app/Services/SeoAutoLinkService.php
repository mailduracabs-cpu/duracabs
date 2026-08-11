<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SeoAutoLinkService
{
    public function linksFor(Model $current, int $limit = 12): array
    {
        $limit = max(4, min($limit, 20));
        $needle = $this->searchText($current);
        $tokens = $this->tokens($needle);

        $products = Product::query()
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->when($current instanceof Product, fn ($q) => $q->whereKeyNot($current->getKey()))
            ->latest('id')
            ->limit(120)
            ->get(['id','brand_id','name','slug','ride_type','url_type','content_type','focus_keyword','meta_title']);

        $pages = Page::query()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->when($current instanceof Page, fn ($q) => $q->whereKeyNot($current->getKey()))
            ->latest('id')
            ->limit(120)
            ->get(['id','brand_id','name','slug','content_type','focus_keyword','meta_title','published_at']);

        $candidates = $products->concat($pages)
            ->map(function ($item) use ($current, $tokens) {
                $score = $this->relevanceScore($current, $item, $tokens);

                return [
                    'title' => $this->title($item),
                    'url' => $item->public_path,
                    'type' => $this->typeLabel($item),
                    'score' => $score,
                    'id' => get_class($item) . ':' . $item->getKey(),
                ];
            })
            ->filter(fn ($link) => filled($link['title']) && filled($link['url']))
            ->sortByDesc('score')
            ->unique('url')
            ->values();

        // Keep relevance first, but always blend in recent public content. This means
        // newly-created pages immediately receive crawlable links without manual linking.
        $relevant = $candidates->take(max(6, $limit - 3));
        $recent = $candidates->sortByDesc(function ($link) {
            $parts = explode(':', $link['id']);
            return (int) end($parts);
        })->take(3);

        $internal = $relevant->concat($recent)
            ->unique('url')
            ->take($limit)
            ->map(fn ($link) => collect($link)->except(['score','id'])->all())
            ->values()
            ->all();

        return [
            'internal' => $internal,
            'external' => $this->trustedExternalLinks($needle),
        ];
    }

    private function relevanceScore(Model $current, Model $candidate, array $tokens): int
    {
        $candidateText = Str::lower($this->searchText($candidate));
        $score = 0;

        foreach ($tokens as $token) {
            if (str_contains($candidateText, $token)) {
                $score += strlen($token) >= 6 ? 5 : 3;
            }
        }

        if (filled($current->getAttribute('brand_id'))
            && $current->getAttribute('brand_id') === $candidate->getAttribute('brand_id')) {
            $score += 18;
        }

        if ($current instanceof Product && $candidate instanceof Product) {
            if (filled($current->ride_type) && $current->ride_type === $candidate->ride_type) {
                $score += 14;
            }
            if (filled($current->url_type) && $current->url_type === $candidate->url_type) {
                $score += 8;
            }
        }

        if ($current instanceof Page && $candidate instanceof Page
            && filled($current->content_type) && $current->content_type === $candidate->content_type) {
            $score += 10;
        }

        return $score;
    }

    private function searchText(Model $model): string
    {
        return collect([
            $model->getAttribute('name'),
            $model->getAttribute('slug'),
            $model->getAttribute('meta_title'),
            $model->getAttribute('focus_keyword'),
            $model->getAttribute('ride_type'),
            $model->getAttribute('content_type'),
        ])->filter()->implode(' ');
    }

    private function tokens(string $text): array
    {
        $stop = ['with','from','this','that','your','dura','cabs','cab','taxi','service','services','page','india','rent','rental'];

        return collect(preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($text))) ?: [])
            ->filter(fn ($word) => strlen($word) >= 3 && !in_array($word, $stop, true))
            ->unique()
            ->take(18)
            ->values()
            ->all();
    }

    private function title(Model $model): string
    {
        return trim((string) ($model->getAttribute('meta_title') ?: $model->getAttribute('name') ?: Str::headline((string) $model->getAttribute('slug'))));
    }

    private function typeLabel(Model $model): string
    {
        if ($model instanceof Product) {
            return match ((string) $model->url_type) {
                Product::URL_TYPE_SELF_DRIVE => 'Self Drive',
                Product::URL_TYPE_BIKE_RENTAL => 'Bike Rental',
                Product::URL_TYPE_TOUR => 'Tour',
                Product::URL_TYPE_PAGE => 'Page',
                default => 'Route',
            };
        }

        return match ((string) $model->content_type) {
            'blog' => 'Blog',
            'tour_package' => 'Tour',
            'service_page' => 'Service',
            default => 'Page',
        };
    }

    private function trustedExternalLinks(string $text): array
    {
        $text = Str::lower(Str::ascii($text));
        $links = [];

        if (str_contains($text, 'airport')) {
            $links[] = ['title' => 'Airports Authority of India', 'url' => 'https://www.aai.aero/', 'type' => 'Official source'];
        }

        if (str_contains($text, 'railway') || str_contains($text, 'train')) {
            $links[] = ['title' => 'Indian Railways', 'url' => 'https://indianrailways.gov.in/', 'type' => 'Official source'];
        }

        if (str_contains($text, 'tour') || str_contains($text, 'tourism') || str_contains($text, 'travel')) {
            $links[] = ['title' => 'Incredible India', 'url' => 'https://www.incredibleindia.gov.in/', 'type' => 'Official tourism source'];
        }

        return collect($links)->unique('url')->take(2)->values()->all();
    }
}