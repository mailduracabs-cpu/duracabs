<?php

namespace App\Http\Controllers\Api\V1;

class SelfDriveController extends BaseApiController
{
    public function index()
    {
        return $this->success([], 'Self drive list API coming in Package 3');
    }

    public function show($slug)
    {
        return $this->success(['slug' => $slug], 'Self drive detail API coming in Package 3');
    }
}
