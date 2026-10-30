<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\FleetManagement\TransporterProfile;
use App\Services\PartnerMarketplaceFleet as Fleet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PartnerMarketplaceFleetController extends PartnerController
{
    private function vendor(Request $request): TransporterProfile
    {
        [$vendor, $role] = $this->context($request);
        abort_unless($role === 'vendor' && $vendor->isActive(), 403, 'Active verified vendor required.');
        return $vendor;
    }

    public function options(Request $request, int $post)
    {
        $vendor = $this->vendor($request); Fleet::ready();
        $input = $request->validate(['kind' => ['required', 'in:drivers,vehicles'], 'q' => ['nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'min:1']]);
        $listing = DB::table('partner_market_posts')->find($post);
        abort_unless($listing && $listing->kind === 'booking' && $listing->status === 'open', 409, 'Open booking required.');
        \App\Services\PartnerMarketplaceAlerts::gate($listing, (int) $vendor->id);
        abort_if((int) $listing->transporter_profile_id === (int) $vendor->id, 403, 'The receiving vendor shares their own driver and taxi.');
        $q = trim($input['q'] ?? '');
        if ($input['kind'] === 'drivers') {
            $query = Fleet::drivers($vendor)->whereNotNull('driving_licence_number')->where('driving_licence_number', '<>', '');
            if ($q !== '') $query->where('name', 'like', '%' . $q . '%');
            $page = $query->orderBy('name')->paginate(20);
            $items = collect($page->items())->map(fn ($row) => ['id' => $row->id, 'name' => $row->name])->values();
        } else {
            $query = Fleet::vehicles($vendor);
            if ($q !== '') $query->where(function ($where) use ($q) { $where->where('vehicle_number', 'like', '%' . $q . '%')->orWhere('car_company_name', 'like', '%' . $q . '%')->orWhere('model_name', 'like', '%' . $q . '%'); });
            $page = $query->orderBy('vehicle_number')->paginate(20);
            $items = collect($page->items())->map(fn ($row) => ['id' => $row->id, 'name' => trim($row->car_company_name . ' ' . $row->model_name), 'vehicle_number' => $row->vehicle_number,
                'car_classification' => $row->car_classification, 'matches_booking' => Fleet::matches($listing->vehicle_class, (string) $row->car_classification, trim($row->car_company_name . ' ' . $row->model_name))])->values();
        }
        return response()->json(['status' => true, 'data' => ['items' => $items, 'page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'required_vehicle_class' => $listing->vehicle_class]])->header('Cache-Control', 'no-store, private');
    }

    public function send(Request $request, int $post)
    {
        $vendor = $this->vendor($request); Fleet::ready();
        $input = $request->validate(['driver_id' => ['required', 'integer', 'min:1'], 'vehicle_id' => ['required', 'integer', 'min:1'],
            'expected_revision' => ['required', 'integer', 'min:1'], 'client_request_id' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9-]{16,80}$/']]);
        $paths = [];
        try {
            $id = DB::transaction(function () use ($post, $vendor, $input, &$paths): int {
                $listing = DB::table('partner_market_posts')->where('id', $post)->lockForUpdate()->first();
                abort_unless($listing && $listing->kind === 'booking', 404);
                \App\Services\PartnerMarketplaceAlerts::gate($listing, (int) $vendor->id);
                abort_if((int) $listing->transporter_profile_id === (int) $vendor->id, 403);
                $previous = DB::table('partner_market_fleet_submissions')->where('vendor_id', $vendor->id)->where('client_request_id', $input['client_request_id'])->first();
                if ($previous) {
                    abort_unless((int) $previous->post_id === $post && (int) $previous->driver_id === (int) $input['driver_id'] && (int) $previous->vehicle_id === (int) $input['vehicle_id'] && (int) $previous->post_revision === (int) $input['expected_revision'], 409, 'This send request belongs to different details. Refresh before sending.');
                    return (int) $previous->id;
                }
                abort_unless($listing->status === 'open' && !$listing->active_payment_id, 409, 'Fleet details are locked while paying or after assignment/cancellation.');
                abort_unless((int) $listing->revision === (int) $input['expected_revision'], 409, 'Booking changed. Review its latest details before sharing your fleet.');
                $driver = Fleet::drivers($vendor)->whereKey($input['driver_id'])->lockForUpdate()->first();
                abort_unless($driver && $driver->canUseDriverLogin() && trim((string) $driver->name) !== '' && trim((string) $driver->driving_licence_number) !== '', 422, 'Select your saved active driver with complete name and licence details.');
                $vehicle = Fleet::vehicles($vendor)->with('frontMedia')->whereKey($input['vehicle_id'])->lockForUpdate()->first();
                abort_unless($vehicle, 422, 'Select an approved active taxi from your own fleet.');
                $name = trim($vehicle->car_company_name . ' ' . $vehicle->model_name);
                abort_unless($name !== '' && trim((string) $vehicle->vehicle_number) !== '', 422, 'Complete the saved taxi name and number first.');
                abort_unless(Fleet::matches($listing->vehicle_class, (string) $vehicle->car_classification, $name), 422, 'Selected taxi does not match this booking vehicle type.');
                $photo = Fleet::photo($vehicle);
                $path = 'partner-market-fleet/' . Str::uuid() . '.' . $photo['extension']; $paths[] = $path;
                abort_unless(Storage::disk('local')->put($path, $photo['bytes']), 503, 'Car photo could not be saved. No details were sent.');
                $id = DB::table('partner_market_fleet_submissions')->insertGetId([
                    'post_id' => $post, 'vendor_id' => $vendor->id, 'post_revision' => $listing->revision,
                    'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id, 'driver_name' => $driver->name,
                    'taxi_name' => $name, 'taxi_number' => $vehicle->vehicle_number, 'car_classification' => $vehicle->car_classification,
                    'photo_path' => $path, 'photo_mime' => $photo['mime'], 'client_request_id' => $input['client_request_id'], 'created_at' => now(),
                ]);
                DB::table('partner_market_messages')->insert([
                    'post_id' => $post, 'sender_vendor_id' => $vendor->id, 'recipient_vendor_id' => $listing->transporter_profile_id,
                    'fleet_submission_id' => $id, 'message' => Fleet::text($driver->name, $name, $vehicle->vehicle_number), 'created_at' => now(), 'updated_at' => now(),
                ]);
                return $id;
            }, 3);
        } finally {
            // A transaction retry/rollback must not leave an orphan photo; never delete a committed snapshot.
            foreach ($paths as $path) if (!DB::table('partner_market_fleet_submissions')->where('photo_path', $path)->exists()) Storage::disk('local')->delete($path);
        }
        return response()->json(['status' => true, 'data' => Fleet::summary(DB::table('partner_market_fleet_submissions')->find($id)), 'message' => 'Saved driver, taxi details and car photo sent to booking chat.'], 201);
    }

    /** Signed photo-only link, issued only in an authorized vendor/admin response. */
    public function photo(Request $request, int $submission)
    {
        $row = DB::table('partner_market_fleet_submissions')->find($submission);
        abort_unless($row && str_starts_with($row->photo_path, 'partner-market-fleet/') && !str_contains($row->photo_path, '..') && Storage::disk('local')->exists($row->photo_path), 404);
        return Storage::disk('local')->response($row->photo_path, 'taxi-photo', ['Content-Type' => $row->photo_mime,
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer']);
    }
}
