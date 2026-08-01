<?php

namespace App\Services;

use App\Models\SelfDriveBooking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class SelfDriveAvailabilityService
{
    private const DEFAULT_SERVICE_RADIUS_KM = 40.0;

    public function check(array $data): array
    {
        try {
            [$start, $end] = $this->dateRange($data);
        } catch (InvalidArgumentException $exception) {
            return $this->failure($exception->getMessage(), 422);
        }

        $vehicleId = (int) ($data['vehicle_id'] ?? 0);

        if ($vehicleId <= 0) {
            return $this->failure('Vehicle is required.', 422);
        }

        $vehicle = $this->customerVisibleVehicleQuery()
            ->with('transporter')
            ->find($vehicleId);

        if (! $vehicle) {
            return $this->failure(
                'Self drive vehicle is not available for customers.',
                404
            );
        }

        $minimumHours = max(1, (int) ($vehicle->minimum_booking_hours ?? 1));
        $selectedHours = $this->selectedHours($start, $end);

        if ($selectedHours < $minimumHours) {
            return $this->failure(
                "Minimum booking duration is {$minimumHours} hour(s).",
                422,
                [
                    'available' => false,
                    'vehicle_id' => $vehicle->id,
                    'reason_code' => 'minimum_duration_not_met',
                    'minimum_booking_hours' => $minimumHours,
                    'selected_hours' => $selectedHours,
                    'start_datetime' => $start->toIso8601String(),
                    'end_datetime' => $end->toIso8601String(),
                ]
            );
        }

        $maximumHours = (int) ($vehicle->maximum_booking_hours ?? 0);

        if ($maximumHours > 0 && $selectedHours > $maximumHours) {
            return $this->failure(
                "Maximum booking duration is {$maximumHours} hour(s).",
                422,
                [
                    'available' => false,
                    'vehicle_id' => $vehicle->id,
                    'reason_code' => 'maximum_duration_exceeded',
                    'maximum_booking_hours' => $maximumHours,
                    'selected_hours' => $selectedHours,
                    'start_datetime' => $start->toIso8601String(),
                    'end_datetime' => $end->toIso8601String(),
                ]
            );
        }

        $excludeBookingId = isset($data['exclude_booking_id'])
            ? (int) $data['exclude_booking_id']
            : null;

        $conflict = $this->conflictingBooking(
            vehicleId: $vehicle->id,
            start: $start,
            end: $end,
            excludeBookingId: $excludeBookingId
        );

        if ($conflict) {
            return $this->failure(
                'Vehicle is unavailable for the selected period.',
                409,
                [
                    'available' => false,
                    'vehicle_id' => $vehicle->id,
                    'reason_code' => 'booking_conflict',
                    'start_datetime' => $start->toIso8601String(),
                    'end_datetime' => $end->toIso8601String(),
                    'next_available_at' => $this->nextAvailableAt(
                        vehicleId: $vehicle->id,
                        requestedEnd: $end,
                        excludeBookingId: $excludeBookingId
                    )?->toIso8601String(),
                ]
            );
        }

        $distance = $this->customerDistance($vehicle, $data);

        if ($distance !== null) {
            $radius = $this->serviceRadius($vehicle);

            if ($distance > $radius) {
                return $this->failure(
                    'Pickup location is outside the vendor service area.',
                    422,
                    [
                        'available' => false,
                        'vehicle_id' => $vehicle->id,
                        'reason_code' => 'outside_service_radius',
                        'distance_km' => round($distance, 2),
                        'service_radius_km' => round($radius, 2),
                        'start_datetime' => $start->toIso8601String(),
                        'end_datetime' => $end->toIso8601String(),
                    ]
                );
            }
        }

        return $this->success(
            'Vehicle is available.',
            [
                'available' => true,
                'vehicle_id' => $vehicle->id,
                'reason_code' => null,
                'distance_km' => $distance !== null ? round($distance, 2) : null,
                'service_radius_km' => round($this->serviceRadius($vehicle), 2),
                'selected_hours' => $selectedHours,
                'chargeable_hours' => max($selectedHours, $minimumHours),
                'minimum_booking_hours' => $minimumHours,
                'maximum_booking_hours' => $maximumHours > 0 ? $maximumHours : null,
                'start_datetime' => $start->toIso8601String(),
                'end_datetime' => $end->toIso8601String(),
                'next_available_at' => null,
            ]
        );
    }

    public function search(array $data): array
    {
        try {
            [$start, $end] = $this->dateRange($data);
        } catch (InvalidArgumentException $exception) {
            return $this->failure($exception->getMessage(), 422);
        }

        $pickupLat = $this->nullableFloat(
            $data['pickup_lat'] ?? $data['pickup_latitude'] ?? null
        );
        $pickupLng = $this->nullableFloat(
            $data['pickup_lng'] ?? $data['pickup_longitude'] ?? null
        );

        if ($pickupLat === null || $pickupLng === null) {
            return $this->failure(
                'Pickup latitude and longitude are required.',
                422
            );
        }

        $query = $this->customerVisibleVehicleQuery()
            ->with([
                'transporter',
                'frontMedia',
                'backMedia',
                'interiorMedia',
            ])
            ->whereNotNull('transporter_profile_id')
            ->whereHas('transporter', function (Builder $query): void {
                $query
                    ->whereIn('partner_type', ['host', 'vendor', 'both'])
                    ->where('status', true)
                    ->where(function (Builder $locationQuery): void {
                        $locationQuery
                            ->where(function (Builder $query): void {
                                $query
                                    ->whereNotNull('pickup_latitude')
                                    ->whereNotNull('pickup_longitude');
                            })
                            ->orWhere(function (Builder $query): void {
                                $query
                                    ->whereNotNull('pickup_lat')
                                    ->whereNotNull('pickup_lng');
                            });
                    });
            })
            ->whereDoesntHave(
                'selfDriveBookings',
                function (Builder $query) use ($start, $end, $data): void {
                    $query
                        ->activeBooking()
                        ->overlapping($start, $end);

                    if (! empty($data['exclude_booking_id'])) {
                        $query->where(
                            'id',
                            '!=',
                            (int) $data['exclude_booking_id']
                        );
                    }
                }
            );

        $this->applySearchFilters($query, $data);

        $vehicles = $query
            ->latest('id')
            ->get()
            ->map(function (Vehicle $vehicle) use (
                $pickupLat,
                $pickupLng,
                $start,
                $end,
                $data
            ): ?array {
                $host = $vehicle->transporter;

                if (! $host) {
                    return null;
                }

                $hostLat = $this->nullableFloat(
                    $host->pickup_latitude ?? $host->pickup_lat ?? null
                );
                $hostLng = $this->nullableFloat(
                    $host->pickup_longitude ?? $host->pickup_lng ?? null
                );

                if ($hostLat === null || $hostLng === null) {
                    return null;
                }

                $distance = $this->distanceKm(
                    $pickupLat,
                    $pickupLng,
                    $hostLat,
                    $hostLng
                );

                $radius = $this->serviceRadius($vehicle);

                if ($distance > $radius) {
                    return null;
                }

                $minimumHours = max(
                    1,
                    (int) ($vehicle->minimum_booking_hours ?? 1)
                );
                $selectedHours = $this->selectedHours($start, $end);
                $maximumHours = (int) ($vehicle->maximum_booking_hours ?? 0);

                if ($selectedHours < $minimumHours) {
                    return null;
                }

                if ($maximumHours > 0 && $selectedHours > $maximumHours) {
                    return null;
                }

                return $this->vehicleData(
                    vehicle: $vehicle,
                    distance: $distance,
                    pickupLocation: (string) ($data['pickup_location'] ?? ''),
                    start: $start,
                    end: $end
                );
            })
            ->filter()
            ->sortBy('distance_km')
            ->values();

        return $this->success(
            'Available vehicles loaded.',
            [
                'vehicles' => $vehicles,
                'count' => $vehicles->count(),
                'pickup_location' => $data['pickup_location'] ?? null,
                'start_datetime' => $start->toIso8601String(),
                'end_datetime' => $end->toIso8601String(),
            ]
        );
    }

    /**
     * Homepage banner listing.
     *
     * This intentionally does not apply booking-conflict or rental-duration
     * filters. Final availability is checked after the customer selects the
     * pickup and return date/time from the booking popup.
     */
    public function homepageVehicles(array $data): array
    {
        $pickupLat = $this->nullableFloat(
            $data['pickup_lat'] ?? $data['pickup_latitude'] ?? null
        );
        $pickupLng = $this->nullableFloat(
            $data['pickup_lng'] ?? $data['pickup_longitude'] ?? null
        );

        if ($pickupLat === null || $pickupLng === null) {
            return $this->failure(
                'Pickup latitude and longitude are required.',
                422
            );
        }

        $vehicles = $this->customerVisibleVehicleQuery()
            ->with([
                'transporter',
                'frontMedia',
                'backMedia',
                'interiorMedia',
            ])
            ->whereNotNull('transporter_profile_id')
            ->whereHas('transporter', function (Builder $query): void {
                $query
                    ->whereIn('partner_type', ['host', 'vendor', 'both'])
                    ->where('status', true)
                    ->whereNotNull('pickup_latitude')
                    ->whereNotNull('pickup_longitude');
            })
            ->latest('id')
            ->get()
            ->map(function (Vehicle $vehicle) use (
                $pickupLat,
                $pickupLng,
                $data
            ): ?array {
                $host = $vehicle->transporter;

                if (! $host) {
                    return null;
                }

                $hostLat = $this->nullableFloat($host->pickup_latitude);
                $hostLng = $this->nullableFloat($host->pickup_longitude);

                if ($hostLat === null || $hostLng === null) {
                    return null;
                }

                $distance = $this->distanceKm(
                    $pickupLat,
                    $pickupLng,
                    $hostLat,
                    $hostLng
                );

                if ($distance > $this->serviceRadius($vehicle)) {
                    return null;
                }

                return $this->homepageVehicleData(
                    vehicle: $vehicle,
                    distance: $distance,
                    pickupLocation: (string) ($data['pickup_location'] ?? '')
                );
            })
            ->filter()
            ->sortBy('distance_km')
            ->values();

        return $this->success(
            'Homepage self drive vehicles loaded.',
            [
                'vehicles' => $vehicles,
                'count' => $vehicles->count(),
                'pickup_location' => $data['pickup_location'] ?? null,
            ]
        );
    }

    public function isAvailable(
        int $vehicleId,
        Carbon|string $start,
        Carbon|string $end,
        ?int $excludeBookingId = null
    ): bool {
        $startAt = $start instanceof Carbon
            ? $start->copy()
            : Carbon::parse($start);
        $endAt = $end instanceof Carbon
            ? $end->copy()
            : Carbon::parse($end);

        if ($endAt->lte($startAt)) {
            return false;
        }

        return ! $this->conflictingBooking(
            vehicleId: $vehicleId,
            start: $startAt,
            end: $endAt,
            excludeBookingId: $excludeBookingId
        );
    }

    public function conflictingBooking(
        int $vehicleId,
        Carbon $start,
        Carbon $end,
        ?int $excludeBookingId = null
    ): ?SelfDriveBooking {
        return SelfDriveBooking::query()
            ->where('vehicle_id', $vehicleId)
            ->activeBooking()
            ->overlapping($start, $end)
            ->when(
                $excludeBookingId,
                fn (Builder $query) => $query->where(
                    'id',
                    '!=',
                    $excludeBookingId
                )
            )
            ->orderBy('start_datetime')
            ->first();
    }

    public function extension(array $data): array
    {
        if (empty($data['exclude_booking_id'])) {
            return $this->failure(
                'Current booking id is required for extension check.',
                422
            );
        }

        return $this->check($data);
    }

    public function dateRange(array $data): array
    {
        try {
            $start = ! empty($data['start_datetime'])
                ? Carbon::parse($data['start_datetime'])
                : Carbon::parse(
                    trim(
                        (string) ($data['start_date'] ?? '')
                        . ' '
                        . (string) ($data['start_time'] ?? '')
                    )
                );

            $end = ! empty($data['end_datetime'])
                ? Carbon::parse($data['end_datetime'])
                : Carbon::parse(
                    trim(
                        (string) ($data['end_date'] ?? '')
                        . ' '
                        . (string) ($data['end_time'] ?? '')
                    )
                );
        } catch (\Throwable) {
            throw new InvalidArgumentException(
                'Valid start and end date/time are required.'
            );
        }

        if ($start->lt(now()->subMinute())) {
            throw new InvalidArgumentException(
                'Start time cannot be in the past.'
            );
        }

        if ($end->lte($start)) {
            throw new InvalidArgumentException(
                'End time must be after start time.'
            );
        }

        return [$start, $end];
    }

    public function distanceKm(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadiusKm = 6371.0;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1))
            * cos(deg2rad($lat2))
            * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm
            * 2
            * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
    }

    private function customerVisibleVehicleQuery(): Builder
    {
        return Vehicle::query()
            ->availableForRental()
            ->where('service_type', Vehicle::SERVICE_SELF_DRIVE)
            ->where('vehicle_type', Vehicle::TYPE_CAR);
    }

    private function applySearchFilters(Builder $query, array $data): void
    {
        foreach (['fuel_type', 'transmission'] as $field) {
            if (! empty($data[$field])) {
                $query->where(
                    $field,
                    strtolower(trim((string) $data[$field]))
                );
            }
        }

        if (! empty($data['category'])) {
            $query->where(
                'car_classification',
                'like',
                '%' . trim((string) $data['category']) . '%'
            );
        }

        if (! empty($data['seats'])) {
            $query->where('seats', '>=', (int) $data['seats']);
        }

        if (! empty($data['vehicle_id'])) {
            $query->where('id', (int) $data['vehicle_id']);
        }
    }

    private function selectedHours(Carbon $start, Carbon $end): int
    {
        return max(1, (int) ceil($start->diffInMinutes($end) / 60));
    }

    private function customerDistance(Vehicle $vehicle, array $data): ?float
    {
        $pickupLat = $this->nullableFloat(
            $data['pickup_lat'] ?? $data['pickup_latitude'] ?? null
        );
        $pickupLng = $this->nullableFloat(
            $data['pickup_lng'] ?? $data['pickup_longitude'] ?? null
        );

        if ($pickupLat === null || $pickupLng === null) {
            return null;
        }

        $host = $vehicle->transporter;

        if (! $host) {
            return null;
        }

        $hostLat = $this->nullableFloat(
            $host->pickup_latitude ?? $host->pickup_lat ?? null
        );
        $hostLng = $this->nullableFloat(
            $host->pickup_longitude ?? $host->pickup_lng ?? null
        );

        if ($hostLat === null || $hostLng === null) {
            return null;
        }

        return $this->distanceKm(
            $pickupLat,
            $pickupLng,
            $hostLat,
            $hostLng
        );
    }

    private function serviceRadius(Vehicle $vehicle): float
    {
        $radius = (float) (
            $vehicle->transporter?->service_radius_km
            ?? self::DEFAULT_SERVICE_RADIUS_KM
        );

        return $radius > 0 ? $radius : self::DEFAULT_SERVICE_RADIUS_KM;
    }

    private function nextAvailableAt(
        int $vehicleId,
        Carbon $requestedEnd,
        ?int $excludeBookingId = null
    ): ?Carbon {
        $latestBlockingEnd = SelfDriveBooking::query()
            ->where('vehicle_id', $vehicleId)
            ->activeBooking()
            ->where('start_datetime', '<', $requestedEnd)
            ->when(
                $excludeBookingId,
                fn (Builder $query) => $query->where(
                    'id',
                    '!=',
                    $excludeBookingId
                )
            )
            ->max('end_datetime');

        return $latestBlockingEnd ? Carbon::parse($latestBlockingEnd) : null;
    }

    private function homepageVehicleData(
        Vehicle $vehicle,
        float $distance,
        string $pickupLocation
    ): array {
        $minimumHours = max(
            1,
            (int) ($vehicle->minimum_booking_hours ?? 1)
        );

        return [
            'id' => $vehicle->id,
            'vehicle_id' => $vehicle->id,
            'vehicle_name' => $vehicle->display_name,
            'car_company_name' => $vehicle->car_company_name,
            'model_name' => $vehicle->model_name,
            'brand' => $vehicle->car_company_name,
            'model' => $vehicle->model_name,
            'category' => $vehicle->car_classification,
            'fuel_type' => $vehicle->fuel_type,
            'transmission' => $vehicle->transmission,
            'color' => $vehicle->car_color,
            'manufacture_year' => $vehicle->manufacture_year,
            'seats' => (int) ($vehicle->seats ?? 0),
            'bags' => (int) ($vehicle->bags ?? 0),
            'hourly_price' => (float) ($vehicle->hourly_price ?? 0),
            'daily_price' => (float) ($vehicle->daily_price ?? 0),
            'weekly_price' => (float) ($vehicle->weekly_price ?? 0),
            'monthly_price' => (float) ($vehicle->monthly_price ?? 0),
            'security_deposit' => (float) ($vehicle->security_deposit ?? 0),
            'minimum_booking_hours' => $minimumHours,
            'maximum_booking_hours' =>
                (int) ($vehicle->maximum_booking_hours ?? 0) ?: null,
            'front_image' => $vehicle->front_image_url,
            'back_image' => $vehicle->back_image_url,
            'interior_image' => $vehicle->interior_image_url,
            'distance_km' => round($distance, 2),
            'service_radius_km' => round($this->serviceRadius($vehicle), 2),
            'pickup_location' => $pickupLocation,
            'pickup_address' =>
                $vehicle->transporter?->pickup_address
                ?? $vehicle->transporter?->office_address,
            'partner' => [
                'id' => $vehicle->transporter?->id,
                'company_name' => $vehicle->transporter?->company_name,
                'pickup_address' =>
                    $vehicle->transporter?->pickup_address
                    ?? $vehicle->transporter?->office_address,
            ],
        ];
    }

    private function vehicleData(
        Vehicle $vehicle,
        float $distance,
        string $pickupLocation,
        Carbon $start,
        Carbon $end
    ): array {
        $selectedHours = $this->selectedHours($start, $end);
        $minimumHours = max(1, (int) ($vehicle->minimum_booking_hours ?? 1));
        $chargeableHours = max($selectedHours, $minimumHours);

        return [
            'id' => $vehicle->id,
            'vehicle_name' => $vehicle->display_name,
            'brand' => $vehicle->car_company_name,
            'model' => $vehicle->model_name,
            'category' => $vehicle->car_classification,
            'fuel_type' => $vehicle->fuel_type,
            'transmission' => $vehicle->transmission,
            'color' => $vehicle->car_color,
            'manufacture_year' => $vehicle->manufacture_year,
            'seats' => (int) ($vehicle->seats ?? 0),
            'bags' => (int) ($vehicle->bags ?? 0),
            'hourly_price' => (float) ($vehicle->hourly_price ?? 0),
            'daily_price' => (float) ($vehicle->daily_price ?? 0),
            'weekly_price' => (float) ($vehicle->weekly_price ?? 0),
            'monthly_price' => (float) ($vehicle->monthly_price ?? 0),
            'security_deposit' => (float) ($vehicle->security_deposit ?? 0),
            'minimum_booking_hours' => $minimumHours,
            'maximum_booking_hours' =>
                (int) ($vehicle->maximum_booking_hours ?? 0) ?: null,
            'front_image' => $vehicle->front_image_url,
            'back_image' => $vehicle->back_image_url,
            'interior_image' => $vehicle->interior_image_url,
            'distance_km' => round($distance, 2),
            'service_radius_km' => round($this->serviceRadius($vehicle), 2),
            'pickup_location' => $pickupLocation,
            'start_datetime' => $start->toIso8601String(),
            'end_datetime' => $end->toIso8601String(),
            'selected_hours' => $selectedHours,
            'chargeable_hours' => $chargeableHours,
            'estimated_amount' => round(
                $chargeableHours * (float) ($vehicle->hourly_price ?? 0),
                2
            ),
            'partner' => [
                'id' => $vehicle->transporter?->id,
                'company_name' => $vehicle->transporter?->company_name,
                'pickup_address' =>
                    $vehicle->transporter?->pickup_address
                    ?? $vehicle->transporter?->office_address,
            ],
        ];
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function success(string $message, array $data): array
    {
        return [
            'status' => true,
            'message' => $message,
            'code' => 200,
            'data' => $data,
        ];
    }

    private function failure(
        string $message,
        int $code,
        ?array $data = null
    ): array {
        return [
            'status' => false,
            'message' => $message,
            'code' => $code,
            'data' => $data,
        ];
    }
}