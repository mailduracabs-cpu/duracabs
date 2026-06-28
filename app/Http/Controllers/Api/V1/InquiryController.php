<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\InquiryRequest;
use App\Services\InquiryService;
use Illuminate\Http\Request;

class InquiryController extends BaseApiController
{
    public function store(InquiryRequest $request, InquiryService $inquiryService)
    {
        $result = $inquiryService->create($request->validated());

        if (!$result['status']) {
            return $this->error($result['message'], $result['code'] ?? 422, $result['errors'] ?? null);
        }

        return $this->success($result['data'], $result['message'], 201);
    }

    public function index(Request $request, InquiryService $inquiryService)
    {
        $data = $inquiryService->myInquiries(
            mobile: $request->query('mobile'),
            email: $request->query('email'),
            limit: (int) $request->query('limit', 20)
        );

        return $this->success($data, 'Inquiries loaded successfully');
    }
}
