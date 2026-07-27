<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\RatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RatingController extends BaseApiController
{
    /**
     * Rating List
     */
    public function index(Request $request, RatingService $ratingService)
    {
        $result = $ratingService->list(
            (int) $request->query('limit', 20)
        );

        return $this->success(
            $result['data'],
            $result['message']
        );
    }

    /**
     * My Ratings
     */
    public function myRatings(Request $request, RatingService $ratingService)
    {
        $result = $ratingService->myRatings(
            $request->user(),
            (int) $request->query('limit', 20)
        );

        if (!$result['status']) {
            return $this->error(
                $result['message'],
                $result['code'] ?? 422
            );
        }

        return $this->success(
            $result['data'],
            $result['message']
        );
    }

    /**
     * Submit Rating
     */
    public function store(Request $request, RatingService $ratingService)
    {
        $validator = Validator::make($request->all(), [

            'booking_id' => 'nullable|integer',

            'driver_id' => 'nullable|integer',

            'vehicle_id' => 'nullable|integer',

            'rating_for' => 'nullable|string',

            'rating' => 'required|numeric|min:1|max:5',

            'title' => 'nullable|string|max:255',

            'review' => 'nullable|string',

            'images' => 'nullable|array',

        ]);

        if ($validator->fails()) {

            return $this->error(
                $validator->errors()->first(),
                422,
                $validator->errors()
            );

        }

        $result = $ratingService->store(
            $request->user(),
            $validator->validated()
        );

        if (!$result['status']) {

            return $this->error(
                $result['message'],
                $result['code'] ?? 422,
                $result['errors'] ?? null
            );

        }

        return $this->success(
            $result['data'],
            $result['message'],
            201
        );
    }

    /**
     * Rating Summary
     */
    public function summary(RatingService $ratingService)
    {
        $result = $ratingService->summary();

        return $this->success(
            $result['data'],
            $result['message']
        );
    }
}