<?php

namespace App\Repositories;

use App\Models\Rating;

class RatingRepository
{
    public function list(int $limit = 20, string $status = 'approved')
    {
        return Rating::query()
            ->where('status', $status)
            ->latest()
            ->paginate($limit);
    }

    public function myRatings(int $userId, int $limit = 20)
    {
        return Rating::query()
            ->where('user_id', $userId)
            ->latest()
            ->paginate($limit);
    }

    public function store(array $data): Rating
    {
        return Rating::create($data);
    }

    public function bookingRatingExists(int $userId, int $bookingId): bool
    {
        return Rating::query()
            ->where('user_id', $userId)
            ->where('booking_id', $bookingId)
            ->exists();
    }

    public function averageRating(?string $ratingFor = null): float
    {
        $query = Rating::query()->where('status', 'approved');

        if ($ratingFor) {
            $query->where('rating_for', $ratingFor);
        }

        return round((float) $query->avg('rating'), 1);
    }
}