<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Banners;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page as SeoPage;
use App\Models\Reviews;
use App\Models\Vehicle;
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
     * Kept for the homepage banner component. Search-panel state now belongs
     * exclusively to ServiceSearchPanel.
     *
     * @var array<int, mixed>
     */
    public array $homepageSelfDriveVehicles = [];

    public function changeBanner(string $value): void
    {
        $allowedTabs = ['one_way', 'return', 'local', 'self_drive'];

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
            return redirect()->away('https://g.page/r/CTGafymLAOMYEBM/review');
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
     * Homepage FAQs are defined once and reused by both the visible FAQ section
     * and the FAQPage JSON-LD schema.
     *
     * @return array<int, array{question:string, answer:string, link_label:?string, link_url:?string}>
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
     * Build homepage FAQPage schema from the same data displayed on the page.
     */
    private function homepageFaqSchema(array $faqItems): array
    {
        if ($faqItems === []) {
            return [];
        }

        $homeUrl = rtrim(url('/'), '/') . '/';

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            '@id' => $homeUrl . '#faq',
            'url' => $homeUrl . '#frequently-asked-questions',
            'mainEntity' => collect($faqItems)
                ->map(static fn (array $faq): array => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['answer'],
                    ],
                ])
                ->values()
                ->all(),
        ];
    }

    public function render(): View
    {
        $seoPage = SeoPage::query()
            ->whereIn('slug', ['home', 'homepage'])
            ->first();

        $faqItems = $this->homepageFaqItems();
        $faqSchema = $this->homepageFaqSchema($faqItems);

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

        $reviews = Reviews::query()->get();

        $categories = Category::query()
            ->where('in_return', 1)
            ->get();

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
            'faqSchema' => $faqSchema,
        ])->title(
            filled($seoPage?->meta_title)
                ? $seoPage->meta_title
                : 'Home Page - Duracabs'
        );
    }
}