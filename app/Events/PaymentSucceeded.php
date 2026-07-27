<?php

namespace App\Events;

use App\Models\CustomerSearchActivity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSucceeded
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public CustomerSearchActivity $searchActivity,
        public array $paymentData = [],
        public array $bookingData = []
    ) {
    }

    /**
     * Return refreshed search activity.
     */
    public function search(): CustomerSearchActivity
    {
        return $this->searchActivity;
    }

    /**
     * Return payment payload.
     */
    public function payment(): array
    {
        return $this->paymentData;
    }

    /**
     * Return booking payload.
     */
    public function booking(): array
    {
        return $this->bookingData;
    }

    /**
     * Return paid amount.
     */
    public function amount(): ?float
    {
        $amount = $this->paymentData['paid_amount']
            ?? $this->paymentData['amount']
            ?? $this->paymentData['grand_total']
            ?? null;

        return is_numeric($amount)
            ? (float) $amount
            : null;
    }

    /**
     * Return transaction identifier.
     */
    public function transactionId(): ?string
    {
        $transactionId = $this->paymentData['transaction_id']
            ?? $this->paymentData['gateway_payment_id']
            ?? null;

        return $transactionId !== null
            ? trim((string) $transactionId)
            : null;
    }

    /**
     * Return gateway order identifier.
     */
    public function gatewayOrderId(): ?string
    {
        $orderId = $this->paymentData['gateway_order_id']
            ?? $this->paymentData['order_id']
            ?? null;

        return $orderId !== null
            ? trim((string) $orderId)
            : null;
    }

    /**
     * Return gateway name.
     */
    public function gateway(): ?string
    {
        $gateway = $this->paymentData['gateway'] ?? null;

        return $gateway !== null
            ? trim((string) $gateway)
            : null;
    }

    /**
     * Return payment method.
     */
    public function paymentMethod(): ?string
    {
        $paymentMethod = $this->paymentData['payment_method'] ?? null;

        return $paymentMethod !== null
            ? trim((string) $paymentMethod)
            : null;
    }

    /**
     * Return payment currency.
     */
    public function currency(): string
    {
        return strtoupper(
            trim((string) ($this->paymentData['currency'] ?? 'INR'))
        );
    }

    /**
     * Return booking identifier.
     */
    public function bookingId(): int|string|null
    {
        return $this->bookingData['booking_id']
            ?? $this->bookingData['id']
            ?? null;
    }

    /**
     * Return booking number.
     */
    public function bookingNumber(): ?string
    {
        $bookingNumber = $this->bookingData['booking_no']
            ?? $this->bookingData['booking_number']
            ?? null;

        return $bookingNumber !== null
            ? trim((string) $bookingNumber)
            : null;
    }
}