<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CommonService;

class CommonController extends Controller
{
    public function __construct(private CommonService $commonService) {}

    public function contactDetails()
    {
        return response()->json([
            'status' => true,
            'message' => 'Contact details fetched successfully',
            'data' => $this->commonService->contactDetails(),
        ]);
    }

    public function settings()
    {
        return response()->json([
            'status' => true,
            'message' => 'Settings fetched successfully',
            'data' => $this->commonService->settings(),
        ]);
    }
}
