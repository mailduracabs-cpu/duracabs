<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomerActivity extends Model
{
    use HasFactory;

    /**
     * Mass assignable columns.
     */
    protected $fillable = [
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

    /**
     * Attribute casting.
     */
    protected $casts = [
        'user_id' => 'integer',
        'related_id' => 'integer',

        'pickup_latitude' => 'decimal:7',
        'pickup_longitude' => 'decimal:7',
        'drop_latitude' => 'decimal:7',
        'drop_longitude' => 'decimal:7',

        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'return_datetime' => 'datetime',
        'occurred_at' => 'datetime',

        'vehicle_category_id' => 'integer',
        'vehicle_id' => 'integer',
        'passengers' => 'integer',

        'estimated_distance' => 'decimal:2',
        'estimated_amount' => 'decimal:2',

        'intent_score' => 'integer',

        'data' => 'array',
        'utm_data' => 'array',

        'admin_notified' => 'boolean',
        'whatsapp_notified' => 'boolean',
        'sms_notified' => 'boolean',
        'push_notified' => 'boolean',
    ];

    /**
     * Hide unnecessary technical fields from normal API responses.
     */
    protected $hidden = [
        'user_agent',
        'device_token',
        'updated_at',
    ];

    /**
     * Automatically generate UUID and occurred_at.
     */
    protected static function booted(): void
    {
        static::creating(function (CustomerActivity $activity): void {
            if (blank($activity->uuid)) {
                $activity->uuid = (string) Str::uuid();
            }

            if (blank($activity->occurred_at)) {
                $activity->occurred_at = now();
            }

            if (blank($activity->source)) {
                $activity->source = 'flutter_app';
            }

            if (blank($activity->priority)) {
                $activity->priority = 'normal';
            }

            if (blank($activity->lead_status)) {
                $activity->lead_status = 'new';
            }
        });
    }

    /**
     * Customer relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Filter activities belonging to authenticated customers.
     */
    public function scopeAuthenticated(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Filter guest customer activities.
     */
    public function scopeGuests(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    /**
     * Filter by activity event.
     */
    public function scopeEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Filter by module.
     */
    public function scopeModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Filter by service type.
     */
    public function scopeServiceType(
        Builder $query,
        string $serviceType
    ): Builder {
        return $query->where('service_type', $serviceType);
    }

    /**
     * Filter by customer mobile number.
     */
    public function scopeMobile(Builder $query, string $mobile): Builder
    {
        return $query->where('mobile', $mobile);
    }

    /**
     * Filter by session ID.
     */
    public function scopeSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Filter unread/unprocessed admin activities.
     */
    public function scopeAdminPending(Builder $query): Builder
    {
        return $query->where('admin_notified', false);
    }

    /**
     * Filter high-priority activities.
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Filter active sales leads.
     */
    public function scopeActiveLeads(Builder $query): Builder
    {
        return $query->whereIn('lead_status', [
            'new',
            'active',
            'contacted',
        ]);
    }

    /**
     * Filter activities created today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Filter activities created between dates.
     */
    public function scopeBetweenDates(
        Builder $query,
        mixed $from,
        mixed $to
    ): Builder {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Mark admin notification as generated.
     */
    public function markAdminNotified(): bool
    {
        return $this->forceFill([
            'admin_notified' => true,
        ])->save();
    }

    /**
     * Mark WhatsApp notification as sent.
     */
    public function markWhatsAppNotified(): bool
    {
        return $this->forceFill([
            'whatsapp_notified' => true,
        ])->save();
    }

    /**
     * Mark SMS notification as sent.
     */
    public function markSmsNotified(): bool
    {
        return $this->forceFill([
            'sms_notified' => true,
        ])->save();
    }

    /**
     * Mark push notification as sent.
     */
    public function markPushNotified(): bool
    {
        return $this->forceFill([
            'push_notified' => true,
        ])->save();
    }

    /**
     * Update lead status.
     */
    public function updateLeadStatus(string $status): bool
    {
        return $this->forceFill([
            'lead_status' => $status,
        ])->save();
    }

    /**
     * Increase activity intent score.
     */
    public function addIntentScore(int $score): bool
    {
        if ($score <= 0) {
            return true;
        }

        $this->increment('intent_score', $score);

        $this->refreshPriority();

        return true;
    }

    /**
     * Set priority based on intent score and estimated amount.
     */
    public function refreshPriority(): bool
    {
        $score = (int) $this->intent_score;
        $estimatedAmount = (float) ($this->estimated_amount ?? 0);

        $priority = match (true) {
            $score >= 100 || $estimatedAmount >= 10000 => 'urgent',
            $score >= 60 || $estimatedAmount >= 5000 => 'high',
            $score >= 25 || $estimatedAmount >= 2000 => 'normal',
            default => 'low',
        };

        if ($this->priority === $priority) {
            return true;
        }

        return $this->forceFill([
            'priority' => $priority,
        ])->save();
    }

    /**
     * Customer display name for admin panel.
     */
    public function getCustomerDisplayNameAttribute(): string
    {
        return $this->customer_name
            ?: $this->user?->name
            ?: $this->mobile
            ?: 'Guest Customer';
    }

    /**
     * Route summary for admin panel.
     */
    public function getRouteSummaryAttribute(): ?string
    {
        $pickup = $this->pickup_city ?: $this->pickup_location;
        $drop = $this->drop_city ?: $this->drop_location;

        if ($pickup && $drop) {
            return "{$pickup} → {$drop}";
        }

        return $pickup ?: $drop;
    }

    /**
     * Human-readable activity title.
     */
    public function getActivityTitleAttribute(): string
    {
        return match ($this->event) {
            'otp_requested' => 'OTP Requested',
            'otp_verified' => 'OTP Verified',
            'user_registered' => 'New Customer Registered',
            'user_login' => 'Customer Logged In',
            'one_way_search' => 'New One Way Search',
            'round_trip_search' => 'New Round Trip Search',
            'local_search' => 'New Local Taxi Search',
            'airport_search' => 'New Airport Search',
            'tour_search' => 'New Tour Search',
            'self_drive_search' => 'New Self Drive Search',
            'bike_rental_search' => 'New Bike Rental Search',
            'vehicle_viewed' => 'Vehicle Viewed',
            'vehicle_selected' => 'Vehicle Selected',
            'checkout_started' => 'Checkout Started',
            'payment_started' => 'Payment Started',
            'payment_failed' => 'Payment Failed',
            'payment_success' => 'Payment Successful',
            'booking_created' => 'New Booking Created',
            'booking_cancelled' => 'Booking Cancelled',
            'trip_started' => 'Trip Started',
            'trip_completed' => 'Trip Completed',
            default => Str::headline($this->event),
        };
    }
}