<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\Response;

class VehiclePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if($user->hasPermissionTo('View Vehicle')){
            return true;
        };

        return false;

    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->hasRole(['Admin', 'Transporter']);

    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if(  $user->hasPermissionTo('Create Vehical')){
            return true;
        };

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vehicle $vehicle): bool
    {
        if(  $user->hasPermissionTo('Update Vehical')){
            return true;
        };

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vehicle $vehicle): bool
    {
        if(  $user->hasPermissionTo('Delete Vehical')){
            return true;
        };

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Vehicle $vehicle): bool
    {
       return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Vehicle $vehicle): bool
    {
       return false;
    }
}
