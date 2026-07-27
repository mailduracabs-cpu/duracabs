<?php

namespace App\Events;

use App\Models\CustomerSearchActivity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCancelled
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public CustomerSearchActivity $searchActivity,
        public array $bookingData = [],
        public ?string $reason = null
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
     * Cancellation reason.
     */
    public function cancellationReason(): ?string
    {
        return $this->reason;
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
     * Cancellation type.
     */
    public function cancellationType(): ?string
    {
        return $this->bookingData['cancellation_type']
            ?? null;
    }

    /**
     * Refund amount.
     */
    public function refundAmount(): ?float
    {
        $amount = $this->bookingData['refund_amount']
            ?? null;

        return is_numeric($amount)
            ? (float) $amount
            : null;
    }

    /**
     * Refund status.
     */
    public function refundStatus(): ?string
    {
        return $this->bookingData['refund_status']
            ?? null;
    }

    /**
     * Cancelled by.
     */
    public function cancelledBy(): ?string
    {
        return $this->bookingData['cancelled_by']
            ?? null;
    }

    /**
     * Cancellation charges.
     */
    public function cancellationCharge(): ?float
    {
        $charge = $this->bookingData['cancellation_charge']
            ?? null;

        return is_numeric($charge)
            ? (float) $charge
            : null;
    }

    /**
     * Whether refund is applicable.
     */
    public function hasRefund(): bool
    {
        return $this->refundAmount() !== null
            && $this->refundAmount() > 0;
    }
}