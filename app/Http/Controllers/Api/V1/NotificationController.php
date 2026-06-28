<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(Request $request)
    {
        try {
            $mobile = $request->query('mobile');
            $userId = $request->query('user_id');

            return response()->json([
                'status' => true,
                'message' => 'Notifications fetched successfully',
                'data' => $this->notificationService->list($userId, $mobile),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch notifications',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function read(Request $request)
    {
        $request->validate([
            'notification_id' => 'nullable|integer',
            'mobile' => 'nullable|string',
            'user_id' => 'nullable|integer',
        ]);

        try {
            $this->notificationService->markAsRead(
                $request->input('notification_id'),
                $request->input('user_id'),
                $request->input('mobile')
            );

            return response()->json([
                'status' => true,
                'message' => 'Notification marked as read',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to update notification',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
