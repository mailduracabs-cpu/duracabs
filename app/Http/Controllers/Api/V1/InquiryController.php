<?php

namespace App\Http\Controllers\Api\V1;

class InquiryController extends BaseApiController
{
    public function store()
    {
        return $this->success([], 'Create inquiry API coming in Package 6');
    }

    public function index()
    {
        return $this->success([], 'My inquiries API coming in Package 6');
    }
}
