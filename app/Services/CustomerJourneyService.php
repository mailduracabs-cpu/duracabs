<?php

namespace App\Services;

use App\Models\CustomerSearchActivity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class CustomerJourneyService
{
    /**
     * Register a customer search event.
     *
     * Compatible with both calls:
     * - searchPerformed($payload)
     * - searchPerformed(searchActivity: $activity, searchData: $payload)
     */
    public function searchPerformed(
        CustomerSearchActivity|array|null $searchActivity = null,
        array $searchData = []
    ): CustomerSearchActivity {
        [$activity, $payload] = $this->resolveCallPayload(
            activityOrPayload: $searchActivity,
            secondaryPayload: $searchData
        );

        return $this->record(
            event: 'search_performed',
            payload: $payload,
            activity: $activity
        );
    }

    /**
     * Register checkout-started event.
     */
    public function checkoutStarted(
        CustomerSearchActivity|array|null $searchActivity = null,
        array $checkoutData = []
    ): CustomerSearchActivity {
        [$activity, $payload] = $this->resolveCallPayload(
            activityOrPayload: $searchActivity,
            secondaryPayload: $checkoutData
        );

        return $this->record(
            event: 'checkout_started',
            payload: $payload,
            activity: $activity
        );
    }

    /**
     * Register payment-started event.
     */
    public function paymentStarted(
        CustomerSearchActivity|array|null $searchActivity = null,
        array $paymentData = []
    ): CustomerSearchActivity {
        [$activity, $payload] = $this->resolveCallPayload(
            activityOrPayload: $searchActivity,
            secondaryPayload: $paymentData
        );

        return $this->record(
            event: 'payment_started',
            payload: $payload,
            activity: $activity
        );
    }

    /**
     * Register successful payment and booking conversion.
     */
    public function paymentSucceeded(
        CustomerSearchActivity|array|null $searchActivity = null,
        array $paymentData = [],
        array $bookingData = []
    ): CustomerSearchActivity {
        [$activity, $payload] = $this->resolveCallPayload(
            activityOrPayload: $searchActivity,
            secondaryPayload: $paymentData
        );

        if ($bookingData !== []) {
            $payload = array_replace_recursive($payload, [
                'booking_data' => $bookingData,
            ]);
        }

        return $this->record(
            event: 'payment_succeeded',
            payload: $payload,
            activity: $activity
        );
    }

    /**
     * Register failed payment.
     */
    public function paymentFailed(
        CustomerSearchActivity|array|null $searchActivity = null,
        array $paymentData = [],
        ?string $reason = null
    ): CustomerSearchActivity {
        [$activity, $payload] = $this->resolveCallPayload(
            activityOrPayload: $searchActivity,
            secondaryPayload: $paymentData
        );

        if (filled($reason)) {
            $payload['failure_reason'] = $reason;
        }

        return $this->record(
            event: 'payment_failed',
            payload: $payload,
            activity: $activity
        );
    }

    /**
     * Register booking-created event.
     * Used by SelfDriveBookingService.
     */
    public function bookingCreated(array $bookingData): CustomerSearchActivity
    {
        return $this->record(
            event: 'booking_created',
            payload: $bookingData,
            activity: $this->findActivityFromPayload($bookingData)
        );
    }

    /**
     * Register booking completion.
     */
    public function bookingCompleted(
        CustomerSearchActivity|array|null $searchActivity = null,
        array $bookingData = []
    ): CustomerSearchActivity {
        [$activity, $payload] = $this->resolveCallPayload(
            activityOrPayload: $searchActivity,
            secondaryPayload: $bookingData
        );

        return $this->record(
            event: 'booking_completed',
            payload: $payload,
            activity: $activity
        );
    }

    /**
     * Register booking cancellation.
     */
    public function bookingCancelled(
        CustomerSearchActivity|array|null $searchActivity = null,
        array $bookingData = [],
        ?string $reason = null
    ): CustomerSearchActivity {
        [$activity, $payload] = $this->resolveCallPayload(
            activityOrPayload: $searchActivity,
            secondaryPayload: $bookingData
        );

        if (filled($reason)) {
            $payload['cancellation_reason'] = $reason;
        }

        return $this->record(
            event: 'booking_cancelled',
            payload: $payload,
            activity: $activity
        );
    }

    /**
     * Find one journey activity by primary key, uuid or activity_uuid.
     */
    public function findActivityOrFail(int|string $identifier): CustomerSearchActivity
    {
        $query = CustomerSearchActivity::query();
        $columns = $this->columns();

        if (is_int($identifier) || ctype_digit((string) $identifier)) {
            return $query->findOrFail((int) $identifier);
        }

        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            throw (new ModelNotFoundException())->setModel(CustomerSearchActivity::class);
        }

        $query->where(function ($builder) use ($identifier, $columns): void {
            $hasCondition = false;

            foreach (['uuid', 'activity_uuid', 'session_id'] as $column) {
                if (! in_array($column, $columns, true)) {
                    continue;
                }

                $hasCondition
                    ? $builder->orWhere($column, $identifier)
                    : $builder->where($column, $identifier);

                $hasCondition = true;
            }

            if (! $hasCondition) {
                $builder->whereRaw('1 = 0');
            }
        });

        return $query->firstOrFail();
    }

    /**
     * Normalize old and new controller method signatures.
     *
     * @return array{0: CustomerSearchActivity|null, 1: array}
     */
    private function resolveCallPayload(
        CustomerSearchActivity|array|null $activityOrPayload,
        array $secondaryPayload
    ): array {
        if ($activityOrPayload instanceof CustomerSearchActivity) {
            return [$activityOrPayload, $secondaryPayload];
        }

        $payload = is_array($activityOrPayload)
            ? array_replace_recursive($activityOrPayload, $secondaryPayload)
            : $secondaryPayload;

        return [$this->findActivityFromPayload($payload), $payload];
    }

    /**
     * Create or update the activity without assuming one fixed DB schema.
     */
    private function record(
        string $event,
        array $payload,
        ?CustomerSearchActivity $activity = null
    ): CustomerSearchActivity {
        try {
            $activity ??= $this->findActivityFromPayload($payload);
            $activity ??= new CustomerSearchActivity();

            $attributes = $this->buildAttributes(
                activity: $activity,
                event: $event,
                payload: $payload
            );

            foreach ($attributes as $key => $value) {
                $activity->setAttribute($key, $value);
            }

            $activity->save();

            return $activity->refresh();
        } catch (Throwable $exception) {
            Log::warning('Customer journey event could not be recorded.', [
                'event' => $event,
                'message' => $exception->getMessage(),
                'payload' => Arr::except($payload, [
                    'password',
                    'token',
                    'payment_token',
                    'gateway_signature',
                ]),
            ]);

            /*
             * Journey tracking must never break the main booking/payment flow.
             * Return an existing model when available; otherwise return an
             * unsaved model carrying the normalized event payload.
             */
            $activity ??= new CustomerSearchActivity();
            $activity->setAttribute('journey_event', $event);
            $activity->setAttribute('journey_payload', $payload);

            return $activity;
        }
    }

    /**
     * Build only attributes that actually exist in customer_search_activities.
     */
    private function buildAttributes(
        CustomerSearchActivity $activity,
        string $event,
        array $payload
    ): array {
        $columns = $this->columns();
        $flat = $this->flattenPayload($payload);
        $attributes = [];

        foreach ($flat as $key => $value) {
            if (in_array($key, $columns, true)) {
                $attributes[$key] = $this->databaseValue($activity, $key, $value);
            }
        }

        $eventColumns = [
            'event',
            'event_name',
            'event_type',
            'activity',
            'activity_type',
            'journey_event',
            'last_event',
            'current_step',
        ];

        foreach ($eventColumns as $column) {
            if (in_array($column, $columns, true)) {
                $attributes[$column] = $event;
            }
        }

        if (in_array('status', $columns, true)) {
            $attributes['status'] = $this->eventStatus($event, $payload);
        }

        foreach (['uuid', 'activity_uuid'] as $column) {
            if (
                in_array($column, $columns, true)
                && blank($activity->getAttribute($column))
                && blank($attributes[$column] ?? null)
            ) {
                $attributes[$column] = (string) Str::uuid();
            }
        }

        $metadata = $this->metadata($payload, $event);

        foreach (['metadata', 'meta', 'payload', 'data', 'journey_payload'] as $column) {
            if (! in_array($column, $columns, true)) {
                continue;
            }

            $existing = $activity->getAttribute($column);
            $existing = is_array($existing) ? $existing : [];

            $attributes[$column] = $this->databaseValue(
                model: $activity,
                key: $column,
                value: array_replace_recursive($existing, $metadata)
            );
        }

        foreach ($this->eventTimestampColumns($event) as $column) {
            if (in_array($column, $columns, true)) {
                $attributes[$column] = now();
            }
        }

        if (in_array('last_activity_at', $columns, true)) {
            $attributes['last_activity_at'] = now();
        }

        return $attributes;
    }

    /**
     * Locate an existing activity using common request identifiers.
     */
    private function findActivityFromPayload(array $payload): ?CustomerSearchActivity
    {
        $columns = $this->columns();

        $id = Arr::first([
            data_get($payload, 'activity_id'),
            data_get($payload, 'search_activity_id'),
            data_get($payload, 'customer_search_activity_id'),
        ], static fn ($value) => filled($value));

        if ($id !== null) {
            $activity = CustomerSearchActivity::query()->find((int) $id);

            if ($activity) {
                return $activity;
            }
        }

        $identifiers = [
            'uuid' => data_get($payload, 'uuid'),
            'activity_uuid' => data_get($payload, 'activity_uuid')
                ?? data_get($payload, 'search_activity_uuid'),
            'session_id' => data_get($payload, 'session_id'),
            'booking_id' => data_get($payload, 'booking_id')
                ?? data_get($payload, 'booking_data.booking_id')
                ?? data_get($payload, 'booking_data.id'),
            'booking_no' => data_get($payload, 'booking_no')
                ?? data_get($payload, 'booking_data.booking_no')
                ?? data_get($payload, 'booking_data.booking_number'),
        ];

        foreach ($identifiers as $column => $value) {
            if (! filled($value) || ! in_array($column, $columns, true)) {
                continue;
            }

            $activity = CustomerSearchActivity::query()
                ->where($column, $value)
                ->latest('id')
                ->first();

            if ($activity) {
                return $activity;
            }
        }

        return null;
    }

    /**
     * Flatten known nested payload groups into normal activity columns.
     */
    private function flattenPayload(array $payload): array
    {
        $flat = $payload;

        foreach ([
            'search_data',
            'checkout_data',
            'payment_data',
            'booking_data',
        ] as $group) {
            $nested = data_get($payload, $group);

            if (is_array($nested)) {
                $flat = array_replace($flat, $nested);
            }
        }

        $aliases = [
            'customer_id' => $flat['customer_id'] ?? $flat['user_id'] ?? null,
            'user_id' => $flat['user_id'] ?? $flat['customer_id'] ?? null,
            'booking_id' => $flat['booking_id'] ?? $flat['id'] ?? null,
            'booking_no' => $flat['booking_no'] ?? $flat['booking_number'] ?? null,
            'amount' => $flat['amount']
                ?? $flat['paid_amount']
                ?? $flat['total_amount']
                ?? $flat['grand_total']
                ?? null,
            'quoted_amount' => $flat['quoted_amount']
                ?? $flat['estimated_amount']
                ?? $flat['grand_total']
                ?? null,
            'final_amount' => $flat['final_amount']
                ?? $flat['total_amount']
                ?? $flat['grand_total']
                ?? null,
            'failure_reason' => $flat['failure_reason']
                ?? $flat['reason']
                ?? $flat['error_message']
                ?? null,
            'cancellation_reason' => $flat['cancellation_reason']
                ?? $flat['reason']
                ?? null,
        ];

        return array_filter(
            array_replace($flat, $aliases),
            static fn ($value) => $value !== null
        );
    }

    private function metadata(array $payload, string $event): array
    {
        $existing = data_get($payload, 'metadata');
        $existing = is_array($existing) ? $existing : [];

        return array_replace_recursive($existing, [
            'last_event' => $event,
            'last_event_at' => now()->toIso8601String(),
            'events' => [
                $event => [
                    'recorded_at' => now()->toIso8601String(),
                    'payload' => Arr::except($payload, [
                        'password',
                        'token',
                        'payment_token',
                        'gateway_signature',
                    ]),
                ],
            ],
        ]);
    }

    private function eventStatus(string $event, array $payload): string
    {
        $providedStatus = data_get($payload, 'status')
            ?? data_get($payload, 'booking_status')
            ?? data_get($payload, 'booking_data.status');

        if (filled($providedStatus)) {
            return (string) $providedStatus;
        }

        return match ($event) {
            'search_performed' => 'searched',
            'checkout_started' => 'checkout_started',
            'payment_started' => 'payment_pending',
            'payment_succeeded' => 'payment_succeeded',
            'payment_failed' => 'payment_failed',
            'booking_created' => 'booking_created',
            'booking_completed' => 'completed',
            'booking_cancelled' => 'cancelled',
            default => $event,
        };
    }

    /**
     * Possible timestamp columns used by different installations.
     */
    private function eventTimestampColumns(string $event): array
    {
        return match ($event) {
            'search_performed' => ['searched_at', 'search_performed_at'],
            'checkout_started' => ['checkout_started_at'],
            'payment_started' => ['payment_started_at'],
            'payment_succeeded' => ['payment_succeeded_at', 'paid_at'],
            'payment_failed' => ['payment_failed_at'],
            'booking_created' => ['booking_created_at', 'booked_at'],
            'booking_completed' => ['booking_completed_at', 'completed_at'],
            'booking_cancelled' => ['booking_cancelled_at', 'cancelled_at'],
            default => [],
        };
    }

    /**
     * Respect model JSON/array casts; otherwise store arrays as JSON text.
     */
    private function databaseValue(
        CustomerSearchActivity $model,
        string $key,
        mixed $value
    ): mixed {
        if (! is_array($value)) {
            return $value;
        }

        $cast = $model->getCasts()[$key] ?? null;
        $arrayCasts = [
            'array',
            'json',
            'object',
            'collection',
            'encrypted:array',
            'encrypted:collection',
            'encrypted:json',
            'encrypted:object',
        ];

        if (is_string($cast) && in_array($cast, $arrayCasts, true)) {
            return $value;
        }

        return json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * @return array<int, string>
     */
    private function columns(): array
    {
        static $columns;

        if (is_array($columns)) {
            return $columns;
        }

        $table = (new CustomerSearchActivity())->getTable();

        if (! Schema::hasTable($table)) {
            return $columns = [];
        }

        return $columns = Schema::getColumnListing($table);
    }
}