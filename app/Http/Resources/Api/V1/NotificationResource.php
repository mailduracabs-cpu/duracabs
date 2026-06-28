<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'title' => $this->title ?? '',
            'message' => $this->message ?? $this->body ?? '',
            'type' => $this->type ?? 'general',
            'is_read' => (bool) ($this->is_read ?? false),
            'created_at' => isset($this->created_at) ? (string) $this->created_at : null,
        ];
    }
}
