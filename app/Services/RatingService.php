<?php

namespace App\Services;

use App\Repositories\RatingRepository;
use Illuminate\Support\Facades\Log;

class RatingService
{
    public function __construct(
        private RatingRepository $ratingRepository
    ) {
    }

    public function list(int $limit = 20): array
    {
        return [
            'status' => true,
            'message' => 'Ratings loaded successfully',
            'data' => $this->ratingRepository->list($limit),
        ];
    }

    public function myRatings($user, int $limit = 20): array
    {
        if (!$user) {
            return [
                'status' => false,
                'message' => 'Unauthenticated',
                'code' => 401,
            ];
        }

        return [
            'status' => true,
            'message' => 'My ratings loaded successfully',
            'data' => $this->ratingRepository->myRatings($user->id, $limit),
        ];
    }

    public function store($user, array $data): array
    {
        if (!$user) {
            return [
                'status' => false,
                'message' => 'Unauthenticated',
                'code' => 401,
            ];
        }

        if (!empty($data['booking_id'])) {
            $exists = $this->ratingRepository->bookingRatingExists(
                $user->id,
                (int) $data['booking_id']
            );

            if ($exists) {
                return [
                    'status' => false,
                    'message' => 'Rating already submitted for this booking',
                    'code' => 422,
                ];
            }
        }

        try {
            $rating = $this->ratingRepository->store([
                'user_id' => $user->id,
                'booking_id' => $data['booking_id'] ?? null,
                'driver_id' => $data['driver_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'rating_for' => $data['rating_for'] ?? 'trip',
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'review' => $data['review'] ?? null,
                'images' => $data['images'] ?? null,
                'status' => $data['status'] ?? 'approved',
                'meta' => $data['meta'] ?? null,
            ]);

            return [
                'status' => true,
                'message' => 'Rating submitted successfully',
                'data' => $rating,
            ];
        } catch (\Throwable $e) {
            Log::error('Rating Store Error', [
                'user_id' => $user->id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to submit rating',
                'code' => 500,
                'errors' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    public function summary(): array
    {
        return [
            'status' => true,
            'message' => 'Rating summary loaded successfully',
            'data' => [
                'overall' => $this->ratingRepository->averageRating(),
                'driver' => $this->ratingRepository->averageRating('driver'),
                'vehicle' => $this->ratingRepository->averageRating('vehicle'),
                'trip' => $this->ratingRepository->averageRating('trip'),
                'self_drive' => $this->ratingRepository->averageRating('self_drive'),
                'tour' => $this->ratingRepository->averageRating('tour'),
                'app' => $this->ratingRepository->averageRating('app'),
            ],
        ];
    }
}