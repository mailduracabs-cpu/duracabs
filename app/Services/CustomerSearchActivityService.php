<?php

namespace App\Services;

use App\Models\CustomerActivity;
use App\Models\CustomerSearchActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CustomerSearchActivityService
{
    /*
    |--------------------------------------------------------------------------
    | Intent Score Defaults
    |--------------------------------------------------------------------------
    */

    private const SCORE_SEARCHED = 20;
    private const SCORE_RESULTS_VIEWED = 10;
    private const SCORE_VEHICLE_VIEWED = 10;
    private const SCORE_VEHICLE_SELECTED = 15;
    private const SCORE_CHECKOUT_STARTED = 20;
    private const SCORE_PAYMENT_STARTED = 15;

    /*
    |--------------------------------------------------------------------------
    | Search Creation
    |--------------------------------------------------------------------------
    */

    public function trackOneWaySearch(
        array $data,
        ?Request $request = null
    ): CustomerSearchActivity {
        return $this->createSearch(
            module: CustomerSearchActivity::MODULE_TAXI,
            serviceType: CustomerSearchActivity::SERVICE_ONE_WAY,
            data: $data,
            request: $request
        );
    }

    public function trackRoundTripSearch(
        array $data,
        ?Request $request = null
    ): CustomerSearchActivity {
        return $this->createSearch(
            module: CustomerSearchActivity::MODULE_TAXI,
            serviceType: CustomerSearchActivity::SERVICE_ROUND_TRIP,
            data: $data,
            request: $request
        );
    }

    public function trackLocalSearch(
        array $data,
        ?Request $request = null
    ): CustomerSearchActivity {
        return $this->createSearch(
            module: CustomerSearchActivity::MODULE_TAXI,
            serviceType: CustomerSearchActivity::SERVICE_LOCAL,
            data: $data,
            request: $request
        );
    }

    public function trackAirportSearch(
        array $data,
        ?Request $request = null
    ): CustomerSearchActivity {
        return $this->createSearch(
            module: CustomerSearchActivity::MODULE_TAXI,
            serviceType: CustomerSearchActivity::SERVICE_AIRPORT,
            data: $data,
            request: $request
        );
    }

    public function trackTourSearch(
        array $data,
        ?Request $request = null
    ): CustomerSearchActivity {
        return $this->createSearch(
            module: CustomerSearchActivity::MODULE_TAXI,
            serviceType: CustomerSearchActivity::SERVICE_TOUR,
            data: $data,
            request: $request
        );
    }

    public function trackSelfDriveSearch(
        array $data,
        ?Request $request = null
    ): CustomerSearchActivity {
        return $this->createSearch(
            module: CustomerSearchActivity::MODULE_SELF_DRIVE,
            serviceType: CustomerSearchActivity::SERVICE_SELF_DRIVE,
            data: $data,
            request: $request
        );
    }

    public function trackBikeRentalSearch(
        array $data,
        ?Request $request = null
    ): CustomerSearchActivity {
        return $this->createSearch(
            module: CustomerSearchActivity::MODULE_BIKE_RENTAL,
            serviceType: CustomerSearchActivity::SERVICE_BIKE_RENTAL,
            data: $data,
            request: $request
        );
    }

    public function createSearch(
        string $module,
        string $serviceType,
        array $data,
        ?Request $request = null
    ): CustomerSearchActivity {
        $request ??= request();

        return DB::transaction(function () use (
            $module,
            $serviceType,
            $data,
            $request
        ): CustomerSearchActivity {
            $user = $this->resolveUser($data);

            $payload = $this->buildSearchPayload(
                module: $module,
                serviceType: $serviceType,
                data: $data,
                user: $user,
                request: $request
            );

            $customerActivity = $this->createCustomerActivity(
                module: $module,
                serviceType: $serviceType,
                payload: $payload
            );

            if ($customerActivity !== null) {
                $payload['customer_activity_id'] = $customerActivity->id;
            }

            return CustomerSearchActivity::create($payload);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Search Lifecycle
    |--------------------------------------------------------------------------
    */

    public function markResultsViewed(
        CustomerSearchActivity|string|int $search,
        ?int $resultCount = null,
        ?bool $hasAvailableVehicle = null,
        ?float $minimumPrice = null,
        ?float $maximumPrice = null
    ): CustomerSearchActivity {
        $search = $this->resolveSearch($search);

        $search->update([
            'stage' => CustomerSearchActivity::STAGE_RESULTS_VIEWED,
            'result_count' => $resultCount ?? $search->result_count,
            'has_available_vehicle' => $hasAvailableVehicle
                ?? $search->has_available_vehicle,
            'minimum_result_price' => $minimumPrice
                ?? $search->minimum_result_price,
            'maximum_result_price' => $maximumPrice
                ?? $search->maximum_result_price,
            'intent_score' => $this->addScore(
                $search,
                self::SCORE_RESULTS_VIEWED
            ),
            'last_activity_at' => now(),
        ]);

        $this->updateLinkedActivity($search, [
            'event' => 'search_results_viewed',
            'stage' => 'results_viewed',
            'intent_score' => $search->intent_score,
            'priority' => $search->priority,
        ]);

        return $search->fresh();
    }

    public function markVehicleViewed(
        CustomerSearchActivity|string|int $search,
        array $vehicleData = []
    ): CustomerSearchActivity {
        $search = $this->resolveSearch($search);

        $search->update([
            'stage' => CustomerSearchActivity::STAGE_VEHICLE_VIEWED,
            'vehicle_id' => $vehicleData['vehicle_id']
                ?? $search->vehicle_id,
            'vehicle_category_id' => $vehicleData['vehicle_category_id']
                ?? $search->vehicle_category_id,
            'vehicle_name' => $vehicleData['vehicle_name']
                ?? $search->vehicle_name,
            'vehicle_category_name' =>
                $vehicleData['vehicle_category_name']
                ?? $search->vehicle_category_name,
            'vehicle_type' => $vehicleData['vehicle_type']
                ?? $search->vehicle_type,
            'fuel_type' => $vehicleData['fuel_type']
                ?? $search->fuel_type,
            'transmission_type' => $vehicleData['transmission_type']
                ?? $search->transmission_type,
            'estimated_amount' => $this->nullableFloat(
                $vehicleData['estimated_amount']
                    ?? $search->estimated_amount
            ),
            'grand_total' => $this->nullableFloat(
                $vehicleData['grand_total']
                    ?? $search->grand_total
            ),
            'intent_score' => $this->addScore(
                $search,
                self::SCORE_VEHICLE_VIEWED
            ),
            'last_activity_at' => now(),
        ]);

        $this->updateLinkedActivity($search, [
            'event' => 'vehicle_viewed',
            'stage' => 'viewed',
            'vehicle_id' => $search->vehicle_id,
            'vehicle_name' => $search->vehicle_name,
            'estimated_amount' => $search->estimated_amount,
            'intent_score' => $search->intent_score,
            'priority' => $search->priority,
        ]);

        return $search->fresh();
    }

    public function markVehicleSelected(
        CustomerSearchActivity|string|int $search,
        array $vehicleData = []
    ): CustomerSearchActivity {
        $search = $this->resolveSearch($search);

        $search->update([
            'stage' => CustomerSearchActivity::STAGE_VEHICLE_SELECTED,
            'vehicle_id' => $vehicleData['vehicle_id']
                ?? $search->vehicle_id,
            'vehicle_category_id' => $vehicleData['vehicle_category_id']
                ?? $search->vehicle_category_id,
            'vehicle_name' => $vehicleData['vehicle_name']
                ?? $search->vehicle_name,
            'vehicle_category_name' =>
                $vehicleData['vehicle_category_name']
                ?? $search->vehicle_category_name,
            'vehicle_type' => $vehicleData['vehicle_type']
                ?? $search->vehicle_type,
            'fuel_type' => $vehicleData['fuel_type']
                ?? $search->fuel_type,
            'transmission_type' => $vehicleData['transmission_type']
                ?? $search->transmission_type,
            'plan_type' => $vehicleData['plan_type']
                ?? $search->plan_type,
            'plan_name' => $vehicleData['plan_name']
                ?? $search->plan_name,
            'estimated_amount' => $this->nullableFloat(
                $vehicleData['estimated_amount']
                    ?? $search->estimated_amount
            ),
            'grand_total' => $this->nullableFloat(
                $vehicleData['grand_total']
                    ?? $search->grand_total
            ),
            'fare_breakdown' => $vehicleData['fare_breakdown']
                ?? $search->fare_breakdown,
            'intent_score' => $this->addScore(
                $search,
                self::SCORE_VEHICLE_SELECTED
            ),
            'last_activity_at' => now(),
        ]);

        $this->updateLinkedActivity($search, [
            'event' => 'vehicle_selected',
            'stage' => 'selected',
            'vehicle_id' => $search->vehicle_id,
            'vehicle_name' => $search->vehicle_name,
            'estimated_amount' => $search->estimated_amount,
            'intent_score' => $search->intent_score,
            'priority' => $search->priority,
        ]);

        return $search->fresh();
    }

    public function markCheckoutStarted(
        CustomerSearchActivity|string|int $search,
        array $data = []
    ): CustomerSearchActivity {
        $search = $this->resolveSearch($search);

        $search->update([
            'stage' => CustomerSearchActivity::STAGE_CHECKOUT_STARTED,
            'checkout_status' => CustomerSearchActivity::CHECKOUT_STARTED,
            'checkout_started_at' => $search->checkout_started_at ?? now(),
            'estimated_amount' => $this->nullableFloat(
                $data['estimated_amount']
                    ?? $search->estimated_amount
            ),
            'grand_total' => $this->nullableFloat(
                $data['grand_total']
                    ?? $search->grand_total
            ),
            'coupon_code' => $data['coupon_code']
                ?? $search->coupon_code,
            'coupon_id' => $data['coupon_id']
                ?? $search->coupon_id,
            'coupon_discount' => $this->nullableFloat(
                $data['coupon_discount']
                    ?? $search->coupon_discount
            ),
            'fare_breakdown' => $data['fare_breakdown']
                ?? $search->fare_breakdown,
            'intent_score' => $this->addScore(
                $search,
                self::SCORE_CHECKOUT_STARTED
            ),
            'last_activity_at' => now(),
        ]);

        $this->updateLinkedActivity($search, [
            'event' => 'checkout_started',
            'stage' => 'checkout',
            'estimated_amount' => $search->estimated_amount,
            'intent_score' => $search->intent_score,
            'priority' => $search->priority,
        ]);

        return $search->fresh();
    }

    public function markPaymentStarted(
        CustomerSearchActivity|string|int $search,
        array $data = []
    ): CustomerSearchActivity {
        $search = $this->resolveSearch($search);

        $search->update([
            'stage' => CustomerSearchActivity::STAGE_PAYMENT_STARTED,
            'payment_status' => CustomerSearchActivity::PAYMENT_STARTED,
            'payment_started_at' => $search->payment_started_at ?? now(),
            'grand_total' => $this->nullableFloat(
                $data['grand_total']
                    ?? $search->grand_total
            ),
            'metadata' => $this->mergeArrayData(
                $search->metadata,
                $data['metadata'] ?? null
            ),
            'intent_score' => $this->addScore(
                $search,
                self::SCORE_PAYMENT_STARTED
            ),
            'last_activity_at' => now(),
        ]);

        $this->updateLinkedActivity($search, [
            'event' => 'payment_started',
            'stage' => 'payment',
            'estimated_amount' => $search->grand_total
                ?? $search->estimated_amount,
            'intent_score' => $search->intent_score,
            'priority' => $search->priority,
        ]);

        return $search->fresh();
    }

    public function markPaymentFailed(
        CustomerSearchActivity|string|int $search,
        array $failureData = []
    ): CustomerSearchActivity {
        $search = $this->resolveSearch($search);

        $metadata = $this->mergeArrayData(
            $search->metadata,
            [
                'payment_failure' => [
                    'reason' => $failureData['reason'] ?? null,
                    'error_code' => $failureData['error_code'] ?? null,
                    'payment_id' => $failureData['payment_id'] ?? null,
                    'failed_at' => now()->toIso8601String(),
                ],
            ]
        );

        $search->update([
            'payment_status' => CustomerSearchActivity::PAYMENT_FAILED,
            'metadata' => $metadata,
            'last_activity_at' => now(),
        ]);

        $this->updateLinkedActivity($search, [
            'event' => 'payment_failed',
            'stage' => 'payment',
            'estimated_amount' => $search->grand_total
                ?? $search->estimated_amount,
            'intent_score' => $search->intent_score,
            'priority' => $search->priority,
        ]);

        return $search->fresh();
    }

    public function markPaymentSuccess(
        CustomerSearchActivity|string|int $search,
        array $paymentData = []
    ): CustomerSearchActivity {
        $search = $this->resolveSearch($search);

        $metadata = $this->mergeArrayData(
            $search->metadata,
            [
                'payment_success' => [
                    'payment_id' => $paymentData['payment_id'] ?? null,
                    'payment_method' =>
                        $paymentData['payment_method'] ?? null,
                    'transaction_id' =>
                        $paymentData['transaction_id'] ?? null,
                    'paid_at' => now()->toIso8601String(),
                ],
            ]
        );

        $search->update([
            'payment_status' => CustomerSearchActivity::PAYMENT_SUCCESS,
            'metadata' => $metadata,
            'last_activity_at' => now(),
        ]);

        $this->updateLinkedActivity($search, [
            'event' => 'payment_success',
            'stage' => 'payment',
            'estimated_amount' => $search->grand_total
                ?? $search->estimated_amount,
            'intent_score' => $search->intent_score,
            'priority' => $search->priority,
        ]);

        return $search->fresh();
    }

    public function markConverted(
        CustomerSearchActivity|string|int $search,
        string|array|null $bookingType = null,
        ?int $bookingId = null,
        ?string $bookingNumber = null,
        ?float $grandTotal = null,
        array $bookingData = [],
        array $paymentData = []
    ): CustomerSearchActivity {
        $search = $this->resolveSearch($search);

        // Support the new listener call style:
        // markConverted(search: $search, bookingData: [...], paymentData: [...])
        if (is_array($bookingType)) {
            $bookingData = array_replace($bookingType, $bookingData);
            $bookingType = null;
        }

        $resolvedBookingType = (string) (
            $bookingData['booking_type']
            ?? $bookingData['service_type']
            ?? $bookingData['trip_type']
            ?? $bookingType
            ?? $search->service_type
            ?? $search->module
            ?? 'booking'
        );

        $resolvedBookingId = $this->nullableInteger(
            $bookingData['booking_id']
            ?? $bookingData['id']
            ?? $bookingData['order_id']
            ?? $bookingId
        );

        $resolvedBookingNumber = $bookingData['booking_no']
            ?? $bookingData['booking_number']
            ?? $bookingNumber;

        $resolvedGrandTotal = $this->nullableFloat(
            $bookingData['grand_total']
            ?? $bookingData['total_amount']
            ?? $paymentData['grand_total']
            ?? $paymentData['amount']
            ?? $grandTotal
            ?? $search->grand_total
        );

        $metadata = $this->mergeArrayData(
            $search->metadata,
            [
                'conversion' => [
                    'booking' => $bookingData,
                    'payment' => $paymentData,
                    'converted_at' => now()->toIso8601String(),
                ],
            ]
        );

        $search->update([
            'stage' => CustomerSearchActivity::STAGE_CONVERTED,
            'search_status' =>
                CustomerSearchActivity::SEARCH_STATUS_CONVERTED,
            'checkout_status' =>
                CustomerSearchActivity::CHECKOUT_COMPLETED,
            'payment_status' =>
                CustomerSearchActivity::PAYMENT_SUCCESS,
            'is_converted' => true,
            'is_abandoned' => false,
            'booking_type' => $resolvedBookingType,
            'booking_id' => $resolvedBookingId,
            'booking_number' => $resolvedBookingNumber,
            'grand_total' => $resolvedGrandTotal,
            'metadata' => $metadata,
            'converted_at' => now(),
            'abandoned_at' => null,
            'lead_status' => CustomerSearchActivity::LEAD_CONVERTED,
            'priority' => CustomerSearchActivity::PRIORITY_LOW,
            'last_activity_at' => now(),
        ]);

        $this->updateLinkedActivity($search, [
            'event' => 'booking_created',
            'stage' => 'booked',
            'related_type' => $resolvedBookingType,
            'related_id' => $resolvedBookingId,
            'estimated_amount' => $resolvedGrandTotal
                ?? $search->estimated_amount,
            'lead_status' => CustomerSearchActivity::LEAD_CONVERTED,
            'priority' => CustomerSearchActivity::PRIORITY_LOW,
        ]);

        return $search->fresh();
    }

    public function markAbandoned(
        CustomerSearchActivity|string|int $search,
        ?Carbon $abandonedAt = null
    ): CustomerSearchActivity {
        $search = $this->resolveSearch($search);

        if ($search->is_converted) {
            return $search;
        }

        $checkoutStatus = $search->checkout_status;

        if ($checkoutStatus === CustomerSearchActivity::CHECKOUT_STARTED) {
            $checkoutStatus =
                CustomerSearchActivity::CHECKOUT_ABANDONED;
        }

        $search->update([
            'stage' => CustomerSearchActivity::STAGE_ABANDONED,
            'search_status' =>
                CustomerSearchActivity::SEARCH_STATUS_ABANDONED,
            'checkout_status' => $checkoutStatus,
            'is_abandoned' => true,
            'abandoned_at' => $abandonedAt ?? now(),
            'follow_up_at' => $search->follow_up_at
                ?? now()->addMinutes(15),
            'last_activity_at' => now(),
        ]);

        $this->updateLinkedActivity($search, [
            'event' => 'search_abandoned',
            'stage' => 'abandoned',
            'intent_score' => $search->intent_score,
            'priority' => $search->priority,
        ]);

        return $search->fresh();
    }

    public function restoreSearch(
        CustomerSearchActivity|string|int $search
    ): CustomerSearchActivity {
        $search = $this->resolveSearch($search);

        $search->update([
            'stage' => CustomerSearchActivity::STAGE_SEARCHED,
            'search_status' =>
                CustomerSearchActivity::SEARCH_STATUS_ACTIVE,
            'checkout_status' =>
                CustomerSearchActivity::CHECKOUT_NOT_STARTED,
            'payment_status' =>
                CustomerSearchActivity::PAYMENT_NOT_STARTED,
            'is_abandoned' => false,
            'abandoned_at' => null,
            'expires_at' => now()->addHours(24),
            'last_activity_at' => now(),
        ]);

        return $search->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Automatic Abandoned Search Processing
    |--------------------------------------------------------------------------
    */

    public function markInactiveSearchesAsAbandoned(
        int $inactiveMinutes = 30,
        int $limit = 500
    ): int {
        $inactiveBefore = now()->subMinutes(max(1, $inactiveMinutes));
        $updated = 0;

        CustomerSearchActivity::query()
            ->active()
            ->where('last_activity_at', '<=', $inactiveBefore)
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get()
            ->each(function (CustomerSearchActivity $search) use (
                &$updated
            ): void {
                $this->markAbandoned($search);
                $updated++;
            });

        return $updated;
    }

    public function expireOldSearches(
        int $olderThanHours = 24,
        int $limit = 500
    ): int {
        $expiresBefore = now()->subHours(max(1, $olderThanHours));
        $updated = 0;

        CustomerSearchActivity::query()
            ->notConverted()
            ->whereIn('search_status', [
                CustomerSearchActivity::SEARCH_STATUS_ACTIVE,
                CustomerSearchActivity::SEARCH_STATUS_ABANDONED,
            ])
            ->where('last_activity_at', '<=', $expiresBefore)
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get()
            ->each(function (CustomerSearchActivity $search) use (
                &$updated
            ): void {
                $search->update([
                    'search_status' =>
                        CustomerSearchActivity::SEARCH_STATUS_EXPIRED,
                    'expires_at' => now(),
                    'last_activity_at' => now(),
                ]);

                $updated++;
            });

        return $updated;
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Search Queries
    |--------------------------------------------------------------------------
    */

    public function latestCustomerSearch(
        ?int $userId = null,
        ?string $mobile = null,
        ?string $sessionId = null
    ): ?CustomerSearchActivity {
        return CustomerSearchActivity::query()
            ->when(
                $userId !== null,
                fn ($query) => $query->where('user_id', $userId)
            )
            ->when(
                $userId === null && filled($mobile),
                fn ($query) => $query->where(
                    'mobile',
                    CustomerSearchActivity::normalizeMobile($mobile)
                )
            )
            ->when(
                $userId === null
                    && blank($mobile)
                    && filled($sessionId),
                fn ($query) => $query->where(
                    'session_id',
                    $sessionId
                )
            )
            ->latestFirst()
            ->first();
    }

    public function customerSearchHistory(
        ?int $userId = null,
        ?string $mobile = null,
        ?string $sessionId = null,
        int $limit = 50
    ): Collection {
        return CustomerSearchActivity::query()
            ->with(['user', 'assignedUser'])
            ->when(
                $userId !== null,
                fn ($query) => $query->where('user_id', $userId)
            )
            ->when(
                $userId === null && filled($mobile),
                fn ($query) => $query->where(
                    'mobile',
                    CustomerSearchActivity::normalizeMobile($mobile)
                )
            )
            ->when(
                $userId === null
                    && blank($mobile)
                    && filled($sessionId),
                fn ($query) => $query->where(
                    'session_id',
                    $sessionId
                )
            )
            ->latestFirst()
            ->limit(max(1, min($limit, 500)))
            ->get();
    }

    public function highPriorityLeads(int $limit = 100): Collection
    {
        return CustomerSearchActivity::query()
            ->with(['user', 'assignedUser'])
            ->highPriority()
            ->openLeads()
            ->notConverted()
            ->latestFirst()
            ->limit(max(1, min($limit, 500)))
            ->get();
    }

    public function abandonedSearchesForFollowUp(
        int $limit = 100
    ): Collection {
        return CustomerSearchActivity::query()
            ->with(['user', 'assignedUser'])
            ->abandoned()
            ->notConverted()
            ->where(function ($query): void {
                $query
                    ->whereNull('follow_up_at')
                    ->orWhere('follow_up_at', '<=', now());
            })
            ->latestFirst()
            ->limit(max(1, min($limit, 500)))
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Payload Builder
    |--------------------------------------------------------------------------
    */

    private function buildSearchPayload(
        string $module,
        string $serviceType,
        array $data,
        ?User $user,
        Request $request
    ): array {
        $mobile = $data['mobile']
            ?? $user?->mobile
            ?? null;

        $viaLocations = $this->normalizeArray(
            $data['via_locations'] ?? null
        );

        $intentScore = $this->normalizeScore(
            $data['intent_score'] ?? self::SCORE_SEARCHED
        );

        return [
            'uuid' => $data['uuid'] ?? (string) Str::uuid(),

            'user_id' => $user?->id,
            'mobile' =>
                CustomerSearchActivity::normalizeMobile($mobile),
            'customer_name' => $data['customer_name']
                ?? $user?->name,
            'customer_email' => $data['customer_email']
                ?? $user?->email,

            'session_id' => $this->resolveSessionId($data, $request),
            'device_id' => $data['device_id']
                ?? $request->input('device_id'),

            'source' => $data['source']
                ?? $request->input('source')
                ?? CustomerSearchActivity::SOURCE_FLUTTER_APP,
            'platform' => $data['platform']
                ?? $request->input('platform'),
            'device_name' => $data['device_name']
                ?? $request->input('device_name'),
            'operating_system' => $data['operating_system']
                ?? $request->input('operating_system'),
            'app_version' => $data['app_version']
                ?? $request->input('app_version'),
            'ip_address' => $data['ip_address']
                ?? $request->ip(),
            'user_agent' => $data['user_agent']
                ?? $request->userAgent(),

            'module' => $module,
            'service_type' => $serviceType,
            'stage' => $data['stage']
                ?? CustomerSearchActivity::STAGE_SEARCHED,

            'pickup_location' => $data['pickup_location'] ?? null,
            'pickup_city' => $data['pickup_city'] ?? null,
            'pickup_state' => $data['pickup_state'] ?? null,
            'pickup_country' => $data['pickup_country'] ?? null,
            'pickup_pincode' => $data['pickup_pincode'] ?? null,
            'pickup_latitude' => $this->nullableFloat(
                $data['pickup_latitude'] ?? null
            ),
            'pickup_longitude' => $this->nullableFloat(
                $data['pickup_longitude'] ?? null
            ),
            'pickup_place_id' => $data['pickup_place_id'] ?? null,

            'drop_location' => $data['drop_location'] ?? null,
            'drop_city' => $data['drop_city'] ?? null,
            'drop_state' => $data['drop_state'] ?? null,
            'drop_country' => $data['drop_country'] ?? null,
            'drop_pincode' => $data['drop_pincode'] ?? null,
            'drop_latitude' => $this->nullableFloat(
                $data['drop_latitude'] ?? null
            ),
            'drop_longitude' => $this->nullableFloat(
                $data['drop_longitude'] ?? null
            ),
            'drop_place_id' => $data['drop_place_id'] ?? null,

            'airport_name' => $data['airport_name'] ?? null,
            'airport_code' => $data['airport_code'] ?? null,
            'airport_trip_type' =>
                $data['airport_trip_type'] ?? null,

            'via_locations' => $viaLocations,
            'total_stops' => $data['total_stops']
                ?? count($viaLocations ?? []),

            'start_datetime' => $this->nullableDateTime(
                $data['start_datetime'] ?? null
            ),
            'end_datetime' => $this->nullableDateTime(
                $data['end_datetime'] ?? null
            ),
            'return_datetime' => $this->nullableDateTime(
                $data['return_datetime'] ?? null
            ),

            'trip_days' => $this->nullableInteger(
                $data['trip_days'] ?? null
            ),
            'rental_hours' => $this->nullableInteger(
                $data['rental_hours'] ?? null
            ),
            'rental_days' => $this->nullableInteger(
                $data['rental_days'] ?? null
            ),
            'rental_weeks' => $this->nullableInteger(
                $data['rental_weeks'] ?? null
            ),
            'rental_months' => $this->nullableInteger(
                $data['rental_months'] ?? null
            ),

            'package_name' => $data['package_name'] ?? null,
            'package_hours' => $this->nullableInteger(
                $data['package_hours'] ?? null
            ),
            'package_km' => $this->nullableFloat(
                $data['package_km'] ?? null
            ),

            'vehicle_category_id' => $this->nullableInteger(
                $data['vehicle_category_id'] ?? null
            ),
            'vehicle_id' => $this->nullableInteger(
                $data['vehicle_id'] ?? null
            ),
            'vehicle_category_name' =>
                $data['vehicle_category_name'] ?? null,
            'vehicle_name' => $data['vehicle_name'] ?? null,
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'fuel_type' => $data['fuel_type'] ?? null,
            'transmission_type' =>
                $data['transmission_type'] ?? null,

            'plan_type' => $data['plan_type'] ?? null,
            'plan_name' => $data['plan_name'] ?? null,
            'minimum_hours' => $this->nullableInteger(
                $data['minimum_hours'] ?? null
            ),
            'included_km' => $this->nullableInteger(
                $data['included_km'] ?? null
            ),

            'price_per_hour' => $this->nullableFloat(
                $data['price_per_hour'] ?? null
            ),
            'price_per_day' => $this->nullableFloat(
                $data['price_per_day'] ?? null
            ),
            'price_per_week' => $this->nullableFloat(
                $data['price_per_week'] ?? null
            ),
            'price_per_month' => $this->nullableFloat(
                $data['price_per_month'] ?? null
            ),
            'weekly_discount_percent' => $this->nullableFloat(
                $data['weekly_discount_percent'] ?? null
            ),
            'monthly_discount_percent' => $this->nullableFloat(
                $data['monthly_discount_percent'] ?? null
            ),

            'helmet_option' => $data['helmet_option'] ?? null,
            'helmet_quantity' => $this->nullableInteger(
                $data['helmet_quantity'] ?? 0
            ) ?? 0,
            'helmet_charge' => $this->nullableFloat(
                $data['helmet_charge'] ?? 0
            ) ?? 0,
            'security_deposit' => $this->nullableFloat(
                $data['security_deposit'] ?? 0
            ) ?? 0,

            'estimated_distance_km' => $this->nullableFloat(
                $data['estimated_distance_km'] ?? null
            ),
            'estimated_duration_minutes' => $this->nullableInteger(
                $data['estimated_duration_minutes'] ?? null
            ),
            'minimum_km' => $this->nullableFloat(
                $data['minimum_km'] ?? null
            ),
            'billable_km' => $this->nullableFloat(
                $data['billable_km'] ?? null
            ),

            'currency' => strtoupper(
                (string) ($data['currency'] ?? 'INR')
            ),
            'base_fare' => $this->nullableFloat(
                $data['base_fare'] ?? null
            ),
            'estimated_amount' => $this->nullableFloat(
                $data['estimated_amount'] ?? null
            ),
            'discount_amount' => $this->nullableFloat(
                $data['discount_amount'] ?? 0
            ) ?? 0,
            'coupon_discount' => $this->nullableFloat(
                $data['coupon_discount'] ?? 0
            ) ?? 0,
            'driver_allowance' => $this->nullableFloat(
                $data['driver_allowance'] ?? 0
            ) ?? 0,
            'toll_amount' => $this->nullableFloat(
                $data['toll_amount'] ?? 0
            ) ?? 0,
            'parking_amount' => $this->nullableFloat(
                $data['parking_amount'] ?? 0
            ) ?? 0,
            'state_tax_amount' => $this->nullableFloat(
                $data['state_tax_amount'] ?? 0
            ) ?? 0,
            'waiting_charge' => $this->nullableFloat(
                $data['waiting_charge'] ?? 0
            ) ?? 0,
            'tax_amount' => $this->nullableFloat(
                $data['tax_amount'] ?? 0
            ) ?? 0,
            'grand_total' => $this->nullableFloat(
                $data['grand_total'] ?? null
            ),
            'is_all_inclusive' => (bool) (
                $data['is_all_inclusive'] ?? false
            ),

            'coupon_code' => $data['coupon_code'] ?? null,
            'coupon_id' => $this->nullableInteger(
                $data['coupon_id'] ?? null
            ),

            'result_count' => $this->nullableInteger(
                $data['result_count'] ?? null
            ),
            'has_available_vehicle' =>
                array_key_exists('has_available_vehicle', $data)
                    ? (bool) $data['has_available_vehicle']
                    : null,
            'minimum_result_price' => $this->nullableFloat(
                $data['minimum_result_price'] ?? null
            ),
            'maximum_result_price' => $this->nullableFloat(
                $data['maximum_result_price'] ?? null
            ),

            'search_status' =>
                CustomerSearchActivity::SEARCH_STATUS_ACTIVE,
            'checkout_status' =>
                CustomerSearchActivity::CHECKOUT_NOT_STARTED,
            'payment_status' =>
                CustomerSearchActivity::PAYMENT_NOT_STARTED,
            'is_converted' => false,
            'is_abandoned' => false,

            'intent_score' => $intentScore,
            'priority' =>
                CustomerSearchActivity::resolvePriority(
                    $intentScore,
                    CustomerSearchActivity::STAGE_SEARCHED,
                    false
                ),
            'lead_status' =>
                CustomerSearchActivity::LEAD_NEW,

            'fare_breakdown' => $this->normalizeArray(
                $data['fare_breakdown'] ?? null
            ),
            'filters' => $this->normalizeArray(
                $data['filters'] ?? null
            ),
            'search_data' => $this->normalizeArray(
                $data['search_data'] ?? $data
            ),
            'metadata' => $this->normalizeArray(
                $data['metadata'] ?? null
            ),
            'utm_data' => $this->normalizeArray(
                $data['utm_data'] ?? null
            ),

            'searched_at' => $this->nullableDateTime(
                $data['searched_at'] ?? now()
            ),
            'last_activity_at' => now(),
            'expires_at' => $this->nullableDateTime(
                $data['expires_at'] ?? now()->addHours(24)
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Activity Integration
    |--------------------------------------------------------------------------
    */

    private function createCustomerActivity(
        string $module,
        string $serviceType,
        array $payload
    ): ?CustomerActivity {
        try {
            return CustomerActivity::create([
                'user_id' => $payload['user_id'] ?? null,
                'mobile' => $payload['mobile'] ?? null,
                'customer_name' =>
                    $payload['customer_name'] ?? null,
                'session_id' => $payload['session_id'] ?? null,
                'device_id' => $payload['device_id'] ?? null,
                'platform' => $payload['platform'] ?? null,
                'device_name' => $payload['device_name'] ?? null,
                'operating_system' =>
                    $payload['operating_system'] ?? null,
                'app_version' => $payload['app_version'] ?? null,
                'source' => $payload['source'] ?? 'flutter_app',

                'event' => $serviceType . '_search',
                'module' => $module,
                'service_type' => $serviceType,
                'stage' => 'searched',

                'pickup_location' =>
                    $payload['pickup_location'] ?? null,
                'pickup_city' => $payload['pickup_city'] ?? null,
                'pickup_latitude' =>
                    $payload['pickup_latitude'] ?? null,
                'pickup_longitude' =>
                    $payload['pickup_longitude'] ?? null,

                'drop_location' =>
                    $payload['drop_location'] ?? null,
                'drop_city' => $payload['drop_city'] ?? null,
                'drop_latitude' =>
                    $payload['drop_latitude'] ?? null,
                'drop_longitude' =>
                    $payload['drop_longitude'] ?? null,

                'vehicle_id' => $payload['vehicle_id'] ?? null,
                'vehicle_name' =>
                    $payload['vehicle_name'] ?? null,
                'plan' => $payload['plan_name']
                    ?? $payload['plan_type']
                    ?? null,
                'estimated_amount' =>
                    $payload['estimated_amount'] ?? null,

                'intent_score' =>
                    $payload['intent_score'] ?? 0,
                'priority' => $payload['priority'] ?? 'low',
                'lead_status' =>
                    $payload['lead_status'] ?? 'new',

                'data' => [
                    'route' => [
                        'pickup_city' =>
                            $payload['pickup_city'] ?? null,
                        'drop_city' =>
                            $payload['drop_city'] ?? null,
                        'via_locations' =>
                            $payload['via_locations'] ?? null,
                    ],
                    'dates' => [
                        'start_datetime' =>
                            $payload['start_datetime'] ?? null,
                        'end_datetime' =>
                            $payload['end_datetime'] ?? null,
                        'return_datetime' =>
                            $payload['return_datetime'] ?? null,
                    ],
                    'fare_breakdown' =>
                        $payload['fare_breakdown'] ?? null,
                    'filters' => $payload['filters'] ?? null,
                ],

                'utm_data' => $payload['utm_data'] ?? null,
                'occurred_at' =>
                    $payload['searched_at'] ?? now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning(
                'Customer activity link could not be created.',
                [
                    'module' => $module,
                    'service_type' => $serviceType,
                    'message' => $exception->getMessage(),
                ]
            );

            return null;
        }
    }

    private function updateLinkedActivity(
        CustomerSearchActivity $search,
        array $attributes
    ): void {
        if ($search->customer_activity_id === null) {
            return;
        }

        try {
            CustomerActivity::query()
                ->whereKey($search->customer_activity_id)
                ->update(array_merge(
                    $attributes,
                    [
                        'occurred_at' => now(),
                        'updated_at' => now(),
                    ]
                ));
        } catch (Throwable $exception) {
            Log::warning(
                'Linked customer activity could not be updated.',
                [
                    'search_activity_id' => $search->id,
                    'customer_activity_id' =>
                        $search->customer_activity_id,
                    'message' => $exception->getMessage(),
                ]
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Resolvers
    |--------------------------------------------------------------------------
    */

    private function resolveSearch(
        CustomerSearchActivity|string|int $search
    ): CustomerSearchActivity {
        if ($search instanceof CustomerSearchActivity) {
            return $search;
        }

        $query = CustomerSearchActivity::query();

        if (is_numeric($search)) {
            return $query->findOrFail((int) $search);
        }

        return $query
            ->where('uuid', (string) $search)
            ->firstOrFail();
    }

    private function resolveUser(array $data): ?User
    {
        if (
            isset($data['user'])
            && $data['user'] instanceof User
        ) {
            return $data['user'];
        }

        if (!empty($data['user_id'])) {
            return User::query()->find((int) $data['user_id']);
        }

        $authenticatedUser = Auth::user();

        if ($authenticatedUser instanceof User) {
            return $authenticatedUser;
        }

        $mobile = CustomerSearchActivity::normalizeMobile(
            $data['mobile'] ?? null
        );

        if ($mobile !== null) {
            return User::query()
                ->where('mobile', $mobile)
                ->first();
        }

        return null;
    }

    private function resolveSessionId(
        array $data,
        Request $request
    ): string {
        $sessionId = $data['session_id']
            ?? $request->input('session_id')
            ?? $request->header('X-Session-ID');

        if (filled($sessionId)) {
            return (string) $sessionId;
        }

        return (string) Str::uuid();
    }

    /*
    |--------------------------------------------------------------------------
    | Utility Methods
    |--------------------------------------------------------------------------
    */

    private function addScore(
        CustomerSearchActivity $search,
        int $points
    ): int {
        return $this->normalizeScore(
            (int) $search->intent_score + max(0, $points)
        );
    }

    private function normalizeScore(mixed $score): int
    {
        return max(0, min(100, (int) $score));
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value)
            ? (float) $value
            : null;
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value)
            ? (int) $value
            : null;
    }

    private function nullableDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeArray(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded)
                ? $decoded
                : null;
        }

        return null;
    }

    private function mergeArrayData(
        mixed $currentData,
        ?array $newData
    ): ?array {
        if (empty($newData)) {
            return is_array($currentData)
                ? $currentData
                : null;
        }

        return array_replace_recursive(
            is_array($currentData) ? $currentData : [],
            $newData
        );
    }
}