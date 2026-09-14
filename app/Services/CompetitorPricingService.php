<?php

namespace App\Services;

use App\Models\CompetitorPriceCheck;
use App\Models\CompetitorPriceRule;
use App\Models\Product;
use App\Models\Price;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class CompetitorPricingService
{
    public function updateAll(bool $apply, int $limit = 0): array
    {
        $summary = ['routes' => 0, 'checked' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        $query = Product::query()
            ->with(['brand:id,name', 'destination:id,name', 'prices.category:id,name'])
            ->where('ride_type', 'one_way')
            ->where('is_active', true)
            ->whereNotNull('brand_id')
            ->whereNotNull('booking_to')
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $query->get()->each(function (Product $product) use ($apply, &$summary): void {
            $summary['routes']++;
            $pages = $this->fetchRoutePages($product);

            foreach ($product->prices as $price) {
                $summary['checked']++;
                $result = $this->processPrice($product, $price, $pages, $apply);
                $summary[$result]++;
            }
        });

        return $summary;
    }

    private function processPrice(Product $product, Price $price, array $pages, bool $apply): string
    {
        $currentPrice = max(0, (float) $price->price);
        $rule = CompetitorPriceRule::query()->firstOrCreate(
            ['price_id' => $price->getKey()],
            [
                'baseline_price' => $currentPrice,
                'floor_price' => max(0, $currentPrice - (float) config('competitor-pricing.floor_reduction', 50)),
                'undercut_amount' => (float) config('competitor-pricing.undercut_amount', 50),
                'enabled' => true,
            ]
        );

        if (! $rule->enabled || $currentPrice <= 0 || ! $price->category) {
            $this->record($product, $price, [], null, $currentPrice, null, null, 'skipped', 'Pricing rule disabled or current fare/category unavailable.');
            return 'skipped';
        }

        $sourcePrices = [];
        foreach ($pages as $provider => $page) {
            if (! is_string($page) || $page === '') {
                continue;
            }

            $fare = $this->extractCategoryFare($page, (string) $price->category->name);
            if ($fare !== null) {
                $sourcePrices[$provider] = $fare;
            }
        }

        $rule->forceFill(['last_checked_at' => now()])->save();

        if ($sourcePrices === []) {
            $this->record($product, $price, [], null, $currentPrice, null, null, 'no_comparable_fare', 'No exact category fare was found on a verified public page.');
            return 'skipped';
        }

        $lowestPrice = min($sourcePrices);
        $lowestSource = array_search($lowestPrice, $sourcePrices, true) ?: null;
        $calculatedPrice = max(
            (float) $rule->floor_price,
            $lowestPrice - (float) $rule->undercut_amount
        );
        $calculatedPrice = round($calculatedPrice, 2);

        if (! $apply || abs($calculatedPrice - $currentPrice) < 0.01) {
            $status = $apply ? 'unchanged' : 'dry_run';
            $this->record($product, $price, $sourcePrices, $lowestSource, $currentPrice, $calculatedPrice, null, $status);
            return 'skipped';
        }

        try {
            DB::transaction(function () use ($price, $calculatedPrice, $rule): void {
                $lockedPrice = Price::query()->lockForUpdate()->findOrFail($price->getKey());
                $lockedPrice->price = $calculatedPrice;
                $lockedPrice->max_price = max((float) $lockedPrice->max_price, $calculatedPrice);
                $lockedPrice->save();
                $rule->forceFill(['last_updated_at' => now()])->save();
            }, 3);

            $this->record($product, $price, $sourcePrices, $lowestSource, $currentPrice, $calculatedPrice, $calculatedPrice, 'updated');
            return 'updated';
        } catch (Throwable $exception) {
            $this->record($product, $price, $sourcePrices, $lowestSource, $currentPrice, $calculatedPrice, null, 'failed', $exception->getMessage());
            return 'failed';
        }
    }

    private function fetchRoutePages(Product $product): array
    {
        $from = $this->citySlug((string) $product->brand?->name);
        $to = $this->citySlug((string) $product->destination?->name);

        if ($from === '' || $to === '') {
            return [];
        }

        $pages = [];
        foreach ((array) config('competitor-pricing.providers', []) as $provider => $settings) {
            if (! ($settings['enabled'] ?? false) || blank($settings['url'] ?? null)) {
                continue;
            }

            $url = $provider === 'savaari'
                ? sprintf($settings['url'], $from, $from, $to)
                : sprintf($settings['url'], $from, $to);

            try {
                $response = Http::withHeaders([
                    'User-Agent' => (string) config('competitor-pricing.user_agent'),
                    'Accept' => 'text/html,application/xhtml+xml',
                ])->timeout((int) config('competitor-pricing.timeout_seconds', 15))
                    ->retry(1, 500, throw: false)
                    ->get($url);

                if ($response->successful() && Str::contains(strtolower($response->header('Content-Type')), ['text/html', 'application/xhtml'])) {
                    $pages[$provider] = $response->body();
                }
            } catch (Throwable) {
                // A failed source must never alter the current DuraCabs fare.
            }

            usleep(max(0, (int) config('competitor-pricing.request_delay_ms', 750)) * 1000);
        }

        return $pages;
    }

    private function extractCategoryFare(string $html, string $categoryName): ?float
    {
        $text = preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '';
        $normalized = Str::lower($text);
        $aliases = $this->categoryAliases($categoryName);
        $matches = [];

        foreach ($aliases as $alias) {
            $offset = 0;
            while (($position = mb_stripos($normalized, $alias, $offset)) !== false) {
                $window = mb_substr($text, $position, 500);
                if (preg_match_all('/(?:₹|Rs\.?|INR)\s*([0-9][0-9,]*(?:\.\d{1,2})?)/iu', $window, $amounts)) {
                    foreach ($amounts[1] as $amount) {
                        $value = (float) str_replace(',', '', $amount);
                        if ($value >= 300 && $value <= 200000) {
                            $matches[] = $value;
                        }
                    }
                }
                $offset = $position + mb_strlen($alias);
            }
        }

        return $matches === [] ? null : min($matches);
    }

    private function categoryAliases(string $categoryName): array
    {
        $name = Str::lower(trim($categoryName));

        return match (true) {
            Str::contains($name, ['hatch', 'wagon', 'indica']) => ['ac hatchback', 'hatchback', 'economy cabs'],
            Str::contains($name, ['sedan', 'dzire', 'etios', 'tigor', 'aura']) => ['ac sedan', 'sedan', 'premium economy'],
            Str::contains($name, ['innova', 'crysta']) => ['innova crysta', 'innova'],
            Str::contains($name, ['suv', 'ertiga', 'xylo']) => ['ac suv large', 'large suv', 'suv'],
            Str::contains($name, ['traveller', 'tempo', 'van']) => ['tempo traveller', 'full size van', 'minivan'],
            default => [$name],
        };
    }

    private function citySlug(string $name): string
    {
        return Str::slug(trim(Str::before($name, ',')));
    }

    private function record(Product $product, Price $price, array $sources, ?string $lowestSource, float $oldPrice, ?float $calculated, ?float $applied, string $status, ?string $error = null): void
    {
        CompetitorPriceCheck::query()->create([
            'product_id' => $product->getKey(),
            'price_id' => $price->getKey(),
            'category_id' => $price->category_id,
            'source_prices' => $sources,
            'lowest_source' => $lowestSource,
            'lowest_price' => $sources === [] ? null : min($sources),
            'old_price' => $oldPrice,
            'calculated_price' => $calculated,
            'applied_price' => $applied,
            'status' => $status,
            'error_message' => $error ? Str::limit($error, 2000, '') : null,
            'checked_at' => now(),
        ]);
    }
}
