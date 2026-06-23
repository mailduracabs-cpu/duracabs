<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Reviews;
use Illuminate\Auth\Access\Response;

class ReviewsPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if($user->hasPermissionTo('View Review')){
            return true;
        };

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Reviews $reviews): bool
    {
        if($user->hasPermissionTo('View Review')){
            return true;
        };

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if($user->hasPermissionTo('Create Review')){
            return true;
        };

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Reviews $reviews): bool
    {
        if($user->hasPermissionTo('Update Review')){
            return true;
        };

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Reviews $reviews): bool
    {
        if($user->hasPermissionTo('Delete Review')){
            return true;
        };

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Reviews $reviews): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Reviews $reviews): bool
    {
        return false;
    }
}
