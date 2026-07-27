<?php

namespace App\Services;

use App\Models\SelfDriveBooking;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SelfDriveBookingService
{
    private const BOOKING_LOCK_SECONDS = 60;
    private const ONLINE_PAYMENT_METHODS = [
        'online',
        'razorpay',
        'razorpay_payment',
        'card',
        'upi',
    ];

    public function __construct(
        private readonly NotificationManagerService $notificationManagerService,
        private readonly CustomerJourneyService $customerJourneyService,
        private readonly SelfDriveAvailabilityService $availabilityService,
        private readonly SelfDrivePricingService $pricingService,
    ) {
    }

    /**
     * Create a self-drive booking without changing the existing Flutter payload.
     */
    public function create(array $data, ?User $authenticatedUser = null): array
    {
        if (! Schema::hasTable('self_drive_bookings')) {
            return $this->failure('Self drive bookings table not found.', 500);
        }

        $customer = $this->resolveCustomer($data, $authenticatedUser);

        if (! $customer) {
            return $this->failure('Customer not found. Please login again.', 401);
        }

        $this->updateCustomerProfile($customer, $data);
        $customer->refresh();

        if (method_exists($customer, 'hasBasicDetails') && ! $customer->hasBasicDetails()) {
            $nextStep = method_exists($customer, 'nextRequiredStep')
                ? ($customer->nextRequiredStep() ?? 'complete_profile')
                : 'complete_profile';

            return $this->failure(
                'Please complete customer name and mobile number. Next step: ' . $nextStep,
                422
            );
        }

        try {
            [$start, $end] = $this->parseBookingDates($data);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage(), 422);
        }

        $vehicleId = (int) ($data['vehicle_id'] ?? 0);

        if ($vehicleId <= 0) {
            return $this->failure('Vehicle is required.', 422);
        }

        $lockKey = $this->bookingLockKey($customer->id, $vehicleId, $start, $end);

        if (! Cache::add($lockKey, true, now()->addSeconds(self::BOOKING_LOCK_SECONDS))) {
            return $this->failure('Duplicate booking request detected. Please wait.', 429);
        }

        try {
            $result = DB::transaction(function () use (
                $data,
                $customer,
                $vehicleId,
                $start,
                $end
            ): array {
                /** @var Vehicle|null $vehicle */
                $vehicle = Vehicle::query()
                    ->with(['transporter'])
                    ->when(
                        method_exists(Vehicle::class, 'scopeAvailableForCustomer'),
                        fn (Builder $query) => $query->availableForCustomer()
                    )
                    ->lockForUpdate()
                    ->find($vehicleId);

                if (! $vehicle) {
                    return $this->failure('Vehicle not available.', 404);
                }

                if (! $this->isVehicleBookable($vehicle)) {
                    return $this->failure('Vehicle is currently inactive or not approved.', 422);
                }

                $availability = $this->availabilityService->check(array_merge(
                    $data,
                    [
                        'vehicle_id' => $vehicle->id,
                        'start_datetime' => $start->toDateTimeString(),
                        'end_datetime' => $end->toDateTimeString(),
                    ]
                ));

                if (! ($availability['status'] ?? false)) {
                    return $availability;
                }

                $recent = $this->findRecentDuplicate(
                    customerId: $customer->id,
                    vehicleId: $vehicle->id,
                    start: $start,
                    end: $end
                );

                if ($recent) {
                    return [
                        'status' => true,
                        'message' => 'Booking already submitted.',
                        'code' => 200,
                        'data' => $this->formatBooking($recent),
                    ];
                }

                try {
                    $pricing = $this->pricingService->calculate($vehicle, $start, $end, $data);
                } catch (Throwable $exception) {
                    return $this->failure($exception->getMessage(), 422);
                }
                $payment = $this->calculatePayment($pricing['payable_amount'], $data);
                $bookingNo = $this->generateBookingNumber();
                $documentStatus = $this->resolveDocumentStatus($customer);

                $bookingData = [
                    'booking_no' => $bookingNo,
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicle->id,
                    'transporter_profile_id' => $vehicle->transporter_profile_id,
                    'pickup_location' => trim((string) ($data['pickup_location'] ?? '')),
                    'pickup_latitude' => $this->nullableFloat($data['pickup_latitude'] ?? null),
                    'pickup_longitude' => $this->nullableFloat($data['pickup_longitude'] ?? null),
                    'start_datetime' => $start,
                    'end_datetime' => $end,
                    'booked_hours' => $pricing['chargeable_hours'],
                    'hourly_price' => $pricing['hourly_price'],
                    'minimum_booking_hours' => $pricing['minimum_hours'],
                    'security_deposit' => $pricing['security_deposit'],
                    'total_days' => $pricing['total_days'],
                    'price_per_day' => $pricing['price_per_day'],
                    'total_amount' => $pricing['rent'],
                    'status' => SelfDriveBooking::STATUS_PENDING,
                    'booking_status' => 'pending_vendor_confirmation',
                    'vendor_confirmation_status' => 'pending',
                    'document_status' => $documentStatus,
                    'payment_type' => $payment['payment_type'],
                    'payment_status' => $payment['payment_status'],
                    'payment_method' => $payment['payment_method'],
                    'payment_reference' => $payment['payment_reference'],
                    'advance_amount' => $payment['advance_amount'],
                    'paid_amount' => $payment['paid_amount'],
                    'remaining_amount' => $payment['remaining_amount'],
                    'payment_completed_at' => $payment['payment_completed_at'],
                ];

                $bookingData = $this->withOptionalColumns($bookingData, [
                    'self_drive_vehicle_id' => $vehicle->id,
                    'customer_name' => $data['customer_name'] ?? $customer->name,
                    'customer_mobile' => $data['customer_mobile']
                        ?? $data['mobile']
                        ?? $customer->mobile,
                    'customer_email' => $data['customer_email']
                        ?? $data['email']
                        ?? $customer->email,
                    'free_km' => $data['free_km'] ?? $vehicle->free_km ?? 0,
                    'extra_hour_rate' => $data['extra_hour_rate']
                        ?? $vehicle->extra_hour_rate
                        ?? $pricing['hourly_price'],
                    'extra_km_rate' => $data['extra_km_rate']
                        ?? $vehicle->extra_km_rate
                        ?? 0,
                    'unlimited_kms' => $this->booleanValue(
                        $data['unlimited_kms']
                        ?? $data['unlimited_km_selected']
                        ?? false
                    ),
                    'unlimited_km_selected' => $this->booleanValue(
                        $data['unlimited_km_selected']
                        ?? $data['unlimited_kms']
                        ?? false
                    ),
                    'special_request_total' => $this->money(
                        $data['special_request_total'] ?? 0
                    ),
                    'extra_service_amount' => $this->money(
                        $data['extra_service_amount'] ?? 0
                    ),
                    'toll_amount' => $this->money($data['toll_amount'] ?? 0),
                    'parking_amount' => $this->money($data['parking_amount'] ?? 0),
                    'government_tax_amount' => $this->money(
                        $data['government_tax_amount'] ?? 0
                    ),
                    'permit_tax_amount' => $this->money(
                        $data['permit_tax_amount'] ?? 0
                    ),
                ]);

                /** @var SelfDriveBooking $booking */
                $booking = SelfDriveBooking::query()->create($bookingData);

                return [
                    'status' => true,
                    'message' => 'Booking request created successfully.',
                    'code' => 201,
                    'data' => array_merge($this->formatBooking($booking->fresh()), [
                        'selected_hours' => $pricing['selected_hours'],
                        'chargeable_hours' => $pricing['chargeable_hours'],
                        'estimated_rent' => $pricing['rent'],
                        'payable_amount' => $pricing['payable_amount'],
                        'customer' => method_exists($customer, 'customerProfileData')
                            ? $customer->customerProfileData()
                            : $customer->only(['id', 'name', 'mobile', 'email']),
                        'next_required_step' => $this->nextRequiredStep($customer),
                    ]),
                ];
            }, 3);

            if ($result['status'] ?? false) {
                $this->afterBookingCreated($result['data'] ?? [], $customer, $data);
            }

            return $result;
        } catch (Throwable $exception) {
            Log::error('Self drive booking creation failed.', [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicleId,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return $this->failure(
                'Unable to create self drive booking. Please try again.',
                500,
                $exception
            );
        } finally {
            Cache::forget($lockKey);
        }
    }

    public function checkAvailability(array $data): array
    {
        $availability = $this->availabilityService->check($data);
        $availabilityCode = (int) ($availability['code'] ?? 422);

        // Booking conflict is a valid availability response for existing
        // Flutter and website clients: request succeeded, vehicle is false.
        if (! ($availability['status'] ?? false) && $availabilityCode !== 409) {
            return $availability;
        }

        try {
            [$start, $end] = $this->parseBookingDates($data);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage(), 422);
        }

        $vehicleId = (int) ($data['vehicle_id'] ?? 0);
        $vehicle = Vehicle::query()->find($vehicleId);

        if (! $vehicle) {
            return $this->failure('Vehicle not available.', 404);
        }

        try {
            try {
                $pricing = $this->pricingService->calculate($vehicle, $start, $end, $data);
            } catch (Throwable $exception) {
                return $this->failure($exception->getMessage(), 422);
            }
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage(), 422);
        }

        $availabilityData = (array) ($availability['data'] ?? []);
        $available = (bool) ($availabilityData['available'] ?? false);

        return [
            'status' => true,
            'message' => (string) ($availability['message'] ?? (
                $available
                    ? 'Vehicle is available.'
                    : 'Vehicle is already booked for the selected time.'
            )),
            'code' => 200,
            'data' => array_merge($availabilityData, [
                'vehicle_id' => $vehicleId,
                'available' => $available,
                'start_datetime' => $start->toDateTimeString(),
                'end_datetime' => $end->toDateTimeString(),
                'selected_hours' => $pricing['selected_hours'],
                'chargeable_hours' => $pricing['chargeable_hours'],
                'hourly_price' => $pricing['hourly_price'],
                'estimated_rent' => $pricing['rent'],
                'security_deposit' => $pricing['security_deposit'],
                'payable_amount' => $pricing['payable_amount'],
            ]),
        ];
    }

    public function pendingBooking(array $data, ?User $authenticatedUser = null): array
    {
        $customer = $this->resolveCustomer($data, $authenticatedUser);

        $query = SelfDriveBooking::query()
            ->with(['vehicle', 'customer'])
            ->whereNotIn('booking_status', ['completed', 'cancelled', 'rejected']);

        if ($customer) {
            $query->where('customer_id', $customer->id);
        } elseif (! empty($data['mobile']) && Schema::hasColumn('self_drive_bookings', 'customer_mobile')) {
            $query->where('customer_mobile', $this->normalizeMobile($data['mobile']));
        } else {
            return $this->failure('Customer is required.', 401);
        }

        $booking = $query->latest('id')->first();

        return [
            'status' => true,
            'message' => 'Pending booking loaded.',
            'code' => 200,
            'data' => $booking ? $this->formatBooking($booking) : null,
        ];
    }

    public function details(int|string $bookingId, ?User $customer = null): array
    {
        $booking = $this->findBooking($bookingId);

        if (! $booking) {
            return $this->failure('Self drive booking not found.', 404);
        }

        if ($customer && (int) $booking->customer_id !== (int) $customer->id) {
            return $this->failure('You are not allowed to view this booking.', 403);
        }

        return [
            'status' => true,
            'message' => 'Self drive booking details loaded.',
            'code' => 200,
            'data' => $this->formatBooking($booking),
        ];
    }

    public function vendorConfirm(int|string $bookingId): array
    {
        try {
            $booking = DB::transaction(function () use ($bookingId): ?SelfDriveBooking {
                $booking = SelfDriveBooking::query()
                    ->lockForUpdate()
                    ->find($bookingId);

                if (! $booking) {
                    return null;
                }

                if (in_array($booking->vendor_confirmation_status, ['confirmed'], true)) {
                    return $booking;
                }

                if (in_array($booking->booking_status, ['cancelled', 'rejected', 'completed'], true)) {
                    throw new \RuntimeException('This booking cannot be confirmed.');
                }

                $customer = User::query()->find($booking->customer_id);
                $approved = $customer && method_exists($customer, 'isKycApproved')
                    ? $customer->isKycApproved()
                    : false;
                $complete = $customer && method_exists($customer, 'hasCompleteKyc')
                    ? $customer->hasCompleteKyc()
                    : false;

                $booking->forceFill([
                    'vendor_confirmation_status' => 'confirmed',
                    'vendor_confirmed_at' => now(),
                    'status' => $approved
                        ? SelfDriveBooking::STATUS_CONFIRMED
                        : SelfDriveBooking::STATUS_PENDING,
                    'booking_status' => $approved
                        ? 'confirmed'
                        : ($complete ? 'documents_under_verification' : 'documents_pending'),
                    'document_status' => $approved
                        ? 'approved'
                        : ($complete ? 'under_verification' : 'not_uploaded'),
                    'booking_confirmed_at' => $approved ? now() : null,
                ])->save();

                return $booking->fresh(['vehicle', 'customer']);
            }, 3);

            if (! $booking) {
                return $this->failure('Booking not found.', 404);
            }

            $this->notifyBookingEvent('self_drive_vendor_confirmed', $booking);

            return [
                'status' => true,
                'message' => 'Booking confirmed by vendor.',
                'code' => 200,
                'data' => $this->formatBooking($booking),
            ];
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage(), 422, $exception);
        }
    }

    public function vendorReject(int|string $bookingId, ?string $reason = null): array
    {
        try {
            $booking = DB::transaction(function () use ($bookingId, $reason): ?SelfDriveBooking {
                $booking = SelfDriveBooking::query()
                    ->lockForUpdate()
                    ->find($bookingId);

                if (! $booking) {
                    return null;
                }

                if (in_array($booking->booking_status, ['running', 'completed'], true)) {
                    throw new \RuntimeException('Running or completed booking cannot be rejected.');
                }

                $booking->forceFill([
                    'vendor_confirmation_status' => 'rejected',
                    'vendor_rejected_at' => now(),
                    'vendor_rejection_reason' => trim((string) ($reason ?: 'Rejected by vendor')),
                    'status' => SelfDriveBooking::STATUS_REJECTED,
                    'booking_status' => 'rejected',
                ])->save();

                return $booking->fresh(['vehicle', 'customer']);
            }, 3);

            if (! $booking) {
                return $this->failure('Booking not found.', 404);
            }

            $this->notifyBookingEvent('self_drive_vendor_rejected', $booking, [
                'reason' => $booking->vendor_rejection_reason,
            ]);

            return [
                'status' => true,
                'message' => 'Booking rejected by vendor.',
                'code' => 200,
                'data' => $this->formatBooking($booking),
            ];
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage(), 422, $exception);
        }
    }

    public function createRazorpayOrder(int|string $bookingId): array
    {
        $booking = $this->findBooking($bookingId);

        if (! $booking) {
            return $this->failure('Booking not found.', 404);
        }

        $amount = max(0, (float) $booking->remaining_amount);

        if ($amount <= 0) {
            return $this->failure('No pending amount for this booking.', 422);
        }

        $result = RazorpayService::createOrder(
            amount: $amount,
            receipt: $booking->booking_no,
            notes: [
                'booking_id' => (string) $booking->id,
                'booking_no' => (string) $booking->booking_no,
                'service_type' => 'self_drive',
            ]
        );

        if (! ($result['status'] ?? false)) {
            return $this->failure(
                $result['message'] ?? 'Unable to create payment order.',
                502
            );
        }

        $gatewayOrderId = data_get($result, 'data.id');

        $updates = [
            'payment_method' => 'razorpay',
            'payment_status' => 'pending',
        ];

        if (Schema::hasColumn('self_drive_bookings', 'razorpay_order_id')) {
            $updates['razorpay_order_id'] = $gatewayOrderId;
        } elseif ($gatewayOrderId) {
            $updates['payment_reference'] = $gatewayOrderId;
        }

        $booking->forceFill($updates)->save();

        return [
            'status' => true,
            'message' => 'Razorpay order created successfully.',
            'code' => 200,
            'data' => array_merge($result['data'] ?? [], [
                'booking_id' => $booking->id,
                'booking_no' => $booking->booking_no,
                'payable_amount' => $amount,
            ]),
        ];
    }

    public function verifyRazorpayPayment(int|string $bookingId, array $data): array
    {
        $booking = $this->findBooking($bookingId);

        if (! $booking) {
            return $this->failure('Booking not found.', 404);
        }

        $orderId = (string) ($data['razorpay_order_id'] ?? $data['order_id'] ?? '');
        $paymentId = (string) ($data['razorpay_payment_id'] ?? $data['payment_id'] ?? '');
        $signature = (string) ($data['razorpay_signature'] ?? $data['signature'] ?? '');

        if ($orderId === '' || $paymentId === '' || $signature === '') {
            return $this->failure('Incomplete Razorpay payment data.', 422);
        }

        $verification = RazorpayService::verifyPayment($orderId, $paymentId, $signature);

        if (! ($verification['status'] ?? false)) {
            $this->notifyBookingEvent('self_drive_payment_failed', $booking, [
                'reason' => $verification['message'] ?? 'Payment verification failed.',
            ]);

            return $this->failure(
                $verification['message'] ?? 'Payment verification failed.',
                422
            );
        }

        try {
            $booking = DB::transaction(function () use ($booking, $paymentId, $orderId): SelfDriveBooking {
                $locked = SelfDriveBooking::query()->lockForUpdate()->findOrFail($booking->id);
                $pendingAmount = max(0, (float) $locked->remaining_amount);
                $newPaid = round((float) $locked->paid_amount + $pendingAmount, 2);

                $updates = [
                    'paid_amount' => $newPaid,
                    'remaining_amount' => 0,
                    'payment_status' => 'paid',
                    'payment_method' => 'razorpay',
                    'payment_reference' => $paymentId,
                    'payment_completed_at' => now(),
                ];

                if (Schema::hasColumn('self_drive_bookings', 'razorpay_order_id')) {
                    $updates['razorpay_order_id'] = $orderId;
                }

                if (Schema::hasColumn('self_drive_bookings', 'razorpay_payment_id')) {
                    $updates['razorpay_payment_id'] = $paymentId;
                }

                $locked->forceFill($updates)->save();

                return $locked->fresh(['vehicle', 'customer']);
            }, 3);

            $this->notifyBookingEvent('self_drive_payment_succeeded', $booking);

            return [
                'status' => true,
                'message' => 'Payment verified successfully.',
                'code' => 200,
                'data' => $this->formatBooking($booking),
            ];
        } catch (Throwable $exception) {
            return $this->failure(
                'Payment was verified, but booking update failed. Please contact support.',
                500,
                $exception
            );
        }
    }

    public function cancel(
        int|string $bookingId,
        ?User $customer = null,
        ?string $reason = null
    ): array {
        try {
            $booking = DB::transaction(function () use ($bookingId, $customer, $reason): ?SelfDriveBooking {
                $booking = SelfDriveBooking::query()->lockForUpdate()->find($bookingId);

                if (! $booking) {
                    return null;
                }

                if ($customer && (int) $booking->customer_id !== (int) $customer->id) {
                    throw new \RuntimeException('You are not allowed to cancel this booking.');
                }

                if (in_array($booking->booking_status, ['running', 'completed', 'cancelled'], true)) {
                    throw new \RuntimeException('This booking cannot be cancelled.');
                }

                $updates = [
                    'status' => SelfDriveBooking::STATUS_CANCELLED,
                    'booking_status' => 'cancelled',
                ];

                if (Schema::hasColumn('self_drive_bookings', 'cancellation_reason')) {
                    $updates['cancellation_reason'] = trim((string) ($reason ?: 'Cancelled by customer'));
                }

                if (Schema::hasColumn('self_drive_bookings', 'cancelled_at')) {
                    $updates['cancelled_at'] = now();
                }

                $booking->forceFill($updates)->save();

                return $booking->fresh(['vehicle', 'customer']);
            }, 3);

            if (! $booking) {
                return $this->failure('Booking not found.', 404);
            }

            $this->notifyBookingEvent('self_drive_booking_cancelled', $booking, [
                'reason' => $reason,
            ]);

            return [
                'status' => true,
                'message' => 'Booking cancelled successfully.',
                'code' => 200,
                'data' => $this->formatBooking($booking),
            ];
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage(), 422, $exception);
        }
    }

    /**
     * Send a lifecycle notification for controller-managed self-drive events.
     * Notification failure is logged internally and never breaks the API flow.
     */
    public function notifyLifecycleEvent(
        int|string $bookingId,
        string $event,
        array $payload = []
    ): void {
        $booking = $this->findBooking($bookingId);

        if (! $booking) {
            Log::warning('Self drive lifecycle notification skipped: booking not found.', [
                'booking_id' => $bookingId,
                'event' => $event,
            ]);

            return;
        }

        $this->notifyBookingEvent($event, $booking, $payload);
    }

    public function isAvailable(
        int $vehicleId,
        Carbon $start,
        Carbon $end,
        ?int $ignoreBookingId = null
    ): bool {
        return $this->availabilityService->isAvailable(
            vehicleId: $vehicleId,
            start: $start,
            end: $end,
            excludeBookingId: $ignoreBookingId
        );
    }

    public function findBooking(int|string $bookingId): ?SelfDriveBooking
    {
        if ($bookingId === '' || $bookingId === null) {
            return null;
        }

        return SelfDriveBooking::query()
            ->with(['vehicle', 'customer', 'transporter'])
            ->where(function (Builder $query) use ($bookingId): void {
                if (is_numeric($bookingId)) {
                    $query->whereKey((int) $bookingId);
                }

                $query->orWhere('booking_no', (string) $bookingId);
            })
            ->first();
    }

    private function parseBookingDates(array $data): array
    {
        $startDate = trim((string) ($data['start_date'] ?? ''));
        $startTime = trim((string) ($data['start_time'] ?? ''));
        $endDate = trim((string) ($data['end_date'] ?? ''));
        $endTime = trim((string) ($data['end_time'] ?? ''));

        if ($startDate === '' || $startTime === '' || $endDate === '' || $endTime === '') {
            throw new \InvalidArgumentException('Start and end date/time are required.');
        }

        $start = Carbon::parse($startDate . ' ' . $startTime);
        $end = Carbon::parse($endDate . ' ' . $endTime);

        if ($end->lessThanOrEqualTo($start)) {
            throw new \InvalidArgumentException('End date and time must be after start date and time.');
        }

        if ($start->lessThan(now()->subMinutes(5))) {
            throw new \InvalidArgumentException('Start date and time cannot be in the past.');
        }

        return [$start, $end];
    }

    private function calculatePayment(float $payableAmount, array $data): array
    {
        $paymentType = strtolower(trim((string) ($data['payment_type'] ?? 'advance')));
        $paymentType = in_array($paymentType, ['advance', 'full'], true)
            ? $paymentType
            : 'advance';

        $method = strtolower(trim((string) ($data['payment_method'] ?? 'cash')));
        $requestedPaid = $this->money($data['paid_amount'] ?? 0);
        $paid = min($requestedPaid, $payableAmount);
        $remaining = max(0, round($payableAmount - $paid, 2));

        return [
            'payment_type' => $paymentType,
            'payment_method' => $method,
            'payment_reference' => $data['payment_reference'] ?? null,
            'payment_status' => $remaining <= 0 && $paid > 0
                ? 'paid'
                : ($paid > 0 ? 'partial' : 'pending'),
            'advance_amount' => $paymentType === 'advance' ? $paid : 0,
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'payment_completed_at' => $remaining <= 0 && $paid > 0 ? now() : null,
        ];
    }

    private function resolveCustomer(array $data, ?User $authenticatedUser = null): ?User
    {
        if ($authenticatedUser) {
            return $authenticatedUser;
        }

        if (! empty($data['customer_id'])) {
            return User::query()->find((int) $data['customer_id']);
        }

        $mobile = $this->normalizeMobile(
            $data['customer_mobile'] ?? $data['mobile'] ?? null
        );

        if ($mobile !== '') {
            return User::query()->where('mobile', $mobile)->first();
        }

        return null;
    }

    private function updateCustomerProfile(User $customer, array $data): void
    {
        $updates = array_filter([
            'name' => $data['customer_name'] ?? $data['name'] ?? null,
            'mobile' => $this->normalizeMobile(
                $data['customer_mobile'] ?? $data['mobile'] ?? null
            ) ?: null,
            'email' => $data['customer_email'] ?? $data['email'] ?? null,
        ], static fn ($value) => $value !== null && trim((string) $value) !== '');

        if ($updates !== []) {
            $customer->fill($updates)->save();
        }
    }

    private function resolveDocumentStatus(User $customer): string
    {
        if (method_exists($customer, 'isKycApproved') && $customer->isKycApproved()) {
            return 'approved';
        }

        if (method_exists($customer, 'hasCompleteKyc') && $customer->hasCompleteKyc()) {
            return 'under_verification';
        }

        return 'not_uploaded';
    }

    private function nextRequiredStep(User $customer): ?string
    {
        if (method_exists($customer, 'isKycApproved') && $customer->isKycApproved()) {
            return null;
        }

        return method_exists($customer, 'nextRequiredStep')
            ? $customer->nextRequiredStep()
            : 'complete_kyc';
    }

    private function isVehicleBookable(Vehicle $vehicle): bool
    {
        if (isset($vehicle->is_active) && ! (bool) $vehicle->is_active) {
            return false;
        }

        $status = strtolower(trim((string) ($vehicle->verification_status ?? 'approved')));

        return ! in_array($status, ['rejected', 'blocked', 'suspended', 'inactive'], true);
    }

    private function findRecentDuplicate(
        int $customerId,
        int $vehicleId,
        Carbon $start,
        Carbon $end
    ): ?SelfDriveBooking {
        return SelfDriveBooking::query()
            ->where('customer_id', $customerId)
            ->where('vehicle_id', $vehicleId)
            ->where('start_datetime', $start)
            ->where('end_datetime', $end)
            ->where('created_at', '>=', now()->subSeconds(self::BOOKING_LOCK_SECONDS))
            ->latest('id')
            ->first();
    }

    private function bookingLockKey(
        int $customerId,
        int $vehicleId,
        Carbon $start,
        Carbon $end
    ): string {
        return 'self_drive_booking_lock:' . hash('sha256', implode('|', [
            $customerId,
            $vehicleId,
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
        ]));
    }

    private function generateBookingNumber(): string
    {
        do {
            $bookingNo = 'SD' . now()->format('ymdHis') . strtoupper(Str::random(4));
        } while (SelfDriveBooking::query()->where('booking_no', $bookingNo)->exists());

        return $bookingNo;
    }

    private function withOptionalColumns(array $base, array $optional): array
    {
        foreach ($optional as $column => $value) {
            if (Schema::hasColumn('self_drive_bookings', $column)) {
                $base[$column] = $value;
            }
        }

        return $base;
    }

    private function formatBooking(SelfDriveBooking $booking): array
    {
        $booking->loadMissing(['vehicle', 'customer']);
        $vehicle = $booking->vehicle;
        $customer = $booking->customer;
        $vehicleName = trim(
            (string) ($vehicle?->car_company_name ?? '') . ' ' .
            (string) ($vehicle?->model_name ?? '')
        );

        $data = [
            'booking_id' => $booking->id,
            'id' => $booking->id,
            'booking_no' => $booking->booking_no,
            'customer_id' => $booking->customer_id,
            'vehicle_id' => $booking->vehicle_id,
            'transporter_profile_id' => $booking->transporter_profile_id,
            'vehicle_name' => $vehicleName !== '' ? $vehicleName : 'Self Drive Car',
            'vehicle_image' => $vehicle?->front_image_url ?? $vehicle?->front_image,
            'pickup_location' => $booking->pickup_location,
            'pickup_latitude' => $booking->pickup_latitude,
            'pickup_longitude' => $booking->pickup_longitude,
            'start_datetime' => optional($booking->start_datetime)->toDateTimeString(),
            'end_datetime' => optional($booking->end_datetime)->toDateTimeString(),
            'booked_hours' => (int) $booking->booked_hours,
            'minimum_booking_hours' => (int) $booking->minimum_booking_hours,
            'hourly_price' => (float) $booking->hourly_price,
            'total_days' => (int) $booking->total_days,
            'price_per_day' => (float) $booking->price_per_day,
            'total_amount' => (float) $booking->total_amount,
            'security_deposit' => (float) $booking->security_deposit,
            'payable_amount' => round(
                (float) $booking->total_amount + (float) $booking->security_deposit,
                2
            ),
            'advance_amount' => (float) $booking->advance_amount,
            'paid_amount' => (float) $booking->paid_amount,
            'remaining_amount' => (float) $booking->remaining_amount,
            'payment_type' => $booking->payment_type,
            'payment_method' => $booking->payment_method,
            'payment_status' => $booking->payment_status,
            'payment_reference' => $booking->payment_reference,
            'status' => $booking->status,
            'booking_status' => $booking->booking_status,
            'vendor_confirmation_status' => $booking->vendor_confirmation_status,
            'document_status' => $booking->document_status,
            'created_at' => optional($booking->created_at)->toDateTimeString(),
            'updated_at' => optional($booking->updated_at)->toDateTimeString(),
            'customer' => $customer
                ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'mobile' => $customer->mobile,
                    'email' => $customer->email,
                ]
                : null,
        ];

        if (! empty($booking->pickup_otp_verified_at)) {
            $data['vehicle_number'] = $vehicle?->vehicle_number;
        }

        foreach ([
            'actual_hours',
            'actual_km',
            'extra_hours',
            'extra_km',
            'extra_hour_amount',
            'extra_km_amount',
            'damage_amount',
            'fuel_charge',
            'cleaning_charge',
            'late_return_charge',
            'other_charge',
            'final_amount',
            'refund_amount',
            'balance_due',
            'settlement_status',
            'refund_status',
            'refund_reference',
        ] as $field) {
            if ($booking->getAttribute($field) !== null) {
                $data[$field] = is_numeric($booking->getAttribute($field))
                    ? (float) $booking->getAttribute($field)
                    : $booking->getAttribute($field);
            }
        }

        return $data;
    }

    private function afterBookingCreated(array $bookingData, User $customer, array $requestData): void
    {
        try {
            $this->recordJourney('bookingCreated', $bookingData, $requestData);
        } catch (Throwable $exception) {
            Log::warning('Self drive journey tracking failed.', [
                'booking_id' => $bookingData['booking_id'] ?? null,
                'message' => $exception->getMessage(),
            ]);
        }

        $booking = isset($bookingData['booking_id'])
            ? $this->findBooking((int) $bookingData['booking_id'])
            : null;

        if ($booking) {
            $this->notifyBookingEvent('self_drive_booking_created', $booking, [], $customer);
        }
    }

    private function recordJourney(string $method, array $bookingData, array $requestData): void
    {
        if (! method_exists($this->customerJourneyService, $method)) {
            return;
        }

        $payload = [
            'booking_id' => $bookingData['booking_id'] ?? null,
            'booking_no' => $bookingData['booking_no'] ?? null,
            'service_type' => 'self_drive',
            'status' => $bookingData['booking_status'] ?? 'pending_vendor_confirmation',
            'total_amount' => $bookingData['payable_amount'] ?? 0,
            'paid_amount' => $bookingData['paid_amount'] ?? 0,
            'metadata' => [
                'source' => $requestData['source'] ?? 'flutter_app',
                'vehicle_id' => $bookingData['vehicle_id'] ?? null,
            ],
        ];

        try {
            $this->customerJourneyService->{$method}($payload);
        } catch (\ArgumentCountError|\TypeError) {
            // Existing CustomerJourneyService installations can have different
            // method signatures. Booking creation must never fail because of CRM.
        }
    }

    private function notifyBookingEvent(
        string $event,
        SelfDriveBooking $booking,
        array $extraPayload = [],
        ?User $knownCustomer = null
    ): void {
        try {
            $customer = $knownCustomer ?: $booking->customer ?: User::query()->find($booking->customer_id);
            $channels = [];
            $recipients = [];

            if ($customer) {
                $channels[] = 'database';
                $recipients['database'] = $customer;
            }

            $mobile = $this->normalizeMobile($customer?->mobile);
            if ($mobile !== '') {
                $channels[] = 'whatsapp';
                $recipients['whatsapp'] = $mobile;
            }

            if (filled($customer?->email)) {
                $channels[] = 'email';
                $recipients['email'] = $customer->email;
            }

            $deviceToken = $this->resolveDeviceToken($customer);
            if ($deviceToken) {
                $channels[] = 'push';
                $recipients['push'] = $deviceToken;
            }

            if ($channels === []) {
                return;
            }

            [$subject, $message] = $this->notificationContent($event, $booking, $extraPayload);

            $this->notificationManagerService->send(
                event: $event,
                channels: array_values(array_unique($channels)),
                recipients: $recipients,
                message: $message,
                subject: $subject,
                payload: array_merge([
                    'type' => $event,
                    'service_type' => 'self_drive',
                    'booking_id' => $booking->id,
                    'booking_no' => $booking->booking_no,
                    'booking_status' => $booking->booking_status,
                    'vehicle_id' => $booking->vehicle_id,
                    'start_datetime' => optional($booking->start_datetime)->toDateTimeString(),
                    'end_datetime' => optional($booking->end_datetime)->toDateTimeString(),
                ], $extraPayload),
                notifiable: $customer
            );
        } catch (Throwable $exception) {
            Log::error('Self drive booking notification failed.', [
                'event' => $event,
                'booking_id' => $booking->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function notificationContent(
        string $event,
        SelfDriveBooking $booking,
        array $payload
    ): array {
        $bookingNo = (string) $booking->booking_no;
        $reason = trim((string) ($payload['reason'] ?? ''));
        $otp = trim((string) ($payload['otp'] ?? ''));
        $amount = number_format((float) ($payload['amount'] ?? 0), 2, '.', '');

        return match ($event) {
            'self_drive_booking_created' => [
                'Self Drive Booking Received',
                "Your self drive booking {$bookingNo} has been received and is awaiting vehicle partner confirmation.",
            ],
            'self_drive_vendor_confirmed' => [
                'Self Drive Booking Confirmed',
                "Your booking {$bookingNo} has been confirmed by the vehicle partner.",
            ],
            'self_drive_vendor_rejected' => [
                'Self Drive Booking Update',
                "Your booking {$bookingNo} was not accepted. Reason: " . ($reason !== '' ? $reason : 'Vehicle unavailable'),
            ],
            'self_drive_payment_succeeded' => [
                'Payment Successful',
                "Payment for self drive booking {$bookingNo} was successful.",
            ],
            'self_drive_payment_failed' => [
                'Payment Failed',
                "Payment for self drive booking {$bookingNo} failed. " . ($reason !== '' ? "Reason: {$reason}" : 'Please try again.'),
            ],
            'self_drive_kyc_approved' => [
                'Documents Approved',
                "Your documents for booking {$bookingNo} have been approved.",
            ],
            'self_drive_kyc_rejected' => [
                'Documents Rejected',
                "Your documents for booking {$bookingNo} were rejected. " . ($reason !== '' ? "Reason: {$reason}" : 'Please upload valid documents.'),
            ],
            'self_drive_pickup_otp_generated' => [
                'Pickup OTP',
                $otp !== ''
                    ? "Your pickup OTP for booking {$bookingNo} is {$otp}. It is valid for 30 minutes. Do not share it before vehicle handover."
                    : "Pickup OTP has been generated for booking {$bookingNo}.",
            ],
            'self_drive_pickup_otp_verified' => [
                'Pickup OTP Verified',
                "Pickup OTP for booking {$bookingNo} was verified successfully. Complete the pickup inspection to start your trip.",
            ],
            'self_drive_trip_started' => [
                'Trip Started',
                "Your self drive trip for booking {$bookingNo} has started.",
            ],
            'self_drive_return_otp_generated' => [
                'Return OTP',
                $otp !== ''
                    ? "Your return OTP for booking {$bookingNo} is {$otp}. It is valid for 30 minutes."
                    : "Return OTP has been generated for booking {$bookingNo}.",
            ],
            'self_drive_return_otp_verified' => [
                'Return OTP Verified',
                "Return OTP for booking {$bookingNo} was verified. Please complete the return inspection.",
            ],
            'self_drive_final_bill_generated' => [
                'Final Bill Generated',
                "Final bill for booking {$bookingNo} has been generated. Final amount: ₹{$amount}.",
            ],
            'self_drive_refund_pending' => [
                'Refund Initiated',
                "A refund of ₹{$amount} is pending for booking {$bookingNo}.",
            ],
            'self_drive_refund_completed' => [
                'Refund Completed',
                "Your refund of ₹{$amount} for booking {$bookingNo} has been completed.",
            ],
            'self_drive_booking_cancelled' => [
                'Booking Cancelled',
                "Your self drive booking {$bookingNo} has been cancelled." . ($reason !== '' ? " Reason: {$reason}" : ''),
            ],
            default => [
                'Self Drive Booking Update',
                "There is an update for your self drive booking {$bookingNo}.",
            ],
        };
    }

    private function resolveDeviceToken(?User $customer): ?string
    {
        if (! $customer) {
            return null;
        }

        foreach (['device_token', 'fcm_token', 'firebase_token'] as $field) {
            $value = trim((string) ($customer->{$field} ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeMobile(mixed $mobile): string
    {
        $normalized = preg_replace('/\D+/', '', (string) $mobile) ?: '';

        if (strlen($normalized) === 12 && str_starts_with($normalized, '91')) {
            $normalized = substr($normalized, 2);
        }

        return $normalized;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private function money(mixed $value): float
    {
        return round(max(0, (float) $value), 2);
    }

    private function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), [
            '1',
            'true',
            'yes',
            'on',
            'included',
        ], true);
    }

    private function failure(
        string $message,
        int $code = 422,
        ?Throwable $exception = null
    ): array {
        if ($exception) {
            Log::error('SelfDriveBookingService failure.', [
                'message' => $message,
                'exception' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }

        return [
            'status' => false,
            'message' => $message,
            'code' => $code,
            'errors' => $exception && config('app.debug')
                ? $exception->getMessage()
                : null,
        ];
    }
}