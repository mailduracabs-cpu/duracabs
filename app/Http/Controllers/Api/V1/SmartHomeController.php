<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\SmartBannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class SmartHomeController extends BaseApiController
{
    /**
     * Return complete Smart Home payload.
     */
    public function index(
        Request $request,
        SmartBannerService $smartBannerService
    ): JsonResponse {
        try {
            $forceRefresh = $request->boolean('refresh');

            if ($forceRefresh) {
                $smartBannerService->clearCache();
            }

            return $this->success(
                $smartBannerService->home(),
                'Smart home data loaded successfully'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                'Unable to load smart home data',
                500
            );
        }
    }

    /**
     * Return only dynamically generated hero banners.
     */
    public function heroBanners(
        Request $request,
        SmartBannerService $smartBannerService
    ): JsonResponse {
        try {
            return $this->success(
                $smartBannerService->heroBanners(
                    $request->boolean('refresh')
                ),
                'Smart hero banners loaded successfully'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                'Unable to load smart hero banners',
                500
            );
        }
    }

    /**
     * Return blocks belonging to one homepage section.
     */
    public function blocks(
        string $blockType,
        SmartBannerService $smartBannerService
    ): JsonResponse {
        $allowedBlockTypes = [
            'hero',
            'popular_route',
            'featured_vehicle',
            'recommended_vehicle',
            'self_drive',
            'offer',
            'festival',
        ];

        if (!in_array($blockType, $allowedBlockTypes, true)) {
            return $this->error(
                'Invalid smart home block type',
                422
            );
        }

        try {
            return $this->success(
                $smartBannerService->blocks($blockType),
                'Smart home blocks loaded successfully'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                'Unable to load smart home blocks',
                500
            );
        }
    }

    /**
     * Clear Smart Home cache.
     *
     * This route should be protected by Sanctum or admin middleware.
     */
    public function clearCache(
        SmartBannerService $smartBannerService
    ): JsonResponse {
        try {
            $smartBannerService->clearCache();

            return $this->success(
                null,
                'Smart home cache cleared successfully'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                'Unable to clear smart home cache',
                500
            );
        }
    }
}