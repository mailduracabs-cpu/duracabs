<?php

namespace App\Events;

use App\Models\CustomerSearchActivity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStarted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Customer search activity.
     */
    public CustomerSearchActivity $searchActivity;

    /**
     * Payment payload.
     */
    public array $paymentData;

    /**
     * Create a new event instance.
     */
    public function __construct(
        CustomerSearchActivity $searchActivity,
        array $paymentData = []
    ) {
        $this->searchActivity = $searchActivity;
        $this->paymentData = $paymentData;
    }

    /**
     * Helper accessor.
     */
    public function search(): CustomerSearchActivity
    {
        return $this->searchActivity;
    }

    /**
     * Helper accessor.
     */
    public function payment(): array
    {
        return $this->paymentData;
    }

    /**
     * Whether payment contains an amount.
     */
    public function hasAmount(): bool
    {
        return isset($this->paymentData['grand_total'])
            || isset($this->paymentData['amount']);
    }

    /**
     * Return payable amount.
     */
    public function amount(): ?float
    {
        $amount = $this->paymentData['grand_total']
            ?? $this->paymentData['amount']
            ?? null;

        return is_numeric($amount)
            ? (float) $amount
            : null;
    }

    /**
     * Return payment method.
     */
    public function paymentMethod(): ?string
    {
        return $this->paymentData['payment_method']
            ?? null;
    }

    /**
     * Return gateway name.
     */
    public function gateway(): ?string
    {
        return $this->paymentData['gateway']
            ?? null;
    }

    /**
     * Return currency.
     */
    public function currency(): string
    {
        return strtoupper(
            (string) (
                $this->paymentData['currency']
                ?? 'INR'
            )
        );
    }
}