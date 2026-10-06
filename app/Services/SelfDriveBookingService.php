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