<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? null,
            'mobile' => $this->mobile ?? null,
            'address' => $this->address ?? null,
            'city' => $this->city ?? null,
            'state' => $this->state ?? null,
            'pincode' => $this->pincode ?? null,
            'landmark' => $this->landmark ?? null,
            'type' => $this->type ?? 'home',
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
