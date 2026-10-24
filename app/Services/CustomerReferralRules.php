<?php
namespace App\Services;
final class CustomerReferralRules
{
    public static function bookingState(object $booking): string
    {
        $meta = $booking->extraOptions ?? [];
        if (is_string($meta)) $meta = json_decode($meta, true) ?: [];
        $data = array_replace(is_array($meta) ? $meta : [], (array) $booking);
        $statuses = array_map(fn ($key) => strtolower(trim((string) ($data[$key] ?? ''))), ['status', 'booking_status']);
        if (array_intersect($statuses, ['cancelled', 'canceled', 'rejected', 'failed', 'refunded'])
            || (float) ($data['refund_amount'] ?? 0) > 0
            || in_array(strtolower((string) ($data['refund_status'] ?? '')), ['partial', 'refunded', 'completed', 'processed', 'success'], true)) return 'reverse';
        $total = (float) ($data['grand_total'] ?? $data['total_amount'] ?? $data['payable_amount'] ?? 0);
        $paid = (float) ($data['paid_amount'] ?? ($meta['reservation']['paid_amount'] ?? 0));
        $payment = strtolower((string) ($data['payment_status'] ?? ''));
        if ($total > 0 && $paid + 0.001 >= $total && $payment === 'paid'
            && array_intersect($statuses, ['completed', 'complete', 'finished'])) return 'credit';
        return 'pending';
    }
}
