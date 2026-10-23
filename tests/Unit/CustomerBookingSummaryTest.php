<?php

namespace Tests\Unit;

use App\Services\CustomerBookingSummary;
use PHPUnit\Framework\TestCase;

class CustomerBookingSummaryTest extends TestCase
{
    public function test_planned_dates_and_paid_status_do_not_fabricate_trip_progress(): void
    {
        $summary = CustomerBookingSummary::fromAttributes([
            'payment_status' => 'paid', 'start_datetime' => '2026-10-20 10:00:00',
            'end_datetime' => '2026-10-21 10:00:00', 'security_deposit' => '5000.00',
            'security_refund_ledger' => [['private_note' => 'not a public field']],
        ]);
        self::assertSame([], $summary['timeline']);
        self::assertArrayNotHasKey('refund_amount', $summary['settlement']);
        self::assertArrayNotHasKey('security_refund_ledger', $summary['settlement']);
    }

    public function test_only_real_events_and_stored_money_are_presented(): void
    {
        $summary = CustomerBookingSummary::fromAttributes([
            'completed_at' => '2026-10-21 12:00:00',
            'payment_completed_at' => '2026-10-20 09:00:00',
            'paid_amount' => '5500.00', 'refund_amount' => '500.00',
            'balance_due' => '0', 'refund_reference' => 'REF123',
        ]);
        self::assertSame('Payment recorded', $summary['timeline'][0]['title']);
        self::assertSame('Booking completed', $summary['timeline'][1]['title']);
        self::assertSame(500.0, $summary['settlement']['refund_amount']);
        self::assertSame(0.0, $summary['settlement']['balance_due']);
    }
}
