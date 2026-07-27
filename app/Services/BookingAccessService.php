<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SelfDriveBooking;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class BookingAccessService
{
    public function authorizeOrder(
        ?User $user,
        Order $order,
        string $unauthenticatedMessage = 'Please login to view this booking.'
    ): void {
        abort_if(
            ! $user,
            Response::HTTP_UNAUTHORIZED,
            $unauthenticatedMessage
        );

        if ($this->canAccessOrder($user, $order)) {
            return;
        }

        abort(
            Response::HTTP_FORBIDDEN,
            'You are not allowed to access this booking.'
        );
    }

    public function authorizeSelfDriveBooking(
        ?User $user,
        SelfDriveBooking $booking,
        string $unauthenticatedMessage = 'Please login to view this booking.'
    ): void {
        abort_if(
            ! $user,
            Response::HTTP_UNAUTHORIZED,
            $unauthenticatedMessage
        );

        if ($this->canAccessSelfDriveBooking($user, $booking)) {
            return;
        }

        abort(
            Response::HTTP_FORBIDDEN,
            'You are not allowed to access this booking.'
        );
    }

    public function canAccessOrder(User $user, Order $order): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ((int) $order->user_id === (int) $user->id) {
            return true;
        }

        if (
            $this->hasRole($user, 'Transporter')
            && (int) ($order->transporter_id ?? 0) === (int) $user->id
        ) {
            return true;
        }

        return $this->hasRole($user, 'Driver')
            && (int) ($order->driver_id ?? 0) === (int) $user->id;
    }

    public function canAccessSelfDriveBooking(
        User $user,
        SelfDriveBooking $booking
    ): bool {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ((int) $booking->customer_id === (int) $user->id) {
            return true;
        }

        if (! $this->hasRole($user, 'Transporter')) {
            return false;
        }

        $booking->loadMissing('transporter');

        return (int) ($booking->transporter?->user_id ?? 0)
            === (int) $user->id;
    }

    private function isAdmin(User $user): bool
    {
        return $this->hasRole($user, 'Admin');
    }

    private function hasRole(User $user, string $role): bool
    {
        return method_exists($user, 'hasRole')
            && $user->hasRole($role);
    }
}
