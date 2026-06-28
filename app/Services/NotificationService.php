<?php

namespace App\Services;

use App\Repositories\NotificationRepository;
use App\Http\Resources\Api\V1\NotificationResource;

class NotificationService
{
    public function __construct(private NotificationRepository $notificationRepository) {}

    public function list($userId = null, $mobile = null)
    {
        return NotificationResource::collection(
            $this->notificationRepository->list($userId, $mobile)
        )->resolve();
    }

    public function markAsRead($notificationId = null, $userId = null, $mobile = null): void
    {
        $this->notificationRepository->markAsRead($notificationId, $userId, $mobile);
    }
}
