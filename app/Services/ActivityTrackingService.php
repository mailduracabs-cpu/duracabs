<?php

namespace App\Services;

use App\Models\CustomerActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ActivityTrackingService
{
    /*
    |--------------------------------------------------------------------------
    | Activity Event Constants
    |--------------------------------------------------------------------------
    */

    public const EVENT_APP_OPENED = 'app_opened';

    public const EVENT_OTP_REQUESTED = 'otp_requested';

    public const EVENT_OTP_VERIFIED = 'otp_verified';

    public const EVENT_USER_REGISTERED = 'user_registered';

    public const EVENT_USER_LOGIN = 'user_login';

    public const EVENT_USER_LOGOUT = 'user_logout';

    public const EVENT_ONE_WAY_SEARCH = 'one_way_search';

    public const EVENT_ROUND_TRIP_SEARCH = 'round_trip_search';

    public const EVENT_LOCAL_SEARCH = 'local_search';

    public const EVENT_AIRPORT_SEARCH = 'airport_search';

    public const EVENT_TOUR_SEARCH = 'tour_search';

    public const EVENT_SELF_DRIVE_SEARCH = 'self_drive_search';

    public const EVENT_BIKE_RENTAL_SEARCH = 'bike_rental_search';

    public const EVENT_VEHICLE_VIEWED = 'vehicle_viewed';

    public const EVENT_VEHICLE_SELECTED = 'vehicle_selected';

    public const EVENT_CHECKOUT_STARTED = 'checkout_started';

    public const EVENT_PAYMENT_STARTED = 'payment_started';

    public const EVENT_PAYMENT_FAILED = 'payment_failed';

    public const EVENT_PAYMENT_SUCCESS = 'payment_success';

    public const EVENT_BOOKING_CREATED = 'booking_created';

    public const EVENT_BOOKING_CONFIRMED = 'booking_confirmed';

    public const EVENT_BOOKING_CANCELLED = 'booking_cancelled';

    public const EVENT_TRIP_STARTED = 'trip_started';

    public const EVENT_TRIP_COMPLETED = 'trip_completed';

    /*
    |--------------------------------------------------------------------------
    | Activity Module Constants
    |--------------------------------------------------------------------------
    */

    public const MODULE_APP = 'app';

    public const MODULE_AUTH = 'auth';

    public const MODULE_TAXI = 'taxi';

    public const MODULE_SELF_DRIVE = 'self_drive';

    public const MODULE_BIKE_RENTAL = 'bike_rental';

    public const MODULE_BOOKING = 'booking';

    public const MODULE_PAYMENT = 'payment';

    public const MODULE_PROFILE = 'profile';

    /*
    |--------------------------------------------------------------------------
    | Activity Stage Constants
    |--------------------------------------------------------------------------
    */

    public const STAGE_OPENED = 'opened';

    public const STAGE_REQUESTED = 'requested';

    public const STAGE_VERIFIED = 'verified';

    public const STAGE_REGISTERED = 'registered';

    public const STAGE_LOGIN = 'login';

    public const STAGE_LOGOUT = 'logout';

    public const STAGE_SEARCHED = 'searched';

    public const STAGE_VIEWED = 'viewed';

    public const STAGE_SELECTED = 'selected';

    public const STAGE_CHECKOUT = 'checkout';

    public const STAGE_PAYMENT = 'payment';

    public const STAGE_BOOKED = 'booked';

    public const STAGE_CONFIRMED = 'confirmed';

    public const STAGE_CANCELLED = 'cancelled';

    public const STAGE_STARTED = 'started';

    public const STAGE_COMPLETED = 'completed';

    /*
    |--------------------------------------------------------------------------
    | Lead Status Constants
    |--------------------------------------------------------------------------
    */

    public const LEAD_NEW = 'new';

    public const LEAD_ACTIVE = 'active';

    public const LEAD_CONTACTED = 'contacted';

    public const LEAD_CONVERTED = 'converted';

    public const LEAD_LOST = 'lost';

    public const LEAD_IGNORED = 'ignored';

    /*
    |--------------------------------------------------------------------------
    | Activity Source Constants
    |--------------------------------------------------------------------------
    */

    public const SOURCE_FLUTTER = 'flutter_app';

    public const SOURCE_WEBSITE = 'website';

    public const SOURCE_ADMIN = 'admin_panel';

    public const SOURCE_API = 'api';

    /**
     * Track any customer activity.
     *
     * This is the central method used by all helper methods in this service.
     */
    public function track(
        string $event,
        array $attributes = [],
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        try {
            $resolvedUser = $this->resolveUser($user, $request);

            $payload = $this->buildPayload(
                event: $event,
                attributes: $attributes,
                request: $request,
                user: $resolvedUser
            );

            return CustomerActivity::query()->create($payload);
        } catch (Throwable $exception) {
            Log::error('Customer activity tracking failed.', [
                'event' => $event,
                'attributes' => $this->safeLogAttributes($attributes),
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
            ]);

            return null;
        }
    }

    /**
     * Track app opening.
     */
    public function trackAppOpened(
        array $attributes = [],
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_APP_OPENED,
            attributes: array_merge([
                'module' => self::MODULE_APP,
                'stage' => self::STAGE_OPENED,
                'intent_score' => 5,
            ], $attributes),
            request: $request,
            user: $user
        );
    }

    /**
     * Track OTP request.
     */
    public function trackOtpRequested(
        string $mobile,
        array $attributes = [],
        ?Request $request = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_OTP_REQUESTED,
            attributes: array_merge([
                'mobile' => $mobile,
                'module' => self::MODULE_AUTH,
                'stage' => self::STAGE_REQUESTED,
                'intent_score' => 5,
            ], $attributes),
            request: $request
        );
    }

    /**
     * Track successful OTP verification.
     */
    public function trackOtpVerified(
        string $mobile,
        array $attributes = [],
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_OTP_VERIFIED,
            attributes: array_merge([
                'mobile' => $mobile,
                'module' => self::MODULE_AUTH,
                'stage' => self::STAGE_VERIFIED,
                'intent_score' => 10,
            ], $attributes),
            request: $request,
            user: $user
        );
    }

    /**
     * Track new customer registration.
     */
    public function trackUserRegistered(
        User $user,
        array $attributes = [],
        ?Request $request = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_USER_REGISTERED,
            attributes: array_merge([
                'module' => self::MODULE_AUTH,
                'stage' => self::STAGE_REGISTERED,
                'intent_score' => 15,
            ], $attributes),
            request: $request,
            user: $user
        );
    }

    /**
     * Track customer login.
     */
    public function trackLogin(
        User $user,
        array $attributes = [],
        ?Request $request = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_USER_LOGIN,
            attributes: array_merge([
                'module' => self::MODULE_AUTH,
                'stage' => self::STAGE_LOGIN,
                'intent_score' => 10,
            ], $attributes),
            request: $request,
            user: $user
        );
    }

    /**
     * Track customer logout.
     */
    public function trackLogout(
        User $user,
        array $attributes = [],
        ?Request $request = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_USER_LOGOUT,
            attributes: array_merge([
                'module' => self::MODULE_AUTH,
                'stage' => self::STAGE_LOGOUT,
                'intent_score' => 0,
            ], $attributes),
            request: $request,
            user: $user
        );
    }

    /**
     * Track One Way taxi search.
     */
    public function trackOneWaySearch(
        array $searchData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->trackSearch(
            event: self::EVENT_ONE_WAY_SEARCH,
            serviceType: 'one_way',
            module: self::MODULE_TAXI,
            searchData: $searchData,
            request: $request,
            user: $user
        );
    }

    /**
     * Track Round Trip taxi search.
     */
    public function trackRoundTripSearch(
        array $searchData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->trackSearch(
            event: self::EVENT_ROUND_TRIP_SEARCH,
            serviceType: 'round_trip',
            module: self::MODULE_TAXI,
            searchData: $searchData,
            request: $request,
            user: $user
        );
    }

    /**
     * Track Local taxi search.
     */
    public function trackLocalSearch(
        array $searchData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->trackSearch(
            event: self::EVENT_LOCAL_SEARCH,
            serviceType: 'local',
            module: self::MODULE_TAXI,
            searchData: $searchData,
            request: $request,
            user: $user
        );
    }

    /**
     * Track Airport taxi search.
     */
    public function trackAirportSearch(
        array $searchData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->trackSearch(
            event: self::EVENT_AIRPORT_SEARCH,
            serviceType: 'airport',
            module: self::MODULE_TAXI,
            searchData: $searchData,
            request: $request,
            user: $user
        );
    }

    /**
     * Track Tour search.
     */
    public function trackTourSearch(
        array $searchData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->trackSearch(
            event: self::EVENT_TOUR_SEARCH,
            serviceType: 'tour',
            module: self::MODULE_TAXI,
            searchData: $searchData,
            request: $request,
            user: $user
        );
    }

    /**
     * Track Self Drive vehicle search.
     */
    public function trackSelfDriveSearch(
        array $searchData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->trackSearch(
            event: self::EVENT_SELF_DRIVE_SEARCH,
            serviceType: 'self_drive',
            module: self::MODULE_SELF_DRIVE,
            searchData: $searchData,
            request: $request,
            user: $user
        );
    }

    /**
     * Track Bike Rental search.
     */
    public function trackBikeRentalSearch(
        array $searchData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->trackSearch(
            event: self::EVENT_BIKE_RENTAL_SEARCH,
            serviceType: 'bike_rental',
            module: self::MODULE_BIKE_RENTAL,
            searchData: $searchData,
            request: $request,
            user: $user
        );
    }

    /**
     * Track vehicle viewed by customer.
     */
    public function trackVehicleViewed(
        array $vehicleData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_VEHICLE_VIEWED,
            attributes: array_merge([
                'stage' => self::STAGE_VIEWED,
                'intent_score' => 20,
            ], $vehicleData),
            request: $request,
            user: $user
        );
    }

    /**
     * Track vehicle selected by customer.
     */
    public function trackVehicleSelected(
        array $vehicleData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_VEHICLE_SELECTED,
            attributes: array_merge([
                'stage' => self::STAGE_SELECTED,
                'intent_score' => 25,
            ], $vehicleData),
            request: $request,
            user: $user
        );
    }

    /**
     * Track checkout start.
     */
    public function trackCheckoutStarted(
        array $checkoutData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_CHECKOUT_STARTED,
            attributes: array_merge([
                'module' => self::MODULE_BOOKING,
                'stage' => self::STAGE_CHECKOUT,
                'intent_score' => 30,
                'lead_status' => self::LEAD_ACTIVE,
            ], $checkoutData),
            request: $request,
            user: $user
        );
    }

    /**
     * Track payment start.
     */
    public function trackPaymentStarted(
        array $paymentData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_PAYMENT_STARTED,
            attributes: array_merge([
                'module' => self::MODULE_PAYMENT,
                'stage' => self::STAGE_PAYMENT,
                'intent_score' => 40,
                'lead_status' => self::LEAD_ACTIVE,
            ], $paymentData),
            request: $request,
            user: $user
        );
    }

    /**
     * Track failed payment.
     */
    public function trackPaymentFailed(
        array $paymentData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_PAYMENT_FAILED,
            attributes: array_merge([
                'module' => self::MODULE_PAYMENT,
                'stage' => self::STAGE_PAYMENT,
                'intent_score' => 45,
                'priority' => 'high',
                'lead_status' => self::LEAD_ACTIVE,
            ], $paymentData),
            request: $request,
            user: $user
        );
    }

    /**
     * Track successful payment.
     */
    public function trackPaymentSuccess(
        array $paymentData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_PAYMENT_SUCCESS,
            attributes: array_merge([
                'module' => self::MODULE_PAYMENT,
                'stage' => self::STAGE_PAYMENT,
                'intent_score' => 50,
                'priority' => 'urgent',
                'lead_status' => self::LEAD_CONVERTED,
            ], $paymentData),
            request: $request,
            user: $user
        );
    }

    /**
     * Track new booking.
     */
    public function trackBookingCreated(
        array $bookingData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_BOOKING_CREATED,
            attributes: array_merge([
                'module' => self::MODULE_BOOKING,
                'stage' => self::STAGE_BOOKED,
                'intent_score' => 100,
                'priority' => 'urgent',
                'lead_status' => self::LEAD_CONVERTED,
            ], $bookingData),
            request: $request,
            user: $user
        );
    }

    /**
     * Track booking confirmation.
     */
    public function trackBookingConfirmed(
        array $bookingData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_BOOKING_CONFIRMED,
            attributes: array_merge([
                'module' => self::MODULE_BOOKING,
                'stage' => self::STAGE_CONFIRMED,
                'intent_score' => 100,
                'priority' => 'urgent',
                'lead_status' => self::LEAD_CONVERTED,
            ], $bookingData),
            request: $request,
            user: $user
        );
    }

    /**
     * Track cancelled booking.
     */
    public function trackBookingCancelled(
        array $bookingData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_BOOKING_CANCELLED,
            attributes: array_merge([
                'module' => self::MODULE_BOOKING,
                'stage' => self::STAGE_CANCELLED,
                'intent_score' => 0,
                'priority' => 'high',
                'lead_status' => self::LEAD_LOST,
            ], $bookingData),
            request: $request,
            user: $user
        );
    }

    /**
     * Track trip start.
     */
    public function trackTripStarted(
        array $tripData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_TRIP_STARTED,
            attributes: array_merge([
                'module' => self::MODULE_BOOKING,
                'stage' => self::STAGE_STARTED,
                'intent_score' => 0,
                'lead_status' => self::LEAD_CONVERTED,
            ], $tripData),
            request: $request,
            user: $user
        );
    }

    /**
     * Track trip completion.
     */
    public function trackTripCompleted(
        array $tripData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: self::EVENT_TRIP_COMPLETED,
            attributes: array_merge([
                'module' => self::MODULE_BOOKING,
                'stage' => self::STAGE_COMPLETED,
                'intent_score' => 0,
                'lead_status' => self::LEAD_CONVERTED,
            ], $tripData),
            request: $request,
            user: $user
        );
    }

    /**
     * Generic search tracking helper.
     */
    public function trackSearch(
        string $event,
        string $serviceType,
        string $module,
        array $searchData,
        ?Request $request = null,
        User|int|null $user = null
    ): ?CustomerActivity {
        return $this->track(
            event: $event,
            attributes: array_merge([
                'module' => $module,
                'service_type' => $serviceType,
                'stage' => self::STAGE_SEARCHED,
                'intent_score' => 20,
                'lead_status' => self::LEAD_ACTIVE,
            ], $searchData),
            request: $request,
            user: $user
        );
    }

    /**
     * Return the customer's latest activity.
     */
    public function latestActivity(
        User|int|string $customer
    ): ?CustomerActivity {
        $query = CustomerActivity::query()->latest('occurred_at');

        if ($customer instanceof User) {
            $query->where('user_id', $customer->getKey());
        } elseif (is_int($customer) || ctype_digit((string) $customer)) {
            $query->where('user_id', (int) $customer);
        } else {
            $query->where('mobile', $this->normalizeMobile($customer));
        }

        return $query->first();
    }

    /**
     * Return a customer's activity timeline.
     */
    public function customerTimeline(
        User|int|string $customer,
        int $limit = 50
    ) {
        $limit = max(1, min($limit, 200));

        $query = CustomerActivity::query()
            ->with('user')
            ->latest('occurred_at');

        if ($customer instanceof User) {
            $query->where('user_id', $customer->getKey());
        } elseif (is_int($customer) || ctype_digit((string) $customer)) {
            $query->where('user_id', (int) $customer);
        } else {
            $query->where('mobile', $this->normalizeMobile($customer));
        }

        return $query->limit($limit)->get();
    }

    /**
     * Return customer's combined intent score.
     */
    public function customerIntentScore(
        User|int|string $customer
    ): int {
        $query = CustomerActivity::query();

        if ($customer instanceof User) {
            $query->where('user_id', $customer->getKey());
        } elseif (is_int($customer) || ctype_digit((string) $customer)) {
            $query->where('user_id', (int) $customer);
        } else {
            $query->where('mobile', $this->normalizeMobile($customer));
        }

        return (int) $query->sum('intent_score');
    }

    /**
     * Build a complete model payload.
     */
    protected function buildPayload(
        string $event,
        array $attributes,
        ?Request $request,
        ?User $user
    ): array {
        $requestData = $request?->all() ?? [];

        $attributes = array_merge(
            $this->extractRequestFields($requestData),
            $attributes
        );

        $userId = $user?->getKey()
            ?? Arr::get($attributes, 'user_id');

        $mobile = Arr::get($attributes, 'mobile')
            ?? $user?->mobile
            ?? Arr::get($requestData, 'mobile');

        $customerName = Arr::get($attributes, 'customer_name')
            ?? $user?->name
            ?? Arr::get($requestData, 'customer_name')
            ?? Arr::get($requestData, 'name');

        $source = Arr::get(
            $attributes,
            'source',
            self::SOURCE_FLUTTER
        );

        $knownColumns = [
            'uuid',
            'user_id',
            'mobile',
            'customer_name',
            'session_id',
            'device_id',
            'device_token',
            'platform',
            'device_name',
            'operating_system',
            'app_version',
            'event',
            'module',
            'service_type',
            'stage',
            'related_type',
            'related_id',
            'pickup_location',
            'pickup_city',
            'pickup_latitude',
            'pickup_longitude',
            'drop_location',
            'drop_city',
            'drop_latitude',
            'drop_longitude',
            'start_datetime',
            'end_datetime',
            'return_datetime',
            'vehicle_category_id',
            'vehicle_id',
            'vehicle_name',
            'plan_type',
            'passengers',
            'estimated_distance',
            'estimated_amount',
            'intent_score',
            'priority',
            'lead_status',
            'data',
            'utm_data',
            'ip_address',
            'user_agent',
            'source',
            'admin_notified',
            'whatsapp_notified',
            'sms_notified',
            'push_notified',
            'occurred_at',
        ];

        $additionalData = Arr::except($attributes, $knownColumns);

        $existingData = Arr::get($attributes, 'data', []);

        if (! is_array($existingData)) {
            $existingData = [
                'value' => $existingData,
            ];
        }

        $data = array_filter(
            array_merge($existingData, $additionalData),
            static fn (mixed $value): bool => $value !== null
        );

        return [
            'uuid' => Arr::get(
                $attributes,
                'uuid',
                (string) Str::uuid()
            ),

            'user_id' => $userId,
            'mobile' => $this->normalizeMobile($mobile),
            'customer_name' => $customerName,

            'session_id' => Arr::get(
                $attributes,
                'session_id',
                $this->resolveSessionId($request)
            ),

            'device_id' => Arr::get($attributes, 'device_id'),
            'device_token' => Arr::get(
                $attributes,
                'device_token',
                $user?->device_token
            ),

            'platform' => Arr::get($attributes, 'platform'),
            'device_name' => Arr::get($attributes, 'device_name'),
            'operating_system' => Arr::get(
                $attributes,
                'operating_system'
            ),
            'app_version' => Arr::get($attributes, 'app_version'),

            'event' => $event,
            'module' => Arr::get($attributes, 'module'),
            'service_type' => Arr::get(
                $attributes,
                'service_type'
            ),
            'stage' => Arr::get($attributes, 'stage'),

            'related_type' => Arr::get(
                $attributes,
                'related_type'
            ),
            'related_id' => Arr::get($attributes, 'related_id'),

            'pickup_location' => Arr::get(
                $attributes,
                'pickup_location'
            ),
            'pickup_city' => Arr::get($attributes, 'pickup_city'),
            'pickup_latitude' => Arr::get(
                $attributes,
                'pickup_latitude'
            ),
            'pickup_longitude' => Arr::get(
                $attributes,
                'pickup_longitude'
            ),

            'drop_location' => Arr::get(
                $attributes,
                'drop_location'
            ),
            'drop_city' => Arr::get($attributes, 'drop_city'),
            'drop_latitude' => Arr::get(
                $attributes,
                'drop_latitude'
            ),
            'drop_longitude' => Arr::get(
                $attributes,
                'drop_longitude'
            ),

            'start_datetime' => Arr::get(
                $attributes,
                'start_datetime'
            ),
            'end_datetime' => Arr::get(
                $attributes,
                'end_datetime'
            ),
            'return_datetime' => Arr::get(
                $attributes,
                'return_datetime'
            ),

            'vehicle_category_id' => Arr::get(
                $attributes,
                'vehicle_category_id'
            ),
            'vehicle_id' => Arr::get($attributes, 'vehicle_id'),
            'vehicle_name' => Arr::get(
                $attributes,
                'vehicle_name'
            ),
            'plan_type' => Arr::get($attributes, 'plan_type'),
            'passengers' => Arr::get($attributes, 'passengers'),

            'estimated_distance' => Arr::get(
                $attributes,
                'estimated_distance'
            ),
            'estimated_amount' => Arr::get(
                $attributes,
                'estimated_amount'
            ),

            'intent_score' => max(
                0,
                (int) Arr::get($attributes, 'intent_score', 0)
            ),

            'priority' => Arr::get(
                $attributes,
                'priority',
                $this->calculatePriority($attributes)
            ),

            'lead_status' => Arr::get(
                $attributes,
                'lead_status',
                self::LEAD_NEW
            ),

            'data' => $data ?: null,
            'utm_data' => Arr::get($attributes, 'utm_data'),

            'ip_address' => Arr::get(
                $attributes,
                'ip_address',
                $request?->ip()
            ),

            'user_agent' => Arr::get(
                $attributes,
                'user_agent',
                $request?->userAgent()
            ),

            'source' => $source,

            'admin_notified' => (bool) Arr::get(
                $attributes,
                'admin_notified',
                false
            ),

            'whatsapp_notified' => (bool) Arr::get(
                $attributes,
                'whatsapp_notified',
                false
            ),

            'sms_notified' => (bool) Arr::get(
                $attributes,
                'sms_notified',
                false
            ),

            'push_notified' => (bool) Arr::get(
                $attributes,
                'push_notified',
                false
            ),

            'occurred_at' => Arr::get(
                $attributes,
                'occurred_at',
                now()
            ),
        ];
    }

    /**
     * Extract known tracking fields from an HTTP request.
     */
    protected function extractRequestFields(array $requestData): array
    {
        $aliases = [
            'session_id' => ['session_id', 'sessionId'],
            'device_id' => ['device_id', 'deviceId'],
            'device_token' => ['device_token', 'deviceToken', 'fcm_token'],
            'platform' => ['platform'],
            'device_name' => ['device_name', 'deviceName'],
            'operating_system' => [
                'operating_system',
                'operatingSystem',
                'os_version',
            ],
            'app_version' => ['app_version', 'appVersion'],
            'pickup_location' => [
                'pickup_location',
                'pickup_address',
                'pickup',
            ],
            'pickup_city' => ['pickup_city', 'from_city'],
            'pickup_latitude' => [
                'pickup_latitude',
                'pickup_lat',
                'pickupLat',
            ],
            'pickup_longitude' => [
                'pickup_longitude',
                'pickup_lng',
                'pickupLng',
            ],
            'drop_location' => [
                'drop_location',
                'drop_address',
                'drop',
            ],
            'drop_city' => ['drop_city', 'to_city'],
            'drop_latitude' => [
                'drop_latitude',
                'drop_lat',
                'dropLat',
            ],
            'drop_longitude' => [
                'drop_longitude',
                'drop_lng',
                'dropLng',
            ],
            'start_datetime' => [
                'start_datetime',
                'pickup_datetime',
                'startDateTime',
            ],
            'end_datetime' => [
                'end_datetime',
                'drop_datetime',
                'endDateTime',
            ],
            'return_datetime' => [
                'return_datetime',
                'returnDateTime',
            ],
            'vehicle_category_id' => [
                'vehicle_category_id',
                'category_id',
            ],
            'vehicle_id' => [
                'vehicle_id',
                'self_drive_vehicle_id',
            ],
            'vehicle_name' => [
                'vehicle_name',
                'vehicle',
                'category_name',
            ],
            'plan_type' => ['plan_type', 'plan'],
            'passengers' => ['passengers', 'passenger_count'],
            'estimated_distance' => [
                'estimated_distance',
                'distance',
                'total_km',
            ],
            'estimated_amount' => [
                'estimated_amount',
                'fare',
                'total_amount',
                'grand_total',
            ],
            'service_type' => ['service_type', 'trip_type'],
        ];

        $result = [];

        foreach ($aliases as $column => $keys) {
            foreach ($keys as $key) {
                if (Arr::has($requestData, $key)) {
                    $result[$column] = Arr::get($requestData, $key);
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Resolve authenticated customer.
     */
    protected function resolveUser(
        User|int|null $user,
        ?Request $request
    ): ?User {
        if ($user instanceof User) {
            return $user;
        }

        if (is_int($user)) {
            return User::query()->find($user);
        }

        $requestUser = $request?->user();

        if ($requestUser instanceof User) {
            return $requestUser;
        }

        $authUser = Auth::user();

        return $authUser instanceof User ? $authUser : null;
    }

    /**
     * Resolve a session identifier.
     */
    protected function resolveSessionId(?Request $request): string
    {
        $headerSession = $request?->header('X-Session-ID');

        if (filled($headerSession)) {
            return (string) $headerSession;
        }

        $requestSession = $request?->input('session_id')
            ?? $request?->input('sessionId');

        if (filled($requestSession)) {
            return (string) $requestSession;
        }

        if ($request?->hasSession()) {
            $laravelSessionId = $request->session()->getId();

            if (filled($laravelSessionId)) {
                return $laravelSessionId;
            }
        }

        return (string) Str::uuid();
    }

    /**
     * Normalize an Indian or international mobile number.
     */
    protected function normalizeMobile(
        string|int|null $mobile
    ): ?string {
        if (blank($mobile)) {
            return null;
        }

        $value = trim((string) $mobile);
        $hasPlusPrefix = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value);

        if (blank($digits)) {
            return null;
        }

        return $hasPlusPrefix ? '+' . $digits : $digits;
    }

    /**
     * Calculate default activity priority.
     */
    protected function calculatePriority(array $attributes): string
    {
        $score = (int) Arr::get($attributes, 'intent_score', 0);
        $amount = (float) Arr::get(
            $attributes,
            'estimated_amount',
            0
        );

        return match (true) {
            $score >= 100 || $amount >= 10000 => 'urgent',
            $score >= 60 || $amount >= 5000 => 'high',
            $score >= 25 || $amount >= 2000 => 'normal',
            default => 'low',
        };
    }

    /**
     * Remove sensitive fields before logging tracking failures.
     */
    protected function safeLogAttributes(array $attributes): array
    {
        return Arr::except($attributes, [
            'otp',
            'password',
            'password_confirmation',
            'device_token',
            'authorization',
            'token',
            'payment_token',
            'card_number',
            'cvv',
            'aadhar_number',
            'driving_licence_number',
        ]);
    }
}