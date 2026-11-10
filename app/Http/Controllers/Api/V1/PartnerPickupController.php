<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MediaType;
use App\Models\SelfDriveBooking;
use App\Services\Media\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartnerPickupController extends PartnerController
{
    private function booking(Request $request, int $id): SelfDriveBooking
    {
        [$profile, $role] = $this->context($request);
        abort_unless($role === 'host', 403, 'Self Drive host account required.');
        return SelfDriveBooking::query()
            ->where('transporter_profile_id', $profile->getKey())
            ->where('booking_type', 'car')->findOrFail($id);
    }

    public function show(Request $request, int $booking)
    {
        $row = $this->booking($request, $booking);
        return response()->json(['data' => [
            'booking_id' => $row->id,
            'booking_no' => $row->booking_no,
            'status' => $row->status,
            'vendor_confirmation_status' => $row->vendor_confirmation_status,
            'can_pickup' => $row->canPickup(),
            'start_km' => $row->start_km,
            'pickup_fuel_level' => $row->pickup_fuel_level,
            'started_at' => $row->trip_start_datetime?->toIso8601String(),
        ]]);
    }

    public function complete(Request $request, int $booking)
    {
        $owned = $this->booking($request, $booking);
        $data = $request->validate([
            'start_km' => 'required|numeric|min:0',
            'fuel_level' => 'required|string|max:50',
            'file' => 'required|image|mimes:jpeg,jpg,png,webp|max:10240',
        ]);
        if (! $owned->canPickup()) {
            throw ValidationException::withMessages(['booking' => 'Only a confirmed, not-yet-started booking can be picked up.']);
        }
        $media = app(MediaService::class)->uploadAndAttach(
            file: $request->file('file'),
            type: MediaType::Other,
            usable: $owned,
            fieldName: 'pickup_images',
            module: 'partner-self-drive-pickup',
            options: [
                'disk' => 'local',
                'is_public' => false,
                'deduplicate' => false,
                'usage_metadata' => ['booking_id' => $owned->id, 'inspection_type' => 'pickup'],
            ],
        );
        DB::transaction(function () use ($owned, $data, $media) {
            $row = SelfDriveBooking::query()
                ->where('transporter_profile_id', $owned->transporter_profile_id)
                ->lockForUpdate()->findOrFail($owned->id);
            if (! $row->canPickup()) {
                throw ValidationException::withMessages(['booking' => 'Booking is no longer eligible for pickup.']);
            }
            $row->start_km = $data['start_km'];
            $row->pickup_fuel_level = $data['fuel_level'];
            $row->pickup_images = [$media->storedPath()];
            $row->trip_start_datetime = now();
            $row->status = 'running';
            $row->booking_status = 'running';
            $row->save();
        });
        return response()->json(['message' => 'Start inspection saved. Trip started.']);
    }
}
