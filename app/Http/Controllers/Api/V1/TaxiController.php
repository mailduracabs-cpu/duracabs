<?php

namespace App\Http\Controllers\Api\V1;

class TaxiController extends BaseApiController
{
    public function home()
    {
        return $this->success([], 'Taxi home API coming in Package 2');
    }

    public function routes()
    {
        return $this->success([], 'One way routes API coming in Package 2');
    }

    public function search()
    {
        return $this->success([], 'Route search API coming in Package 2');
    }

    public function fareEstimate()
    {
        return $this->success([], 'Fare estimate API coming in Package 2');
    }
}
