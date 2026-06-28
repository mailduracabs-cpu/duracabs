<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Collection;

class ProfileService
{
    public function getProfile(?User $user): User
    {
        abort_if(!$user, 401, 'Unauthenticated');
        return $user->loadCount(['orders as bookings_count']);
    }

    public function updateProfile(?User $user, array $data): User
    {
        abort_if(!$user, 401, 'Unauthenticated');

        $user->fill([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'mobile' => $data['mobile'] ?? $user->mobile,
        ]);
        $user->save();

        return $user->fresh();
    }

    public function addresses(?User $user): Collection
    {
        abort_if(!$user, 401, 'Unauthenticated');

        return Address::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    public function saveAddress(?User $user, array $data): Address
    {
        abort_if(!$user, 401, 'Unauthenticated');

        return Address::query()->updateOrCreate(
            [
                'id' => $data['address_id'] ?? null,
                'user_id' => $user->id,
            ],
            [
                'user_id' => $user->id,
                'name' => $data['name'] ?? $user->name,
                'mobile' => $data['mobile'] ?? $user->mobile,
                'address' => $data['address'],
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'landmark' => $data['landmark'] ?? null,
                'type' => $data['type'] ?? 'home',
            ]
        );
    }

    public function deleteAddress(?User $user, int $addressId): void
    {
        abort_if(!$user, 401, 'Unauthenticated');

        Address::query()
            ->where('user_id', $user->id)
            ->where('id', $addressId)
            ->delete();
    }
}
