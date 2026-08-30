<?php

namespace App\Models;

use App\Models\FleetManagement\TransporterProfile;
use App\Services\FinalBillingService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class SelfDriveBooking extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAYMENT_PENDING = 'payment_pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PICKUP_PENDING = 'pickup_pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_RETURN_PENDING = 'return_pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';

    public const SETTLEMENT_PENDING = 'pending';
    public const SETTLEMENT_COMPLETED = 'completed';

    protected $fillable = [
        'booking_no',
        'customer_id',
        'vehicle_id',
        'transporter_profile_id',
        'pickup_location',
        'delivery_address',
        'delivery_price',
        'pickup_address',
        'pickup_price',
        'pickup_latitude',
        'pickup_longitude',
        'customer_live_lat',
        'customer_live_lng',
        'location_sharing_enabled',
        'customer_live_location_updated_at',
        'start_datetime',
        'end_datetime',
        'booked_hours',
        'hourly_price',
        'minimum_booking_hours',
        'security_deposit',
        'total_days',
        'price_per_day',
        'total_amount',
        'status',
        'booking_status',
        'vendor_confirmation_status',
        'document_status',
        'vendor_confirmed_at',
        'vendor_rejected_at',
        'vendor_rejection_reason',
        'payment_type',
        'payment_status',
        'payment_method',
        'payment_reference',
        'advance_amount',
        'paid_amount',
        'remaining_amount',
        'payment_completed_at',
        'aadhaar_front',
        'aadhaar_back',
        'driving_licence_front',
        'driving_licence_back',
        'customer_selfie',
        'documents_uploaded_at',
        'documents_verified_at',
        'documents_rejected_at',
        'document_rejection_reason',
        'booking_confirmed_at',
        'pickup_otp',
        'pickup_otp_generated_at',
        'pickup_otp_expires_at',
        'pickup_otp_attempts',
        'pickup_otp_verified_at',
        'registration_unlocked_at',
        'return_otp',
        'return_otp_generated_at',
        'return_otp_expires_at',
        'return_otp_attempts',
        'return_otp_verified_at',
        'end_requested_at',
        'trip_start_datetime',
        'trip_end_datetime',
        'start_km',
        'end_km',
        'pickup_images',
        'drop_images',
        'pickup_fuel_level',
        'drop_fuel_level',
        'actual_hours',
        'free_km',
        'actual_km',
        'extra_hours',
        'extra_km',
        'extra_hour_rate',
        'extra_km_rate',
        'extra_hour_amount',
        'extra_km_amount',
        'damage_amount',
        'damage_note',
        'fuel_charge',
        'cleaning_charge',
        'late_return_charge',
        'other_charge',
        'other_charge_note',
        'final_amount',
        'refund_amount',
        'balance_due',
        'settlement_status',
        'final_bill_generated_at',
        'final_invoice_path',
        'refund_status',
        'refund_reference',
        'refund_initiated_at',
        'refunded_at',
        'completed_at',

        // Optional fields supported when corresponding columns exist.
        'unlimited_kms',
        'unlimited_km_selected',
        'special_request_total',
        'extra_service_amount',
        'toll_amount',
        'parking_amount',
        'government_tax_amount',
        'permit_tax_amount',
        'gst_percent',
        'gst_amount',
        'discount_amount',
        'manual_price',
        'online_payment_charge',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'vendor_confirmed_at' => 'datetime',
        'vendor_rejected_at' => 'datetime',
        'payment_completed_at' => 'datetime',
        'documents_uploaded_at' => 'datetime',
        'documents_verified_at' => 'datetime',
        'documents_rejected_at' => 'datetime',
        'booking_confirmed_at' => 'datetime',
        'pickup_otp_generated_at' => 'datetime',
        'pickup_otp_expires_at' => 'datetime',
        'pickup_otp_verified_at' => 'datetime',
        'registration_unlocked_at' => 'datetime',
        'return_otp_generated_at' => 'datetime',
        'return_otp_expires_at' => 'datetime',
        'return_otp_verified_at' => 'datetime',
        'end_requested_at' => 'datetime',
        'trip_start_datetime' => 'datetime',
        'trip_end_datetime' => 'datetime',
        'customer_live_location_updated_at' => 'datetime',
        'final_bill_generated_at' => 'datetime',
        'refund_initiated_at' => 'datetime',
        'refunded_at' => 'datetime',
        'completed_at' => 'datetime',

        'pickup_images' => 'array',
        'drop_images' => 'array',

        'booked_hours' => 'integer',
        'minimum_booking_hours' => 'integer',
        'total_days' => 'integer',
        'pickup_otp_attempts' => 'integer',
        'return_otp_attempts' => 'integer',

        'unlimited_kms' => 'boolean',
        'unlimited_km_selected' => 'boolean',
        'location_sharing_enabled' => 'boolean',

        'pickup_latitude' => 'decimal:7',
        'customer_live_lat' => 'decimal:7',
        'customer_live_lng' => 'decimal:7',
        'pickup_longitude' => 'decimal:7',
        'delivery_price' => 'decimal:2',
        'pickup_price' => 'decimal:2',
        'hourly_price' => 'decimal:2',
        'price_per_day' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'start_km' => 'decimal:2',
        'end_km' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'free_km' => 'decimal:2',
        'actual_km' => 'decimal:2',
        'extra_hours' => 'decimal:2',
        'extra_km' => 'decimal:2',
        'extra_hour_rate' => 'decimal:2',
        'extra_km_rate' => 'decimal:2',
        'extra_hour_amount' => 'decimal:2',
        'extra_km_amount' => 'decimal:2',
        'damage_amount' => 'decimal:2',
        'fuel_charge' => 'decimal:2',
        'cleaning_charge' => 'decimal:2',
        'late_return_charge' => 'decimal:2',
        'other_charge' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'special_request_total' => 'decimal:2',
        'extra_service_amount' => 'decimal:2',
        'toll_amount' => 'decimal:2',
        'parking_amount' => 'decimal:2',
        'government_tax_amount' => 'decimal:2',
        'permit_tax_amount' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'manual_price' => 'decimal:2',
        'online_payment_charge' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(
            TransporterProfile::class,
            'transporter_profile_id'
        );
    }

    public function scopeActiveBooking(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED,
            self::STATUS_FAILED,
        ]);
    }

    public function scopeOverlapping(
        Builder $query,
        Carbon|string $start,
        Carbon|string $end
    ): Builder {
        return $query
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start);
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PAYMENT_PENDING,
            self::STATUS_PICKUP_PENDING,
        ], true);
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING
            || $this->booking_status === self::STATUS_RUNNING;
    }

    public function isReturnPending(): bool
    {
        return $this->status === self::STATUS_RETURN_PENDING
            || $this->booking_status === self::STATUS_RETURN_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            || $this->booking_status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED
            || $this->booking_status === self::STATUS_CANCELLED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED
            || $this->booking_status === self::STATUS_REJECTED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED
            || $this->booking_status === self::STATUS_FAILED;
    }

    public function isTerminal(): bool
    {
        return $this->isCompleted()
            || $this->isCancelled()
            || $this->isRejected()
            || $this->isFailed();
    }

    public function canPickup(): bool
    {
        return ! $this->isTerminal()
            && ! $this->isRunning()
            && $this->vendor_confirmation_status === 'confirmed'
            && $this->document_status === 'approved'
            && in_array($this->status, [
                self::STATUS_CONFIRMED,
                self::STATUS_PICKUP_PENDING,
            ], true);
    }

    public function canReturn(): bool
    {
        return ! $this->isTerminal()
            && ($this->isRunning() || $this->isReturnPending());
    }

    public function canCancel(): bool
    {
        return ! $this->isTerminal()
            && ! $this->isRunning()
            && ! $this->isReturnPending();
    }

    protected static function booted(): void
    {
        static::creating(function (self $booking): void {
            /*
             * booking_no column is NOT NULL in the database.
             * The final SD000001-style number needs the database ID,
             * which does not exist until after insert.
             *
             * Therefore save a unique temporary value for the INSERT,
             * then replace it in the created event below.
             */
            if (blank($booking->booking_no)) {
                $booking->booking_no =
                    'TMP' . strtoupper(bin2hex(random_bytes(6)));
            }

            $booking->status ??= self::STATUS_PENDING;
            $booking->booking_status ??= 'pending_vendor_confirmation';
            $booking->vendor_confirmation_status ??= 'pending';
            $booking->document_status ??= 'not_uploaded';
            $booking->settlement_status ??= self::SETTLEMENT_PENDING;
            $booking->refund_status ??= 'not_applicable';

            $booking->syncVehicleData();
            $booking->syncDuration();
            $booking->syncPayment();
        });

        static::created(function (self $booking): void {
            /*
             * Convert only our temporary booking number to the final,
             * customer-facing booking number.
             *
             * Example:
             * ID 1   => SD000001
             * ID 27  => SD000027
             * ID 123 => SD000123
             */
            if (
                blank($booking->booking_no)
                || str_starts_with((string) $booking->booking_no, 'TMP')
            ) {
                $booking->forceFill([
                    'booking_no' => 'SD' . str_pad(
                        (string) $booking->getKey(),
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),
                ])->saveQuietly();
            }
        });

        static::updating(function (self $booking): void {
            if ($booking->isDirty('vehicle_id')) {
                $booking->syncVehicleData();
            }

            if ($booking->isDirty([
                'start_datetime',
                'end_datetime',
            ])) {
                $booking->syncDuration();
            }

            if ($booking->isDirty([
                'payment_type',
                'total_amount',
                'final_amount',
                'manual_price',
                'security_deposit',
                'online_payment_charge',
                'paid_amount',
                'refund_amount',
            ])) {
                $booking->syncPayment();
            }
        });

        static::updated(function (self $booking): void {
            if (! $booking->wasChanged('status')) {
                return;
            }

            try {
                $booking->sendStatusWhatsApp();
            } catch (\Throwable $e) {
                Log::error('Self Drive status WhatsApp failed.', [
                    'booking_id' => $booking->id,
                    'booking_no' => $booking->booking_no,
                    'status' => $booking->status,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function sendStatusWhatsApp(): void
    {
        $this->loadMissing(['customer', 'vehicle', 'transporter']);

        $mobile = trim((string) (
            $this->customer?->mobile
            ?? ''
        ));

        if ($mobile === '') {
            Log::warning('Self Drive status WhatsApp skipped: customer mobile missing.', [
                'booking_id' => $this->id,
                'booking_no' => $this->booking_no,
                'status' => $this->status,
            ]);
            return;
        }

        $customerName = trim((string) (
            $this->customer?->name
            ?? 'Customer'
        )) ?: 'Customer';

        $vehicleName = trim(
            (string) ($this->vehicle?->car_company_name ?? '')
            . ' '
            . (string) ($this->vehicle?->model_name ?? '')
        );

        if ($vehicleName === '') {
            $vehicleName = 'Self Drive Vehicle';
        }

        $pickup = trim((string) (
            $this->pickup_location
            ?: $this->delivery_address
            ?: 'N/A'
        ));

        $travelDate = $this->start_datetime
            ? Carbon::parse($this->start_datetime)->format('d F Y')
            : 'N/A';

        $travelTime = $this->start_datetime
            ? Carbon::parse($this->start_datetime)->format('h:i A')
            : 'N/A';

        $common = [
            'mobile' => $mobile,
            'customer_mobile' => $mobile,
            'customer_name' => $customerName,
            'booking_id' => (string) ($this->booking_no ?: $this->id),
            'service_type' => 'Self Drive Car Rental',
            'vehicle_name' => $vehicleName,
            'pickup' => $pickup,
            'route' => $pickup,
            'travel_date' => $travelDate,
            'travel_time' => $travelTime,
            'total_amount' => number_format(
                $this->effectiveRentalAmount(),
                2,
                '.',
                ''
            ),
            'rental_amount' => number_format(
                $this->effectiveRentalAmount(),
                2,
                '.',
                ''
            ),
            'security_deposit' => number_format(
                (float) ($this->security_deposit ?? 0),
                2,
                '.',
                ''
            ),
            'payable_amount' => number_format(
                $this->payableAmount(),
                2,
                '.',
                ''
            ),
            'paid_amount' => number_format(
                (float) ($this->paid_amount ?? 0),
                2,
                '.',
                ''
            ),
            'remaining_amount' => number_format(
                (float) ($this->remaining_amount ?? 0),
                2,
                '.',
                ''
            ),
            'payment_status' => ucfirst(
                (string) ($this->payment_status ?: 'pending')
            ),
            'refund_amount' => number_format(
                (float) ($this->refund_amount ?? 0),
                2,
                '.',
                ''
            ),
        ];

        $event = match ((string) $this->status) {
            self::STATUS_CONFIRMED => 'selfdrive.booking.confirmed',
            self::STATUS_RUNNING => 'trip.started',
            self::STATUS_COMPLETED => 'trip.completed',
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED => 'booking.cancelled',
            default => null,
        };

        if (! $event) {
            return;
        }

        $result = WhatsAppService::dispatchEvent(
            $event,
            $common
        );

        if (! (bool) (
            $result['status']
            ?? $result['success']
            ?? false
        )) {
            Log::warning('Self Drive status WhatsApp was not accepted.', [
                'booking_id' => $this->id,
                'booking_no' => $this->booking_no,
                'status' => $this->status,
                'event' => $event,
                'result' => $result,
            ]);
        }
    }

    /**
     * Send a Self Drive WhatsApp template by template key.
     *
     * This method is used by Filament actions such as payment received,
     * pickup OTP and return OTP. Pricing comes from the central model helpers,
     * so Manual Price is reflected everywhere automatically.
     */
    public function sendSelfDriveTemplate(
        string $templateKey,
        array $extra = []
    ): bool {
        $this->loadMissing(['customer', 'vehicle']);

        $mobile = trim((string) ($this->customer?->mobile ?? ''));

        if ($mobile === '') {
            Log::warning('Self Drive WhatsApp skipped: customer mobile missing.', [
                'booking_id' => $this->id,
                'booking_no' => $this->booking_no,
                'template_key' => $templateKey,
            ]);

            return false;
        }

        $customerName = trim((string) ($this->customer?->name ?? 'Customer'))
            ?: 'Customer';

        $vehicleName = trim(
            (string) ($this->vehicle?->car_company_name ?? '')
            . ' '
            . (string) ($this->vehicle?->model_name ?? '')
        ) ?: 'Self Drive Vehicle';

        $start = $this->start_datetime
            ? Carbon::parse($this->start_datetime)
            : null;

        $end = $this->end_datetime
            ? Carbon::parse($this->end_datetime)
            : null;

        $pickup = trim((string) (
            $this->pickup_location
            ?: $this->delivery_address
            ?: 'Dura Cabs'
        ));

        $data = array_merge([
            'customer_name' => $customerName,
            'booking_id' => (string) ($this->booking_no ?: $this->id),
            'vehicle_name' => $vehicleName,
            'pickup' => $pickup,
            'start_date' => $start?->format('d M Y') ?? 'N/A',
            'start_time' => $start?->format('h:i A') ?? 'N/A',
            'end_date' => $end?->format('d M Y') ?? 'N/A',
            'end_time' => $end?->format('h:i A') ?? 'N/A',

            // Central pricing source: Manual Price overrides DB price.
            'rental_amount' => number_format(
                $this->effectiveRentalAmount(),
                2,
                '.',
                ''
            ),
            'security_deposit' => number_format(
                (float) ($this->security_deposit ?? 0),
                2,
                '.',
                ''
            ),
            'payable_amount' => number_format(
                $this->payableAmount(),
                2,
                '.',
                ''
            ),
            'paid_amount' => number_format(
                (float) ($this->paid_amount ?? 0),
                2,
                '.',
                ''
            ),
            'remaining_amount' => number_format(
                (float) ($this->remaining_amount ?? 0),
                2,
                '.',
                ''
            ),
            'refund_amount' => number_format(
                (float) ($this->refund_amount ?? 0),
                2,
                '.',
                ''
            ),
            'payment_method' => (string) ($this->payment_method ?: 'N/A'),
            'payment_reference' => (string) ($this->payment_reference ?: 'N/A'),
            'start_km' => (string) ($this->start_km ?? 'N/A'),
            'end_km' => (string) ($this->end_km ?? 'N/A'),
            'total_km' => (string) ($this->actual_km ?? 'N/A'),
            'duration' => trim(
                ((int) ($this->total_days ?? 0)) . ' day(s), '
                . ((int) ($this->booked_hours ?? 0)) . ' hour(s)'
            ),
            'reason' => (string) (
                $this->vendor_rejection_reason
                ?: $this->document_rejection_reason
                ?: 'As updated by Dura Cabs'
            ),
        ], $extra);

        $params = match ($templateKey) {
            'selfdrive_booking_received' => [
                $data['customer_name'],
                $data['booking_id'],
                $data['vehicle_name'],
                $data['pickup'],
                $data['start_date'],
                $data['start_time'],
                $data['end_date'],
                $data['end_time'],
                $data['rental_amount'],
            ],
            'selfdrive_booking_confirmed' => [
                $data['customer_name'],
                $data['booking_id'],
                $data['vehicle_name'],
                $data['pickup'],
                $data['start_date'],
                $data['start_time'],
                $data['end_date'],
                $data['end_time'],
                $data['rental_amount'],
                $data['security_deposit'],
            ],
            'selfdrive_trip_started' => [
                $data['customer_name'],
                $data['booking_id'],
                $data['vehicle_name'],
                $data['start_time'],
                $data['start_km'],
            ],
            'selfdrive_trip_completed' => [
                $data['customer_name'],
                $data['booking_id'],
                $data['vehicle_name'],
                $data['total_km'],
                $data['duration'],
                $data['rental_amount'],
                $data['paid_amount'],
                $data['remaining_amount'],
            ],
            'selfdrive_booking_cancelled' => [
                $data['customer_name'],
                $data['booking_id'],
                $data['vehicle_name'],
                $data['start_date'],
                $data['paid_amount'],
                $data['refund_amount'],
            ],
            'selfdrive_booking_rejected' => [
                $data['customer_name'],
                $data['booking_id'],
                $data['vehicle_name'],
                $data['start_date'],
                $data['reason'],
            ],
            'selfdrive_pickup_otp',
            'selfdrive_return_otp' => [
                $data['customer_name'],
                $data['booking_id'],
                $data['vehicle_name'],
                (string) ($data['otp'] ?? ''),
            ],
            'selfdrive_payment_received' => [
                $data['customer_name'],
                $data['booking_id'],
                (string) ($data['received_amount'] ?? $data['paid_amount']),
                $data['payment_method'],
                $data['payment_reference'],
                $data['remaining_amount'],
            ],
            'selfdrive_security_refunded' => [
                $data['customer_name'],
                $data['booking_id'],
                $data['refund_amount'],
                (string) ($data['refund_reference'] ?? 'N/A'),
            ],
            default => [],
        };

        if ($params === []) {
            return false;
        }

        $result = WhatsAppService::sendByKey(
            templateKey: $templateKey,
            number: $mobile,
            bodyParameters: $params
        );

        $ok = (bool) (
            $result['status']
            ?? $result['success']
            ?? false
        );

        if (! $ok) {
            Log::warning('Self Drive WhatsApp template failed.', [
                'booking_id' => $this->id,
                'booking_no' => $this->booking_no,
                'template_key' => $templateKey,
                'result' => $result,
            ]);
        }

        return $ok;
    }

    public function syncVehicleData(): void
    {
        if (! $this->vehicle_id) {
            return;
        }

        $vehicle = Vehicle::query()->find($this->vehicle_id);

        if (! $vehicle) {
            return;
        }
        $this->transporter_profile_id =
            $vehicle->transporter_profile_id;

        $this->hourly_price ??=
            (float) ($vehicle->hourly_price ?? 0);

        $this->price_per_day ??=
            (float) ($vehicle->daily_price ?? 0);

        $this->security_deposit ??=
            (float) ($vehicle->security_deposit ?? 0);

        $this->minimum_booking_hours ??=
            max(
                1,
                (int) ($vehicle->minimum_booking_hours ?? 1)
            );

        $this->extra_hour_rate ??=
            (float) ($vehicle->extra_hour_rate ?? 0);

        $this->extra_km_rate ??=
            (float) ($vehicle->extra_km_rate ?? 0);
    }

    public function syncDuration(): void
    {
        if (! $this->start_datetime || ! $this->end_datetime) {
            return;
        }

        $start = Carbon::parse($this->start_datetime);
        $end = Carbon::parse($this->end_datetime);

        if ($end->lte($start)) {
            return;
        }

        $minutes = $start->diffInMinutes($end);
        $hours = max(1, (int) ceil($minutes / 60));

        $this->booked_hours = $hours;
        $this->total_days = max(
            1,
            (int) ceil($hours / 24)
        );
    }

    public function effectiveRentalAmount(): float
    {
        /*
         * Single source of truth for the rental amount.
         *
         * - Manual Price overrides the automatic/database rental price.
         * - Manual Price is GST-inclusive.
         * - final_amount is treated as the effective rental total when present.
         * - total_amount remains the automatic/database rental amount.
         */
        $manual = (float) ($this->manual_price ?? 0);

        if ($manual > 0) {
            return round($manual, 2);
        }

        $final = (float) ($this->final_amount ?? 0);

        if ($final > 0) {
            return round($final, 2);
        }

        return round(max(0, (float) ($this->total_amount ?? 0)), 2);
    }

    public function includedGstAmount(): float
    {
        $rental = $this->effectiveRentalAmount();
        $rate = (float) ($this->gst_percent ?? 18);

        if ($rental <= 0 || $rate <= 0) {
            return 0.0;
        }

        $taxable = $rental / (1 + ($rate / 100));

        return round(max(0, $rental - $taxable), 2);
    }

    public function taxableRentalAmount(): float
    {
        return round(
            max(0, $this->effectiveRentalAmount() - $this->includedGstAmount()),
            2
        );
    }

    public function payableAmount(): float
    {
        /*
         * Customer payable = GST-inclusive rental total + refundable security deposit.
         * Security remains separate from rental income but must be collected.
         */
        return round(
            max(
                0,
                $this->effectiveRentalAmount()
                + (float) ($this->security_deposit ?? 0)
                + (float) ($this->online_payment_charge ?? 0)
            ),
            2
        );
    }

    public function syncPayment(): void
    {
        $payable = $this->payableAmount();
        $paid = max(
            0,
            (float) ($this->paid_amount ?? 0)
        );

        if (
            $this->payment_type === 'advance'
            && ! $this->exists
        ) {
            $paid = min(500, $payable);
            $this->advance_amount = $paid;
        }

        if (
            $this->payment_type === 'full'
            && ! $this->exists
        ) {
            $paid = $payable;
            $this->advance_amount = 0;
        }

        $this->paid_amount = min($paid, $payable);
        $this->remaining_amount = max(
            0,
            $payable - $this->paid_amount
        );
        $this->balance_due = $this->remaining_amount;

        if ($this->payment_status === 'refunded') {
            return;
        }

        if ($this->paid_amount <= 0) {
            $this->payment_status = 'pending';
            $this->payment_completed_at = null;
            return;
        }

        if ($this->remaining_amount > 0) {
            $this->payment_status = 'partial';
            $this->payment_completed_at = null;
            return;
        }

        $this->payment_status = 'paid';
        $this->payment_completed_at ??= now();
    }

    public function calculateActualKm(): float
    {
        if (
            $this->start_km === null
            || $this->end_km === null
        ) {
            return 0;
        }

        return max(
            0,
            (float) $this->end_km
            - (float) $this->start_km
        );
    }

    public function calculateActualHours(): float
    {
        if (
            ! $this->trip_start_datetime
            || ! $this->trip_end_datetime
        ) {
            return 0;
        }

        return max(
            0,
            Carbon::parse($this->trip_start_datetime)
                ->diffInMinutes(
                    Carbon::parse($this->trip_end_datetime)
                ) / 60
        );
    }

    public function hasUnlimitedKms(): bool
    {
        return $this->booleanAttribute([
            'unlimited_km_selected',
            'unlimited_kms',
        ]);
    }

    public function calculateFinalAmount(): float
    {
        $billing = $this->finalBilling();

        return (float) ($billing['grand_total'] ?? 0);
    }

    public function finalBilling(): array
    {
        return app(FinalBillingService::class)->calculate([
            'service_type' => 'self_drive',
            'base_fare' => $this->effectiveRentalAmount(),
            'special_request_total' =>
                $this->numericAttribute([
                    'special_request_total',
                    'extra_service_amount',
                ]),
            'extra_hour_amount' =>
                (float) ($this->extra_hour_amount ?? 0),
            'extra_km_amount' =>
                (float) ($this->extra_km_amount ?? 0),
            'unlimited_km_selected' =>
                $this->hasUnlimitedKms(),
            'toll_amount' =>
                $this->numericAttribute(['toll_amount']),
            'parking_amount' =>
                $this->numericAttribute(['parking_amount']),
            'government_tax_amount' =>
                $this->numericAttribute([
                    'government_tax_amount',
                    'permit_tax_amount',
                ]),
            'security_deposit' =>
                (float) ($this->security_deposit ?? 0),
            'damage_amount' =>
                (float) ($this->damage_amount ?? 0),
            'fuel_charge' =>
                (float) ($this->fuel_charge ?? 0),
            'cleaning_charge' =>
                (float) ($this->cleaning_charge ?? 0),
            'late_return_charge' =>
                (float) ($this->late_return_charge ?? 0),
            'other_charge' =>
                (float) ($this->other_charge ?? 0),
            'coupon_discount' =>
                $this->numericAttribute([
                    'coupon_discount',
                    'coupon_value',
                ]),
            'payment_method' =>
                (string) ($this->payment_method ?? 'cash'),
            'paid_amount' =>
                (float) ($this->paid_amount ?? 0),
            'manual_price' =>
                (float) ($this->manual_price ?? 0),
            'gst_percent' =>
                (float) ($this->gst_percent ?? 18),
        ]);
    }

    public function refreshTripAmounts(): void
    {
        $this->actual_km = $this->calculateActualKm();
        $this->actual_hours = $this->calculateActualHours();

        $this->extra_hours = max(
            0,
            (float) $this->actual_hours
            - (float) $this->booked_hours
        );

        $this->extra_hour_amount = round(
            (float) $this->extra_hours
            * (float) $this->extra_hour_rate,
            2
        );

        if ($this->hasUnlimitedKms()) {
            $this->extra_km = 0;
            $this->extra_km_amount = 0;
        } else {
            $this->extra_km = max(
                0,
                (float) $this->actual_km
                - (float) $this->free_km
            );

            $this->extra_km_amount = round(
                (float) $this->extra_km
                * (float) $this->extra_km_rate,
                2
            );
        }

        $billing = $this->finalBilling();

        /*
         * Keep final_amount as the effective GST-inclusive RENTAL total.
         * Do not replace it with a payable amount that may include security deposit.
         */
        $this->final_amount = $this->effectiveRentalAmount();

        $this->refund_amount =
            (float) ($billing['refund_amount'] ?? 0);

        $this->setOptionalAttribute(
            'gst_percent',
            $billing['gst_percent'] ?? 18
        );

        $this->setOptionalAttribute(
            'gst_amount',
            $this->includedGstAmount()
        );

        $this->setOptionalAttribute(
            'online_payment_charge',
            $billing['online_payment_charge'] ?? 0
        );

        $this->syncPayment();
    }

    private function numericAttribute(
        array $keys
    ): float {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $this->attributes)) {
                continue;
            }

            $value = $this->attributes[$key];

            if ($value === null || $value === '') {
                continue;
            }

            return max(0, (float) $value);
        }

        return 0;
    }

    private function booleanAttribute(
        array $keys
    ): bool {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $this->attributes)) {
                continue;
            }

            $value = $this->attributes[$key];

            if (is_bool($value)) {
                return $value;
            }

            if ((int) $value === 1) {
                return true;
            }

            if (
                in_array(
                    strtolower(trim((string) $value)),
                    ['true', 'yes', 'on', 'selected'],
                    true
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function setOptionalAttribute(
        string $key,
        mixed $value
    ): void {
        if (! array_key_exists($key, $this->attributes)) {
            return;
        }

        $this->setAttribute($key, $value);
    }
}