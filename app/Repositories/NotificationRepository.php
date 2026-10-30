<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NotificationRepository
{
    private function columns(): array
    {
        return Schema::hasTable('notifications')
            ? Schema::getColumnListing('notifications') : [];
    }

    private function ownedQuery(array $columns, $userId, $mobile)
    {
        $query = DB::table('notifications');
        // Always scope list, count and updates to the same owner.
        if ($userId && in_array('user_id', $columns, true)) {
            return $query->where('user_id', $userId);
        }
        if (in_array('notifiable_id', $columns, true)
            && in_array('notifiable_type', $columns, true)) {
            $user = $userId ? User::query()->find($userId) : null;
            if (! $user && ! $userId && filled($mobile)) {
                $user = User::query()->where('mobile', trim((string) $mobile))->first();
            }
            return $user
                ? $query->where('notifiable_id', $user->getKey())
                    ->whereIn('notifiable_type', array_unique([User::class, $user->getMorphClass()]))
                : $query->whereRaw('1 = 0');
        }
        if (filled($mobile) && in_array('mobile', $columns, true)) {
            return $query->where('mobile', trim((string) $mobile));
        }
        return $query->whereRaw('1 = 0');
    }

    public function list($userId = null, $mobile = null, int $limit = 50)
    {
        $columns = $this->columns();
        if ($columns === []) {
            return collect([]);
        }
        $query = $this->ownedQuery($columns, $userId, $mobile);
        if (in_array('created_at', $columns, true)) {
            $query->orderByDesc('created_at');
        }
        return $query->limit(max(1, min(100, $limit)))->get();
    }

    public function markAsRead($notificationId = null, $userId = null, $mobile = null): void
    {
        $columns = $this->columns();
        if ($columns === []) {
            return;
        }
        $query = $this->ownedQuery($columns, $userId, $mobile);
        if ($notificationId !== null && (string) $notificationId !== '') {
            $query->where('id', $notificationId);
        }
        $updates = [];
        if (in_array('is_read', $columns, true)) {
            $updates['is_read'] = 1;
        }
        if (in_array('read_at', $columns, true)) {
            $updates['read_at'] = now();
        }
        if ($updates === []) {
            return;
        }
        if (in_array('updated_at', $columns, true)) {
            $updates['updated_at'] = now();
        }
        $query->update($updates);
    }

    public function unreadCount($userId = null, $mobile = null): int
    {
        $columns = $this->columns();
        if ($columns === []) {
            return 0;
        }
        $query = $this->ownedQuery($columns, $userId, $mobile);
        if (in_array('read_at', $columns, true)) {
            $query->whereNull('read_at');
        }
        if (in_array('is_read', $columns, true)) {
            $query->where(function ($unread): void {
                $unread->where('is_read', 0)->orWhereNull('is_read');
            });
        }
        if (! in_array('read_at', $columns, true) && ! in_array('is_read', $columns, true)) {
            return 0;
        }
        return $query->count();
    }

    public function create(array $data): bool
    {
        $columns = $this->columns();
        if ($columns === []) {
            return false;
        }
        $payload = is_array($data['data'] ?? null) ? $data['data'] : [];
        $message = (string) ($data['message'] ?? $data['body'] ?? '');
        $payload = array_merge($payload, [
            'title' => (string) ($data['title'] ?? 'Dura Cabs'),
            'message' => $message,
            'body' => $message,
            'type' => $data['type'] ?? $payload['type'] ?? 'general',
        ]);
        $row = array_intersect_key($data, array_flip($columns));
        unset($row['id']);
        if (in_array('notifiable_id', $columns, true)) {
            $user = isset($data['user_id']) ? User::query()->find($data['user_id']) : null;
            if (! $user && filled($data['mobile'] ?? null)) {
                $user = User::query()->where('mobile', $data['mobile'])->first();
            }
            if (! $user) {
                return false;
            }
            $row['id'] = (string) Str::uuid();
            $row['notifiable_id'] = $user->getKey();
            $row['notifiable_type'] = $user->getMorphClass();
            $row['type'] = (string) ($data['type'] ?? 'general');
        } elseif (empty($data['user_id']) && blank($data['mobile'] ?? null)) {
            return false;
        }
        if (in_array('data', $columns, true)) {
            $row['data'] = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }
        if (in_array('is_read', $columns, true)) {
            $row['is_read'] = (bool) ($data['is_read'] ?? false);
        }
        if (in_array('read_at', $columns, true)) {
            $row['read_at'] = $data['read_at'] ?? null;
        }
        foreach (['created_at', 'updated_at'] as $column) {
            if (in_array($column, $columns, true)) {
                $row[$column] = now();
            }
        }
        return DB::table('notifications')->insert($row);
    }
}
