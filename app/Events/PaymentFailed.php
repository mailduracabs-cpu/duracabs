<?php

namespace App\Events;

use App\Models\CustomerSearchActivity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public CustomerSearchActivity $searchActivity,
        public array $paymentData = [],
        public ?string $reason = null
    ) {
    }

    /**
     * Return search activity.
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
     * Failure reason.
     */
    public function failureReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Transaction id.
     */
    public function transactionId(): ?string
    {
        return $this->paymentData['transaction_id']
            ?? $this->paymentData['gateway_payment_id']
            ?? null;
    }

    /**
     * Gateway order id.
     */
    public function gatewayOrderId(): ?string
    {
        return $this->paymentData['gateway_order_id']
            ?? $this->paymentData['order_id']
            ?? null;
    }

    /**
     * Gateway.
     */
    public function gateway(): ?string
    {
        return $this->paymentData['gateway']
            ?? null;
    }

    /**
     * Payment method.
     */
    public function paymentMethod(): ?string
    {
        return $this->paymentData['payment_method']
            ?? null;
    }

    /**
     * Failed amount.
     */
    public function amount(): ?float
    {
        $amount = $this->paymentData['amount']
            ?? $this->paymentData['grand_total']
            ?? null;

        return is_numeric($amount)
            ? (float) $amount
            : null;
    }

    /**
     * Currency.
     */
    public function currency(): string
    {
        return strtoupper(
            (string) ($this->paymentData['currency'] ?? 'INR')
        );
    }

    /**
     * Whether retry is allowed.
     */
    public function canRetry(): bool
    {
        return (bool) (
            $this->paymentData['can_retry']
            ?? true
        );
    }
}