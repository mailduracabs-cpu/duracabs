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

    public function render(): View
    {
        $seoPage = SeoPage::query()
            ->whereIn('slug', ['home', 'homepage'])
            ->first();

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
        ])->title(
            filled($seoPage?->meta_title)
                ? $seoPage->meta_title
                : 'Home Page - Duracabs'
        );
    }
}