<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Banners;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page as SeoPage;
use App\Models\Reviews;
use App\Models\Vehicle;
use App\SEO\Services\SeoSchemaService;
use App\Services\SmartBannerService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Homepage extends Component
{
    public string $bannerTab = 'one_way';

    public ?string $name = null;

    public ?string $designation = null;

    public ?string $description = null;

    public $reviwerStar = null;

    public bool $showReview = false;

    /**
     * Kept for the homepage banner component. Search-panel state belongs
     * exclusively to ServiceSearchPanel.
     *
     * @var array<int, mixed>
     */
    public array $homepageSelfDriveVehicles = [];

    public function changeBanner(string $value): void
    {
        $allowedTabs = [
            'one_way',
            'return',
            'local',
            'self_drive',
        ];

        if (! in_array($value, $allowedTabs, true)) {
            return;
        }

        $this->bannerTab = $value;
        $this->dispatch('banner-filter-finished');
    }

    public function changeStarValue($value): void
    {
        $value = (int) $value;

        $this->reviwerStar = max(1, min(5, $value));
    }

    public function reviewFunction(bool $show): void
    {
        $this->showReview = $show;

        if (! $show) {
            $this->resetReviewForm();
        }
    }

    public function submitReview()
    {
        $star = (int) $this->reviwerStar;

        if ($star > 3) {
            return redirect()->away(
                'https://g.page/r/CTGafymLAOMYEBM/review'
            );
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'reviwerStar' => ['required', 'integer', 'between:1,5'],
        ]);

        Reviews::create([
            'name' => $validated['name'],
            'designation' => $validated['designation'],
            'description' => $validated['description'],
            'star' => (int) $validated['reviwerStar'],
        ]);

        $this->showReview = false;
        $this->resetReviewForm();
        $this->dispatch('review-submitted');

        return null;
    }

    private function resetReviewForm(): void
    {
        $this->reset([
            'name',
            'designation',
            'description',
            'reviwerStar',
        ]);

        $this->resetValidation();
    }

    /**
     * Homepage FAQs are defined once and reused by the visible FAQ section
     * and the centralized FAQPage schema.
     *
     * @return array<int, array{
     *     question:string,
     *     answer:string,
     *     link_label:?string,
     *     link_url:?string
     * }>
     */
    private function homepageFaqItems(): array
    {
        return [
            [
                'question' => 'What payment methods does DURA Cabs accept?',
                'answer' => 'We accept credit cards, debit cards, net banking, UPI, supported wallets and cash at pickup where available. Payment options are shown during the booking process.',
                'link_label' => null,
                'link_url' => null,
            ],
            [
                'question' => 'What is included in the cab fare?',
                'answer' => 'Fare details depend on the selected service and package. The booking summary displays the applicable rental fare, taxes and other included charges before confirmation.',
                'link_label' => null,
                'link_url' => null,
            ],
            [
                'question' => 'Can I pay at the pickup location?',
                'answer' => 'Yes, when cash payment is available for the selected booking, you can choose that option and pay at pickup.',
                'link_label' => null,
                'link_url' => null,
            ],
            [
                'question' => 'Who pays tolls, parking and state permit charges?',
                'answer' => 'These charges are generally paid by the customer unless they are specifically included in the selected package. Always check the fare summary before confirming.',
                'link_label' => null,
                'link_url' => null,
            ],
            [
                'question' => 'Do DURA Cabs vehicles have FASTag?',
                'answer' => 'Availability may depend on the assigned vehicle. Toll amounts remain payable according to the booking terms and selected fare package.',
                'link_label' => null,
                'link_url' => null,
            ],
            [
                'question' => 'How can I attach my car with DURA Cabs?',
                'answer' => 'Visit the vendor registration page, submit your business and vehicle details, and upload the requested documents for verification.',
                'link_label' => 'Register as a vendor',
                'link_url' => url('/vendor-register'),
            ],
            [
                'question' => 'What is the minimum age for a self-drive booking?',
                'answer' => 'The customer must meet the applicable age requirement and hold a valid driving licence. Final eligibility can vary according to vehicle and booking terms.',
                'link_label' => null,
                'link_url' => null,
            ],
            [
                'question' => 'How do I book a taxi with DURA Cabs?',
                'answer' => 'Select the trip type, enter pickup and destination details, choose the date and time, then continue to view available vehicles and fares.',
                'link_label' => 'Book a cab',
                'link_url' => url('/rides'),
            ],
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int, Vehicle> $products
     * @return array<int, array<string, mixed>>
     */
    private function homepageItemListSchemas($products): array
    {
        if ($products->isEmpty()) {
            return [];
        }

        $homeUrl = rtrim(url('/'), '/') . '/';

        $items = $products
            ->values()
            ->map(function (Vehicle $vehicle, int $index): array {
                $vehicleUrl = filled($vehicle->slug ?? null)
                    ? url('/self-drive/' . ltrim((string) $vehicle->slug, '/'))
                    : url('/self-drive');

                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => $vehicleUrl,
                    'name' => trim((string) (
                        $vehicle->name
                        ?: $vehicle->vehicle_name
                        ?: 'Self Drive Car'
                    )),
                ];
            })
            ->all();

        return [[
            '@type' => 'ItemList',
            '@id' => $homeUrl . '#featured-self-drive-vehicles',
            'name' => 'Featured Self Drive Vehicles',
            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
            'numberOfItems' => count($items),
            'itemListElement' => $items,
        ]];
    }

    public function render(SeoSchemaService $seoSchemaService): View
    {
        $seoPage = SeoPage::query()
            ->whereIn('slug', ['home', 'homepage'])
            ->first();

        $faqItems = $this->homepageFaqItems();

        $brands = Brand::query()
            ->where('is_active', 1)
            ->where('is_populer', 1)
            ->get();

        $products = Vehicle::query()
            ->with(['frontMedia', 'transporter'])
            ->availableForRental()
            ->selfDrive()
            ->cars()
            ->where('daily_price', '>', 0)
            ->latest('id')
            ->take(12)
            ->get();

        $carousel = Banners::query()
            ->where('ride_type', $this->bannerTab)
            ->get();

        $smartHeroBanners = app(SmartBannerService::class)
            ->getSection('hero_banners');

        $tours = Banners::query()
            ->where('ride_type', 'tour')
            ->get();

        $reviews = Reviews::query()
            ->latest('id')
            ->get();

        $categories = Category::query()
            ->where('in_return', 1)
            ->get();

        $homeUrl = rtrim(url('/'), '/') . '/';

        $seoTitle = filled($seoPage?->meta_title)
            ? trim((string) $seoPage->meta_title)
            : 'Home Page - Duracabs';

        $seoDescription = filled($seoPage?->meta_description)
            ? trim((string) $seoPage->meta_description)
            : 'Book reliable taxi, local cab, round trip and self-drive car services with Dura Cabs.';

        $imageUrl = filled($seoPage?->resolved_image_url)
            ? $seoPage->resolved_image_url
            : null;

        $schemaGraph = $seoSchemaService->pageGraph(
            url: $homeUrl,
            title: $seoTitle,
            description: $seoDescription,
            pageType: 'WebPage',
            imageUrl: $imageUrl,
            breadcrumbs: [
                [
                    'name' => 'Home',
                    'url' => $homeUrl,
                ],
            ],
            faqs: $faqItems,
            additionalSchemas: $this->homepageItemListSchemas($products),
            keywords: $this->normaliseKeywords(
                $seoPage?->meta_keywords,
                $seoPage?->focus_keyword,
            ),
            datePublished: optional($seoPage?->published_at)?->toIso8601String(),
            dateModified: optional($seoPage?->updated_at)?->toIso8601String(),
            homeUrl: $homeUrl,
        );

        return view('livewire.homepage', [
            'brands' => $brands,
            'categories' => $categories,
            'reviews' => $reviews,
            'carousel' => $carousel,
            'smartHeroBanners' => $smartHeroBanners,
            'homepageSelfDriveVehicles' => $this->homepageSelfDriveVehicles,
            'tours' => $tours,
            'products' => $products,
            'seoPage' => $seoPage,
            'faqItems' => $faqItems,
            'schemaGraph' => $schemaGraph,
        ])->title($seoTitle);
    }

    /**
     * @return array<int, string>|null
     */
    private function normaliseKeywords(
        mixed $keywords,
        mixed $focusKeyword
    ): ?array {
        $items = [];

        if (is_array($keywords)) {
            $items = $keywords;
        } elseif (is_string($keywords)) {
            $items = preg_split('/[,\n]+/', $keywords) ?: [];
        }

        if (filled($focusKeyword)) {
            array_unshift($items, (string) $focusKeyword);
        }

        $items = collect($items)
            ->map(fn (mixed $item): string => trim((string) $item))
            ->filter()
            ->unique(fn (string $item): string => mb_strtolower($item))
            ->values()
            ->all();

        return $items !== [] ? $items : null;
    }
}