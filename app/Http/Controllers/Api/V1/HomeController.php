<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\HomeService;

class HomeController extends BaseApiController
{
    public function appConfig(HomeService $homeService)
    {
        return $this->success(
            $homeService->appConfig(),
            'App config loaded successfully'
        );
    }

    public function home(HomeService $homeService)
    {
        return $this->success(
            $homeService->home(),
            'Home data loaded successfully'
        );
    }

    public function banners(HomeService $homeService)
    {
        return $this->success(
            $homeService->banners(),
            'Banners loaded successfully'
        );
    }

    public function popularRoutes(HomeService $homeService)
    {
        return $this->success(
            $homeService->popularRoutes(),
            'Popular routes loaded successfully'
        );
    }

    public function recommendedTrips(HomeService $homeService)
    {
        return $this->success(
            $homeService->recommendedTrips(),
            'Recommended trips loaded successfully'
        );
    }

    public function contact(HomeService $homeService)
    {
        $config = $homeService->appConfig();

        return $this->success([
            'support_mobile'   => $config['support_mobile'] ?? null,
            'whatsapp_mobile'  => $config['whatsapp_mobile'] ?? null,
            'email'            => $config['email'] ?? null,
            'website'          => $config['website'] ?? null,
        ], 'Contact details loaded successfully');
    }

    public function settings(HomeService $homeService)
    {
        return $this->success(
            $homeService->appConfig(),
            'Settings loaded successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Tour Packages
    |--------------------------------------------------------------------------
    */

    public function tourPackages(HomeService $homeService)
    {
        if (method_exists($homeService, 'tourPackages')) {
            return $this->success(
                $homeService->tourPackages(),
                'Tour packages loaded successfully'
            );
        }

        return $this->success([], 'Tour package API ready');
    }

    /*
    |--------------------------------------------------------------------------
    | AI Home Feed
    |--------------------------------------------------------------------------
    */

    public function aiHomeFeed(HomeService $homeService)
    {
        if (method_exists($homeService, 'aiHomeFeed')) {
            return $this->success(
                $homeService->aiHomeFeed(),
                'AI home feed loaded successfully'
            );
        }

        return $this->success([
            'recommended' => [],
            'offers' => [],
            'trending' => [],
            'popular_routes' => [],
        ], 'AI Home Feed API ready');
    }
}