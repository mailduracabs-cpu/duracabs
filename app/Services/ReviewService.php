<?php

namespace App\Services;

use App\Repositories\ReviewRepository;
use App\Http\Resources\Api\V1\ReviewResource;

class ReviewService
{
    public function __construct(private ReviewRepository $reviewRepository) {}

    public function list(int $limit = 20)
    {
        return ReviewResource::collection(
            $this->reviewRepository->list($limit)
        )->resolve();
    }

    public function store(array $data)
    {
        $review = $this->reviewRepository->store($data);
        return $review ? (new ReviewResource($review))->resolve() : $data;
    }
}
