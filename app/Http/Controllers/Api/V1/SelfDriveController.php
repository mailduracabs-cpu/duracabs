<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\HomeService;

class SelfDriveController extends BaseApiController
{
    public function index(HomeService $homeService)
    {
        return $this->success($homeService->selfDriveCars(), 'Self drive cars loaded successfully');
    }

    public function show(HomeService $homeService, $slug)
    {
        $cars = collect($homeService->selfDriveCars());
        $car = $cars->first(function ($item) use ($slug) {
            return ($item['slug'] ?? null) === $slug || (string)($item['id'] ?? '') === (string)$slug;
        });

        if (!$car) {
            return $this->success(['slug' => $slug], 'Self drive car not found');
        }

        return $this->success($car, 'Self drive car loaded successfully');
    }
}
