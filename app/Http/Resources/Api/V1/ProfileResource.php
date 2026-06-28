<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile ?? $this->phone ?? null,
            'bookings_count' => $this->bookings_count ?? 0,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
