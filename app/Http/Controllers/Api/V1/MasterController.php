<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\MasterService;

class MasterController extends BaseApiController
{
    public function cities(MasterService $masterService)
    {
        return $this->success($masterService->cities(), 'Cities loaded successfully');
    }

    public function vehicleCategories(MasterService $masterService)
    {
        return $this->success($masterService->vehicleCategories(), 'Vehicle categories loaded successfully');
    }

    public function offers(MasterService $masterService)
    {
        return $this->success($masterService->offers(), 'Offers loaded successfully');
    }

    public function pages(MasterService $masterService)
    {
        return $this->success($masterService->pages(), 'Pages loaded successfully');
    }
}
