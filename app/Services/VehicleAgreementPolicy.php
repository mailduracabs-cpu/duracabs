<?php

namespace App\Services;

use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class VehicleAgreementPolicy
{
    public static function validate(Vehicle $vehicle): void
    {
        if (! $vehicle->isSelfDrive() || ! Schema::hasColumn('vehicles', 'partner_agreement_expires_on')) return;

        if ((! $vehicle->exists || $vehicle->isDirty('vehicle_number'))
            && ! preg_match('/^[A-Z0-9]{5,20}$/', (string) $vehicle->vehicle_number)) {
            self::reject('vehicle_number', 'Enter a valid car registration number.');
        }
        if (filled($vehicle->vehicle_number)) {
            $key = self::registrationKey($vehicle->vehicle_number);
            $vehicle->setAttribute('vehicle_registration_key', $key);
            if (Vehicle::withoutGlobalScopes()->where('vehicle_registration_key', $key)
                ->when($vehicle->exists, fn ($query) => $query->whereKeyNot($vehicle->id))->exists()) {
                self::reject('vehicle_number', 'This car is already attached. Admin must use its existing vehicle record.');
            }
        }

        $newAttachment = ! $vehicle->exists || $vehicle->isDirty('transporter_profile_id') || $vehicle->isDirty('service_type');
        if ($newAttachment && ! $vehicle->partner_joining_date) $vehicle->partner_joining_date = VendorLifecycleClock::today()->toDateString();
        $joining = $vehicle->partner_joining_date
            ? VendorLifecycleClock::date($vehicle->partner_joining_date->toDateString()) : null;
        $registration = $vehicle->first_registration_date
            ? VendorLifecycleClock::date($vehicle->first_registration_date->toDateString()) : null;

        if ($newAttachment || $vehicle->isDirty('first_registration_date') || $vehicle->isDirty('partner_joining_date')) {
            if (! $registration) self::reject('first_registration_date', 'Enter the RC first registration date.');
            if (! $joining) self::reject('partner_joining_date', 'Enter the actual joining date.');
            if ($registration->gt($joining) || $registration->gt(VendorLifecycleClock::today())) self::reject('first_registration_date', 'Registration must be on or before joining and today.');
            if ($joining->gt($registration->addYearsNoOverflow(3))) self::reject('first_registration_date', 'A car over 3 years old at joining cannot be attached.');
        }

        if ($joining) {
            $maximum = $joining->addYearsNoOverflow(3);
            if (! $vehicle->partner_agreement_expires_on) $vehicle->partner_agreement_expires_on = $maximum->toDateString();
            $expiry = VendorLifecycleClock::date($vehicle->partner_agreement_expires_on->toDateString());
            if ($expiry->lte($joining) || $expiry->gt($maximum)) {
                self::reject('partner_agreement_expires_on', 'Agreement expiry must be after joining and no later than joining + 3 years.');
            }
        } elseif ($vehicle->partner_agreement_expires_on) {
            self::reject('partner_joining_date', 'Joining date is required before setting agreement expiry.');
        }
        if ($vehicle->attachmentIsExpired()) {
            $vehicle->is_active = false;
            $vehicle->is_live = false;
        }
    }

    public static function registrationKey(string $number): ?string
    {
        $key = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $number));
        return $key === '' ? null : $key;
    }

    private static function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
