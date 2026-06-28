<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id ?? null,
            'booking_id' => isset($this->id) ? 'DC' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT) : null,
            'status' => $this->status ?? null,
            'ride_type' => $this->ride_type ?? null,
            'pickup' => $this->booking_from ?? null,
            'drop' => $this->booking_to ?? null,
            'date' => $this->date ?? null,
            'time' => $this->time ?? null,
            'grand_total' => (float) ($this->grand_total ?? 0),
            'payment_status' => $this->payment_status ?? null,
            'created_at' => $this->created_at ?? null,
        ];
    }
}
