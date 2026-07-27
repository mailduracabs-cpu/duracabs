<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReviewRepository
{
    public function list(int $limit = 20)
    {
        if (!Schema::hasTable('reviews')) {
            return collect([]);
        }

        $query = DB::table('reviews')->latest()->limit($limit);

        if (Schema::hasColumn('reviews', 'is_active')) {
            $query->where('is_active', 1);
        }

        return $query->get();
    }

    public function store(array $data)
    {
        if (!Schema::hasTable('reviews')) {
            return $data;
        }

        $columns = Schema::getColumnListing('reviews');
        $payload = [];

        $map = [
            'user_id' => $data['user_id'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'name' => $data['name'] ?? null,
            'customer_name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'rating' => $data['rating'] ?? null,
            'message' => $data['message'] ?? null,
            'review' => $data['message'] ?? null,
            'booking_id' => $data['booking_id'] ?? null,
            'is_active' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach ($map as $key => $value) {
            if (in_array($key, $columns, true)) {
                $payload[$key] = $value;
            }
        }

        $id = DB::table('reviews')->insertGetId($payload);

        return DB::table('reviews')->where('id', $id)->first();
    }
}
