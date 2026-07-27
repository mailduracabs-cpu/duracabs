<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;
use Illuminate\Auth\Access\Response;

class OrderPolicy
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
        if($user->hasPermissionTo('View Order')){
            return true;
        };
        
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        if($user->hasPermissionTo('View Order')){
            return true;
        };
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if($user->hasPermissionTo('Create Order')){
            return true;
        };
        
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): bool
    {
        if($user->hasPermissionTo('Update Order')){
            return true;
        };
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        if($user->hasPermissionTo('Delete Order')){
            return true;
        };
        
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
         return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
         return false;
    }
}
