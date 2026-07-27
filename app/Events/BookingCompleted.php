<?php

namespace App\Events;

use App\Models\CustomerSearchActivity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCompleted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public CustomerSearchActivity $searchActivity,
        public array $bookingData = []
    ) {
    }

    /**
     * Search activity.
     */
    public function search(): CustomerSearchActivity
    {
        return $this->searchActivity;
    }

    /**
     * Booking payload.
     */
    public function booking(): array
    {
        return $this->bookingData;
    }

    /**
     * Booking id.
     */
    public function bookingId(): int|string|null
    {
        return $this->bookingData['booking_id']
            ?? $this->bookingData['id']
            ?? null;
    }

    /**
     * Booking number.
     */
    public function bookingNumber(): ?string
    {
        return $this->bookingData['booking_no']
            ?? $this->bookingData['booking_number']
            ?? null;
    }

    /**
     * Booking status.
     */
    public function status(): ?string
    {
        return $this->bookingData['status']
            ?? null;
    }

    /**
     * Service type.
     */
    public function serviceType(): ?string
    {
        return $this->bookingData['service_type']
            ?? null;
    }

    /**
     * Total amount.
     */
    public function totalAmount(): ?float
    {
        $amount = $this->bookingData['total_amount']
            ?? $this->bookingData['grand_total']
            ?? null;

        return is_numeric($amount)
            ? (float) $amount
            : null;
    }

    /**
     * Paid amount.
     */
    public function paidAmount(): ?float
    {
        $amount = $this->bookingData['paid_amount']
            ?? null;

        return is_numeric($amount)
            ? (float) $amount
            : null;
    }

    /**
     * Driver id.
     */
    public function driverId(): ?int
    {
        $driver = $this->bookingData['driver_id']
            ?? null;

        return is_numeric($driver)
            ? (int) $driver
            : null;
    }

    /**
     * Vehicle id.
     */
    public function vehicleId(): ?int
    {
        $vehicle = $this->bookingData['vehicle_id']
            ?? null;

        return is_numeric($vehicle)
            ? (int) $vehicle
            : null;
    }

    /**
     * Booking completed?
     */
    public function isCompleted(): bool
    {
        return strtolower(
            (string) ($this->bookingData['status'] ?? '')
        ) === 'completed';
    }
}