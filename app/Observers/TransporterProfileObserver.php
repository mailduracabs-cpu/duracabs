<?php

namespace App\Observers;

use App\Models\FleetManagement\TransporterProfile;
use App\Models\SelfDriveVendor;

class TransporterProfileObserver
{
    public function saved(TransporterProfile $profile): void
    {
        if (! in_array($profile->partner_type, ['host', 'both'], true)) {
            return;
        }

        SelfDriveVendor::withoutEvents(function () use ($profile) {
            SelfDriveVendor::updateOrCreate(
                ['vendor_id' => $profile->id],
                [
                    'office_name' => $profile->company_name,
                    'mobile' => $profile->mobile,
                    'pickup_address' => $profile->office_address,
                    'latitude' => $profile->pickup_latitude,
                    'longitude' => $profile->pickup_longitude,
                    'service_radius_km' => $profile->service_radius_km ?: 40,
                    'is_active' => (bool) $profile->status,
                ]
            );
        });
    }
}