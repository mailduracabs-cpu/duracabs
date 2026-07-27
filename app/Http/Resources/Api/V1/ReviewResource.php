<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'name' => $this->name ?? $this->customer_name ?? 'Customer',
            'rating' => (int) ($this->rating ?? 5),
            'message' => $this->message ?? $this->review ?? '',
            'created_at' => isset($this->created_at) ? (string) $this->created_at : null,
        ];
    }
}
