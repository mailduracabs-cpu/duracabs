<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\MasterService;

class MasterController extends BaseApiController
{
    public function cities(MasterService $masterService)
    {
        return $this->success(
            $masterService->cities(),
            'Cities loaded successfully'
        );
    }

    public function vehicleCategories(MasterService $masterService)
    {
        return $this->success(
            $masterService->vehicleCategories(),
            'Vehicle categories loaded successfully'
        );
    }

    public function offers(MasterService $masterService)
    {
        return $this->success(
            $masterService->offers(),
            'Offers loaded successfully'
        );
    }

    public function pages(MasterService $masterService)
    {
        return $this->success(
            $masterService->pages(),
            'Pages loaded successfully'
        );
    }

    public function coupons(MasterService $masterService)
    {
        if (method_exists($masterService, 'coupons')) {
            return $this->success(
                $masterService->coupons(),
                'Coupons loaded successfully'
            );
        }

        return $this->success([], 'Coupons API ready');
    }

    public function serviceTypes(MasterService $masterService)
    {
        if (method_exists($masterService, 'serviceTypes')) {
            return $this->success(
                $masterService->serviceTypes(),
                'Service types loaded successfully'
            );
        }

        return $this->success([
            ['key' => 'one_way', 'name' => 'One Way Taxi'],
            ['key' => 'round_trip', 'name' => 'Round Trip Taxi'],
            ['key' => 'local', 'name' => 'Local Taxi'],
            ['key' => 'airport', 'name' => 'Airport Transfer'],
            ['key' => 'self_drive', 'name' => 'Self Drive Car'],
            ['key' => 'tour', 'name' => 'Tour Package'],
            ['key' => 'tempo_traveller', 'name' => 'Tempo Traveller'],
        ], 'Service types loaded successfully');
    }
}