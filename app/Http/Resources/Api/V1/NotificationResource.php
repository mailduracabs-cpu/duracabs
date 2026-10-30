<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $raw = $this->resource;
        $value = static fn (string $key) => is_array($raw)
            ? ($raw[$key] ?? null) : ($raw->{$key} ?? null);
        $data = $value('data');
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        $data = is_array($data) ? $data : [];
        $text = static function (array $keys) use ($value, $data): string {
            foreach ($keys as $key) {
                foreach ([$value($key), $data[$key] ?? null] as $candidate) {
                    if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                        return trim((string) $candidate);
                    }
                }
            }
            return '';
        };
        $readAt = $value('read_at');
        $isRead = filled($readAt) || in_array($value('is_read'), [true, 1, '1', 'true'], true);
        $title = $text(['title', 'subject', 'heading']);
        $message = $text(['message', 'body', 'description', 'content']);
        $type = $text(['event', 'notification_type']);
        $type = $type ?: ($data['type'] ?? $value('type') ?? 'general');

        return [
            'id' => $value('id'),
            'notification_id' => $value('id'),
            'title' => $title,
            'message' => $message,
            'body' => $message,
            'type' => $type,
            'data' => $data,
            'is_read' => $isRead,
            'read_at' => filled($readAt) ? (string) $readAt : null,
            'created_at' => $value('created_at') !== null ? (string) $value('created_at') : null,
            'booking_id' => $text(['booking_id', 'booking_no', 'order_id']),
        ];
    }
}
