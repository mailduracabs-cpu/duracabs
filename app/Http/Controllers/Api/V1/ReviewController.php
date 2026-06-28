<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreReviewRequest;
use App\Services\ReviewService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    public function index(Request $request)
    {
        try {
            return response()->json([
                'status' => true,
                'message' => 'Reviews fetched successfully',
                'data' => $this->reviewService->list((int) $request->query('limit', 20)),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch reviews',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreReviewRequest $request)
    {
        try {
            $review = $this->reviewService->store($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Review submitted successfully',
                'data' => $review,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to submit review',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
