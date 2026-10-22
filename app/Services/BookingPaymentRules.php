<?php

namespace App\Services;

use RuntimeException;

final class BookingPaymentRules
{
    public static function received(array $payment, string $orderId, string $paymentId, float $expected): float
    {
        if (($payment['id'] ?? '') !== $paymentId
            || ($payment['order_id'] ?? '') !== $orderId
            || ($payment['status'] ?? '') !== 'captured'
            || ($payment['captured'] ?? false) !== true
            || strtoupper((string) ($payment['currency'] ?? '')) !== 'INR'
            || (int) ($payment['amount_refunded'] ?? 0) !== 0
            || $expected <= 0
            || (int) ($payment['amount'] ?? 0) !== (int) round($expected * 100)) {
            throw new RuntimeException('Payment is not captured or does not match this checkout. No booking created.');
        }

        return round(((int) $payment['amount']) / 100, 2);
    }
}
