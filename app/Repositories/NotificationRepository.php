<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationRepository
{
    public function list($userId = null, $mobile = null, int $limit = 50)
    {
        if (!Schema::hasTable('notifications')) {
            return collect([]);
        }

        $query = DB::table('notifications')->latest()->limit($limit);

        if ($userId && Schema::hasColumn('notifications', 'user_id')) {
            $query->where('user_id', $userId);
        }

        if ($mobile && Schema::hasColumn('notifications', 'mobile')) {
            $query->where('mobile', $mobile);
        }

        return $query->get();
    }

    public function markAsRead($notificationId = null, $userId = null, $mobile = null): void
    {
        if (!Schema::hasTable('notifications') || !Schema::hasColumn('notifications', 'is_read')) {
            return;
        }

        $query = DB::table('notifications');

        if ($notificationId) {
            $query->where('id', $notificationId);
        } elseif ($userId && Schema::hasColumn('notifications', 'user_id')) {
            $query->where('user_id', $userId);
        } elseif ($mobile && Schema::hasColumn('notifications', 'mobile')) {
            $query->where('mobile', $mobile);
        } else {
            return;
        }

        $query->update(['is_read' => 1, 'updated_at' => now()]);
    }
}
