<?php

namespace App\Services;

/** Read-only customer presentation of persisted booking facts. */
final class CustomerBookingSummary
{
    public static function fromAttributes(array $attributes): array
    {
        $settlement = [];
        foreach (['security_deposit', 'extra_km_amount', 'extra_hour_amount',
            'fuel_charge', 'damage_amount', 'cleaning_charge', 'late_return_charge',
            'other_charge', 'final_amount', 'paid_amount', 'balance_due', 'refund_amount'] as $key) {
            if (isset($attributes[$key]) && is_numeric($attributes[$key])) {
                $settlement[$key] = round((float) $attributes[$key], 2);
            }
        }
        foreach (['settlement_status', 'refund_status', 'refund_reference',
            'refund_initiated_at', 'refunded_at', 'final_bill_generated_at'] as $key) {
            if (isset($attributes[$key]) && is_scalar($attributes[$key])) {
                $settlement[$key] = (string) $attributes[$key];
            }
        }

        $events = [];
        foreach ([
            'payment_completed_at' => 'Payment recorded',
            'vendor_confirmed_at' => 'Vendor confirmed',
            'booking_confirmed_at' => 'Booking confirmed',
            'documents_uploaded_at' => 'Documents uploaded',
            'documents_verified_at' => 'Documents verified',
            'documents_rejected_at' => 'Documents need correction',
            'trip_start_datetime' => 'Trip started',
            'trip_end_datetime' => 'Vehicle returned',
            'final_bill_generated_at' => 'Final bill generated',
            'completed_at' => 'Booking completed',
            'refunded_at' => 'Refund recorded',
        ] as $key => $title) {
            // No dates are inferred from the planned pickup/end date or payment status.
            $timestamp = $attributes[$key] ?? null;
            if (! is_string($timestamp) || trim($timestamp) === '') {
                continue;
            }
            $events[] = ['title' => $title, 'created_at' => $timestamp];
        }
        usort($events, static fn (array $a, array $b) => strcmp($a['created_at'], $b['created_at']));

        return ['settlement' => $settlement, 'timeline' => $events];
    }
}
