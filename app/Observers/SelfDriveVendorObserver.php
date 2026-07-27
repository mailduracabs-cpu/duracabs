<?php

namespace App\Observers;

use App\Models\FleetManagement\TransporterProfile;
use App\Models\SelfDriveVendor;

class SelfDriveVendorObserver
{
    public function saved(SelfDriveVendor $vendor): void
    {
        TransporterProfile::withoutEvents(function () use ($vendor) {
            $profile = TransporterProfile::updateOrCreate(
                ['mobile' => $vendor->mobile],
                [
                    'partner_type' => 'host',
                    'company_name' => $vendor->office_name,
                    'contact_person' => $vendor->office_name,
                    'office_address' => $vendor->pickup_address,
                    'pickup_latitude' => $vendor->latitude,
                    'pickup_longitude' => $vendor->longitude,
                    'service_radius_km' => $vendor->service_radius_km ?: 40,
                    'status' => (bool) $vendor->is_active,
                    'verification_status' => 'pending',
                ]
            );

            if ((int) $vendor->vendor_id !== (int) $profile->id) {
                $vendor->withoutEvents(function () use ($vendor, $profile) {
                    $vendor->update([
                        'vendor_id' => $profile->id,
                    ]);
                });
            }
        });
    }
}