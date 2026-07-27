<?php

namespace App\Policies;

use App\Models\AppMedia;
use App\Models\User;

class AppMediaPolicy
{
    public function before(
        User $user,
        string $ability,
    ): ?bool {
        if ($this->isAdmin($user)) {
            return true;
        }

        return null;
    }

    public function viewAny(
        User $user,
    ): bool {
        return true;
    }

    public function view(
        User $user,
        AppMedia $media,
    ): bool {
        if ($media->is_public) {
            return true;
        }

        return (int) $media->uploaded_by
            === (int) $user->getKey();
    }

    public function create(
        User $user,
    ): bool {
        return true;
    }

    public function update(
        User $user,
        AppMedia $media,
    ): bool {
        return (int) $media->uploaded_by
            === (int) $user->getKey();
    }

    public function delete(
        User $user,
        AppMedia $media,
    ): bool {
        if ($media->hasReferences()) {
            return false;
        }

        return (int) $media->uploaded_by
            === (int) $user->getKey();
    }

    public function restore(
        User $user,
        AppMedia $media,
    ): bool {
        return (int) $media->uploaded_by
            === (int) $user->getKey();
    }

    public function forceDelete(
        User $user,
        AppMedia $media,
    ): bool {
        return false;
    }

    private function isAdmin(
        User $user,
    ): bool {
        if (
            method_exists($user, 'hasAnyRole')
        ) {
            return $user->hasAnyRole([
                'admin',
                'super_admin',
                'super-admin',
            ]);
        }

        if (
            method_exists($user, 'hasRole')
        ) {
            return $user->hasRole('admin')
                || $user->hasRole(
                    'super_admin'
                );
        }

        return false;
    }
}