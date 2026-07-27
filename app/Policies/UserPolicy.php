<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Give the main Admin role complete access to this resource.
     * Other roles continue to use the existing policy checks below.
     */
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('Admin') ? true : null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Admin', 'Transporter','moderator','Driver']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, user $model): bool
    {
        return $user->hasRole('Admin','Transporter');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Admin','Transporter']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return $user->hasRole(['Admin','moderator','Transporter','Driver']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, user $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, user $model): bool
    {
        return false;
    }
}
