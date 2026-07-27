<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Brand;
use Illuminate\Auth\Access\Response;

class BrandPolicy
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
        if($user->hasPermissionTo('View City')){
            return true;
        };
        
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Brand $brand): bool
    {
        if($user->hasPermissionTo('View City')){
            return true;
        };
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if($user->hasPermissionTo('Create City')){
            return true;
        };
        
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Brand $brand): bool
    {
        if($user->hasPermissionTo('Update City')){
            return true;
        };
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Brand $brand): bool
    {
        if($user->hasPermissionTo('Delete City')){
            return true;
        };
        
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Brand $brand): bool
    {
         return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Brand $brand): bool
    {
         return false;
    }
}
