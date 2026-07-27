<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CustomerSearchActivity extends Model
{
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Module Constants
    |--------------------------------------------------------------------------
    */

    public const MODULE_TAXI = 'taxi';
    public const MODULE_SELF_DRIVE = 'self_drive';
    public const MODULE_BIKE_RENTAL = 'bike_rental';

    /*
    |--------------------------------------------------------------------------
    | Service Type Constants
    |--------------------------------------------------------------------------
    */

    public const SERVICE_ONE_WAY = 'one_way';
    public const SERVICE_ROUND_TRIP = 'round_trip';
    public const SERVICE_LOCAL = 'local';
    public const SERVICE_AIRPORT = 'airport';
    public const SERVICE_TOUR = 'tour';
    public const SERVICE_SELF_DRIVE = 'self_drive';
    public const SERVICE_BIKE_RENTAL = 'bike_rental';

    /*
    |--------------------------------------------------------------------------
    | Stage Constants
    |--------------------------------------------------------------------------
    */

    public const STAGE_INITIATED = 'initiated';
    public const STAGE_SEARCHED = 'searched';
    public const STAGE_RESULTS_VIEWED = 'results_viewed';
    public const STAGE_VEHICLE_VIEWED = 'vehicle_viewed';
    public const STAGE_VEHICLE_SELECTED = 'vehicle_selected';
    public const STAGE_CHECKOUT_STARTED = 'checkout_started';
    public const STAGE_PAYMENT_STARTED = 'payment_started';
    public const STAGE_CONVERTED = 'converted';
    public const STAGE_ABANDONED = 'abandoned';

    /*
    |--------------------------------------------------------------------------
    | Search Status Constants
    |--------------------------------------------------------------------------
    */

    public const SEARCH_STATUS_ACTIVE = 'active';
    public const SEARCH_STATUS_COMPLETED = 'completed';
    public const SEARCH_STATUS_CONVERTED = 'converted';
    public const SEARCH_STATUS_ABANDONED = 'abandoned';
    public const SEARCH_STATUS_EXPIRED = 'expired';

    /*
    |--------------------------------------------------------------------------
    | Checkout Status Constants
    |--------------------------------------------------------------------------
    */

    public const CHECKOUT_NOT_STARTED = 'not_started';
    public const CHECKOUT_STARTED = 'started';
    public const CHECKOUT_COMPLETED = 'completed';
    public const CHECKOUT_ABANDONED = 'abandoned';

    /*
    |--------------------------------------------------------------------------
    | Payment Status Constants
    |--------------------------------------------------------------------------
    */

    public const PAYMENT_NOT_STARTED = 'not_started';
    public const PAYMENT_STARTED = 'started';
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_SUCCESS = 'success';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_CANCELLED = 'cancelled';

    /*
    |--------------------------------------------------------------------------
    | Priority Constants
    |--------------------------------------------------------------------------
    */

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /*
    |--------------------------------------------------------------------------
    | Lead Status Constants
    |--------------------------------------------------------------------------
    */

    public const LEAD_NEW = 'new';
    public const LEAD_CONTACTED = 'contacted';
    public const LEAD_FOLLOW_UP = 'follow_up';
    public const LEAD_CONVERTED = 'converted';
    public const LEAD_LOST = 'lost';
    public const LEAD_NOT_INTERESTED = 'not_interested';

    /*
    |--------------------------------------------------------------------------
    | Source Constants
    |--------------------------------------------------------------------------
    */

    public const SOURCE_FLUTTER_APP = 'flutter_app';
    public const SOURCE_WEBSITE = 'website';
    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_API = 'api';

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',

        'user_id',
        'mobile',
        'customer_name',
        'customer_email',
        'session_id',
        'device_id',

        'source',
        'platform',
        'device_name',
        'operating_system',
        'app_version',
        'ip_address',
        'user_agent',

        'module',
        'service_type',
        'stage',

        'pickup_location',
        'pickup_city',
        'pickup_state',
        'pickup_country',
        'pickup_pincode',
        'pickup_latitude',
        'pickup_longitude',
        'pickup_place_id',

        'drop_location',
        'drop_city',
        'drop_state',
        'drop_country',
        'drop_pincode',
        'drop_latitude',
        'drop_longitude',
        'drop_place_id',

        'airport_name',
        'airport_code',
        'airport_trip_type',

        'via_locations',
        'total_stops',

        'start_datetime',
        'end_datetime',
        'return_datetime',

        'trip_days',
        'rental_hours',
        'rental_days',
        'rental_weeks',
        'rental_months',

        'package_name',
        'package_hours',
        'package_km',

        'vehicle_category_id',
        'vehicle_id',
        'vehicle_category_name',
        'vehicle_name',
        'vehicle_type',
        'fuel_type',
        'transmission_type',

        'plan_type',
        'plan_name',
        'minimum_hours',
        'included_km',

        'price_per_hour',
        'price_per_day',
        'price_per_week',
        'price_per_month',
        'weekly_discount_percent',
        'monthly_discount_percent',

        'helmet_option',
        'helmet_quantity',
        'helmet_charge',
        'security_deposit',

        'estimated_distance_km',
        'estimated_duration_minutes',
        'minimum_km',
        'billable_km',

        'currency',
        'base_fare',
        'estimated_amount',
        'discount_amount',
        'coupon_discount',
        'driver_allowance',
        'toll_amount',
        'parking_amount',
        'state_tax_amount',
        'waiting_charge',
        'tax_amount',
        'grand_total',
        'is_all_inclusive',

        'coupon_code',
        'coupon_id',

        'result_count',
        'has_available_vehicle',
        'minimum_result_price',
        'maximum_result_price',

        'search_status',
        'checkout_status',
        'payment_status',
        'is_converted',
        'is_abandoned',

        'booking_type',
        'booking_id',
        'booking_number',

        'checkout_started_at',
        'payment_started_at',
        'converted_at',
        'abandoned_at',

        'intent_score',
        'priority',
        'lead_status',
        'lead_notes',
        'follow_up_at',
        'assigned_to',

        'admin_notified',
        'whatsapp_notified',
        'sms_notified',
        'push_notified',
        'email_notified',

        'admin_notified_at',
        'whatsapp_notified_at',
        'sms_notified_at',
        'push_notified_at',
        'email_notified_at',

        'fare_breakdown',
        'filters',
        'search_data',
        'metadata',
        'utm_data',

        'customer_activity_id',

        'searched_at',
        'last_activity_at',
        'expires_at',
    ];

    /**
     * Attribute casts.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'vehicle_category_id' => 'integer',
        'vehicle_id' => 'integer',
        'coupon_id' => 'integer',
        'booking_id' => 'integer',
        'assigned_to' => 'integer',
        'customer_activity_id' => 'integer',

        'pickup_latitude' => 'decimal:7',
        'pickup_longitude' => 'decimal:7',
        'drop_latitude' => 'decimal:7',
        'drop_longitude' => 'decimal:7',

        'package_km' => 'decimal:2',

        'price_per_hour' => 'decimal:2',
        'price_per_day' => 'decimal:2',
        'price_per_week' => 'decimal:2',
        'price_per_month' => 'decimal:2',
        'weekly_discount_percent' => 'decimal:2',
        'monthly_discount_percent' => 'decimal:2',

        'helmet_charge' => 'decimal:2',
        'security_deposit' => 'decimal:2',

        'estimated_distance_km' => 'decimal:2',
        'minimum_km' => 'decimal:2',
        'billable_km' => 'decimal:2',

        'base_fare' => 'decimal:2',
        'estimated_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'driver_allowance' => 'decimal:2',
        'toll_amount' => 'decimal:2',
        'parking_amount' => 'decimal:2',
        'state_tax_amount' => 'decimal:2',
        'waiting_charge' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',

        'minimum_result_price' => 'decimal:2',
        'maximum_result_price' => 'decimal:2',

        'total_stops' => 'integer',
        'trip_days' => 'integer',
        'rental_hours' => 'integer',
        'rental_days' => 'integer',
        'rental_weeks' => 'integer',
        'rental_months' => 'integer',
        'package_hours' => 'integer',
        'minimum_hours' => 'integer',
        'included_km' => 'integer',
        'helmet_quantity' => 'integer',
        'estimated_duration_minutes' => 'integer',
        'result_count' => 'integer',
        'intent_score' => 'integer',

        'is_all_inclusive' => 'boolean',
        'has_available_vehicle' => 'boolean',
        'is_converted' => 'boolean',
        'is_abandoned' => 'boolean',

        'admin_notified' => 'boolean',
        'whatsapp_notified' => 'boolean',
        'sms_notified' => 'boolean',
        'push_notified' => 'boolean',
        'email_notified' => 'boolean',

        'via_locations' => 'array',
        'fare_breakdown' => 'array',
        'filters' => 'array',
        'search_data' => 'array',
        'metadata' => 'array',
        'utm_data' => 'array',

        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'return_datetime' => 'datetime',

        'checkout_started_at' => 'datetime',
        'payment_started_at' => 'datetime',
        'converted_at' => 'datetime',
        'abandoned_at' => 'datetime',
        'follow_up_at' => 'datetime',

        'admin_notified_at' => 'datetime',
        'whatsapp_notified_at' => 'datetime',
        'sms_notified_at' => 'datetime',
        'push_notified_at' => 'datetime',
        'email_notified_at' => 'datetime',

        'searched_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Hidden attributes.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function (CustomerSearchActivity $search): void {
            $search->uuid ??= (string) Str::uuid();
            $search->source ??= self::SOURCE_FLUTTER_APP;
            $search->stage ??= self::STAGE_SEARCHED;
            $search->search_status ??= self::SEARCH_STATUS_ACTIVE;
            $search->checkout_status ??= self::CHECKOUT_NOT_STARTED;
            $search->payment_status ??= self::PAYMENT_NOT_STARTED;
            $search->lead_status ??= self::LEAD_NEW;
            $search->searched_at ??= now();
            $search->last_activity_at ??= now();

            $search->mobile = self::normalizeMobile($search->mobile);

            if ($search->intent_score === null) {
                $search->intent_score = 0;
            }

            $search->priority = self::resolvePriority(
                (int) $search->intent_score,
                $search->stage,
                (bool) $search->is_converted
            );
        });

        static::updating(function (CustomerSearchActivity $search): void {
            $search->mobile = self::normalizeMobile($search->mobile);
            $search->last_activity_at = now();

            if (
                $search->isDirty('intent_score')
                || $search->isDirty('stage')
                || $search->isDirty('is_converted')
            ) {
                $search->priority = self::resolvePriority(
                    (int) $search->intent_score,
                    $search->stage,
                    (bool) $search->is_converted
                );
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function customerActivity(): BelongsTo
    {
        return $this->belongsTo(CustomerActivity::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeAuthenticated(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    public function scopeGuests(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForMobile(Builder $query, string $mobile): Builder
    {
        return $query->where('mobile', self::normalizeMobile($mobile));
    }

    public function scopeForSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopeServiceType(Builder $query, string $serviceType): Builder
    {
        return $query->where('service_type', $serviceType);
    }

    public function scopeStage(Builder $query, string $stage): Builder
    {
        return $query->where('stage', $stage);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('search_status', self::SEARCH_STATUS_ACTIVE)
            ->where('is_converted', false)
            ->where('is_abandoned', false);
    }

    public function scopeConverted(Builder $query): Builder
    {
        return $query->where('is_converted', true);
    }

    public function scopeAbandoned(Builder $query): Builder
    {
        return $query->where('is_abandoned', true);
    }

    public function scopeNotConverted(Builder $query): Builder
    {
        return $query->where('is_converted', false);
    }

    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', [
            self::PRIORITY_HIGH,
            self::PRIORITY_URGENT,
        ]);
    }

    public function scopeAdminPending(Builder $query): Builder
    {
        return $query->where('admin_notified', false);
    }

    public function scopeNeedsFollowUp(Builder $query): Builder
    {
        return $query
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<=', now())
            ->whereNotIn('lead_status', [
                self::LEAD_CONVERTED,
                self::LEAD_LOST,
                self::LEAD_NOT_INTERESTED,
            ]);
    }

    public function scopeOpenLeads(Builder $query): Builder
    {
        return $query->whereIn('lead_status', [
            self::LEAD_NEW,
            self::LEAD_CONTACTED,
            self::LEAD_FOLLOW_UP,
        ]);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('searched_at', today());
    }

    public function scopeBetweenDates(
        Builder $query,
        Carbon|string $from,
        Carbon|string $to
    ): Builder {
        return $query->whereBetween('searched_at', [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay(),
        ]);
    }

    public function scopeRoute(
        Builder $query,
        ?string $pickupCity,
        ?string $dropCity = null
    ): Builder {
        return $query
            ->when(
                filled($pickupCity),
                fn (Builder $builder) => $builder->where(
                    'pickup_city',
                    'like',
                    '%' . trim((string) $pickupCity) . '%'
                )
            )
            ->when(
                filled($dropCity),
                fn (Builder $builder) => $builder->where(
                    'drop_city',
                    'like',
                    '%' . trim((string) $dropCity) . '%'
                )
            );
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('searched_at');
    }

    /*
    |--------------------------------------------------------------------------
    | State Update Methods
    |--------------------------------------------------------------------------
    */

    public function markResultsViewed(?int $resultCount = null): bool
    {
        return $this->update([
            'stage' => self::STAGE_RESULTS_VIEWED,
            'result_count' => $resultCount ?? $this->result_count,
            'last_activity_at' => now(),
        ]);
    }

    public function markVehicleViewed(
        ?int $vehicleId = null,
        ?string $vehicleName = null
    ): bool {
        return $this->update([
            'stage' => self::STAGE_VEHICLE_VIEWED,
            'vehicle_id' => $vehicleId ?? $this->vehicle_id,
            'vehicle_name' => $vehicleName ?? $this->vehicle_name,
            'last_activity_at' => now(),
        ]);
    }

    public function markVehicleSelected(
        ?int $vehicleId = null,
        ?string $vehicleName = null
    ): bool {
        return $this->update([
            'stage' => self::STAGE_VEHICLE_SELECTED,
            'vehicle_id' => $vehicleId ?? $this->vehicle_id,
            'vehicle_name' => $vehicleName ?? $this->vehicle_name,
            'last_activity_at' => now(),
        ]);
    }

    public function markCheckoutStarted(): bool
    {
        return $this->update([
            'stage' => self::STAGE_CHECKOUT_STARTED,
            'checkout_status' => self::CHECKOUT_STARTED,
            'checkout_started_at' => $this->checkout_started_at ?? now(),
            'last_activity_at' => now(),
        ]);
    }

    public function markPaymentStarted(): bool
    {
        return $this->update([
            'stage' => self::STAGE_PAYMENT_STARTED,
            'payment_status' => self::PAYMENT_STARTED,
            'payment_started_at' => $this->payment_started_at ?? now(),
            'last_activity_at' => now(),
        ]);
    }

    public function markPaymentFailed(?array $metadata = null): bool
    {
        return $this->update([
            'payment_status' => self::PAYMENT_FAILED,
            'metadata' => $this->mergeArrayData($this->metadata, $metadata),
            'last_activity_at' => now(),
        ]);
    }

    public function markConverted(
        string $bookingType,
        int $bookingId,
        ?string $bookingNumber = null,
        ?float $grandTotal = null
    ): bool {
        return $this->update([
            'stage' => self::STAGE_CONVERTED,
            'search_status' => self::SEARCH_STATUS_CONVERTED,
            'checkout_status' => self::CHECKOUT_COMPLETED,
            'payment_status' => self::PAYMENT_SUCCESS,
            'is_converted' => true,
            'is_abandoned' => false,
            'booking_type' => $bookingType,
            'booking_id' => $bookingId,
            'booking_number' => $bookingNumber,
            'grand_total' => $grandTotal ?? $this->grand_total,
            'converted_at' => now(),
            'abandoned_at' => null,
            'lead_status' => self::LEAD_CONVERTED,
            'priority' => self::PRIORITY_LOW,
            'last_activity_at' => now(),
        ]);
    }

    public function markAbandoned(?Carbon $abandonedAt = null): bool
    {
        if ($this->is_converted) {
            return false;
        }

        return $this->update([
            'stage' => self::STAGE_ABANDONED,
            'search_status' => self::SEARCH_STATUS_ABANDONED,
            'checkout_status' => $this->checkout_status === self::CHECKOUT_STARTED
                ? self::CHECKOUT_ABANDONED
                : $this->checkout_status,
            'is_abandoned' => true,
            'abandoned_at' => $abandonedAt ?? now(),
            'last_activity_at' => now(),
        ]);
    }

    public function restoreAsActive(): bool
    {
        return $this->update([
            'search_status' => self::SEARCH_STATUS_ACTIVE,
            'is_abandoned' => false,
            'abandoned_at' => null,
            'last_activity_at' => now(),
        ]);
    }

    public function markExpired(): bool
    {
        if ($this->is_converted) {
            return false;
        }

        return $this->update([
            'search_status' => self::SEARCH_STATUS_EXPIRED,
            'last_activity_at' => now(),
        ]);
    }

    public function increaseIntentScore(int $points = 1): bool
    {
        $points = max(0, $points);

        $newScore = min(100, (int) $this->intent_score + $points);

        return $this->update([
            'intent_score' => $newScore,
            'priority' => self::resolvePriority(
                $newScore,
                $this->stage,
                (bool) $this->is_converted
            ),
            'last_activity_at' => now(),
        ]);
    }

    public function decreaseIntentScore(int $points = 1): bool
    {
        $points = max(0, $points);

        $newScore = max(0, (int) $this->intent_score - $points);

        return $this->update([
            'intent_score' => $newScore,
            'priority' => self::resolvePriority(
                $newScore,
                $this->stage,
                (bool) $this->is_converted
            ),
            'last_activity_at' => now(),
        ]);
    }

    public function refreshPriority(): bool
    {
        return $this->update([
            'priority' => self::resolvePriority(
                (int) $this->intent_score,
                $this->stage,
                (bool) $this->is_converted
            ),
        ]);
    }

    public function updateLeadStatus(
        string $status,
        ?string $notes = null,
        ?Carbon $followUpAt = null,
        ?int $assignedTo = null
    ): bool {
        return $this->update([
            'lead_status' => $status,
            'lead_notes' => $notes ?? $this->lead_notes,
            'follow_up_at' => $followUpAt,
            'assigned_to' => $assignedTo ?? $this->assigned_to,
            'last_activity_at' => now(),
        ]);
    }

    public function assignTo(int $userId): bool
    {
        return $this->update([
            'assigned_to' => $userId,
            'last_activity_at' => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Notification Update Methods
    |--------------------------------------------------------------------------
    */

    public function markAdminNotified(): bool
    {
        return $this->update([
            'admin_notified' => true,
            'admin_notified_at' => now(),
        ]);
    }

    public function markWhatsAppNotified(): bool
    {
        return $this->update([
            'whatsapp_notified' => true,
            'whatsapp_notified_at' => now(),
        ]);
    }

    public function markSmsNotified(): bool
    {
        return $this->update([
            'sms_notified' => true,
            'sms_notified_at' => now(),
        ]);
    }

    public function markPushNotified(): bool
    {
        return $this->update([
            'push_notified' => true,
            'push_notified_at' => now(),
        ]);
    }

    public function markEmailNotified(): bool
    {
        return $this->update([
            'email_notified' => true,
            'email_notified_at' => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getCustomerDisplayNameAttribute(): string
    {
        if (filled($this->customer_name)) {
            return (string) $this->customer_name;
        }

        if (filled($this->user?->name)) {
            return (string) $this->user->name;
        }

        if (filled($this->mobile)) {
            return (string) $this->mobile;
        }

        return 'Guest Customer';
    }

    public function getRouteSummaryAttribute(): string
    {
        $pickup = $this->pickup_city
            ?: $this->pickup_location
            ?: 'Pickup';

        $drop = $this->drop_city
            ?: $this->drop_location;

        if ($this->service_type === self::SERVICE_LOCAL) {
            return trim((string) $pickup) . ' - Local Rental';
        }

        if ($this->service_type === self::SERVICE_SELF_DRIVE) {
            return trim((string) $pickup) . ' - Self Drive';
        }

        if ($this->service_type === self::SERVICE_BIKE_RENTAL) {
            return trim((string) $pickup) . ' - Bike Rental';
        }

        if (blank($drop)) {
            return trim((string) $pickup);
        }

        return trim((string) $pickup) . ' → ' . trim((string) $drop);
    }

    public function getServiceLabelAttribute(): string
    {
        return match ($this->service_type) {
            self::SERVICE_ONE_WAY => 'One Way',
            self::SERVICE_ROUND_TRIP => 'Round Trip',
            self::SERVICE_LOCAL => 'Local Rental',
            self::SERVICE_AIRPORT => 'Airport Transfer',
            self::SERVICE_TOUR => 'Multi-City Tour',
            self::SERVICE_SELF_DRIVE => 'Self Drive',
            self::SERVICE_BIKE_RENTAL => 'Bike Rental',
            default => Str::headline((string) $this->service_type),
        };
    }

    public function getStageLabelAttribute(): string
    {
        return Str::headline((string) $this->stage);
    }

    public function getFormattedAmountAttribute(): string
    {
        $amount = $this->grand_total
            ?? $this->estimated_amount
            ?? $this->minimum_result_price;

        if ($amount === null) {
            return 'Not available';
        }

        return ($this->currency ?: 'INR') . ' ' . number_format(
            (float) $amount,
            2
        );
    }

    public function getIsGuestAttribute(): bool
    {
        return $this->user_id === null;
    }

    public function getRequiresFollowUpAttribute(): bool
    {
        if (
            in_array($this->lead_status, [
                self::LEAD_CONVERTED,
                self::LEAD_LOST,
                self::LEAD_NOT_INTERESTED,
            ], true)
        ) {
            return false;
        }

        if ($this->follow_up_at === null) {
            return false;
        }

        return $this->follow_up_at->isPast()
            || $this->follow_up_at->isToday();
    }

    /*
    |--------------------------------------------------------------------------
    | Utility Methods
    |--------------------------------------------------------------------------
    */

    public static function resolvePriority(
        int $intentScore,
        ?string $stage = null,
        bool $isConverted = false
    ): string {
        if ($isConverted) {
            return self::PRIORITY_LOW;
        }

        if (
            in_array($stage, [
                self::STAGE_CHECKOUT_STARTED,
                self::STAGE_PAYMENT_STARTED,
            ], true)
            && $intentScore >= 60
        ) {
            return self::PRIORITY_URGENT;
        }

        return match (true) {
            $intentScore >= 80 => self::PRIORITY_URGENT,
            $intentScore >= 60 => self::PRIORITY_HIGH,
            $intentScore >= 30 => self::PRIORITY_MEDIUM,
            default => self::PRIORITY_LOW,
        };
    }

    public static function normalizeMobile(?string $mobile): ?string
    {
        if (blank($mobile)) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', (string) $mobile);

        if (blank($normalized)) {
            return null;
        }

        if (
            strlen($normalized) === 12
            && str_starts_with($normalized, '91')
        ) {
            $normalized = substr($normalized, 2);
        }

        if (
            strlen($normalized) === 11
            && str_starts_with($normalized, '0')
        ) {
            $normalized = substr($normalized, 1);
        }

        return $normalized;
    }

    private function mergeArrayData(
        mixed $currentData,
        ?array $newData
    ): ?array {
        if (empty($newData)) {
            return is_array($currentData) ? $currentData : null;
        }

        return array_merge(
            is_array($currentData) ? $currentData : [],
            $newData
        );
    }
}