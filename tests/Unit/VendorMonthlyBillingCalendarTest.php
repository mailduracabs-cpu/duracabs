<?php

namespace Tests\Unit;

use App\Services\VendorMonthlyBilling;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VendorMonthlyBillingCalendarTest extends TestCase
{
    public function test_fixed_fifteenth_first_period_is_clipped_to_joining(): void
    {
        $periods = VendorMonthlyBilling::periods('2026-10-25', '2029-10-25', '2026-11-15', 'fixed_day', 15, 'prorate');
        $this->assertCount(1, $periods);
        $this->assertSame('2026-10-25', $periods[0]['period_start']);
        $this->assertSame('2026-11-14', $periods[0]['period_end']);
        $this->assertSame(21, $periods[0]['billable_days']);
        $this->assertSame(31, $periods[0]['cycle_days']);
    }

    public function test_joining_day_bills_at_completed_calendar_anniversary(): void
    {
        $this->assertSame([], VendorMonthlyBilling::periods('2026-10-25', '2029-10-25', '2026-11-24', 'joining_date', 15, 'prorate'));
        $periods = VendorMonthlyBilling::periods('2026-10-25', '2029-10-25', '2026-11-25', 'joining_date', 15, 'prorate');
        $this->assertSame('2026-11-25', $periods[0]['bill_date']);
        $this->assertSame(31, $periods[0]['billable_days']);
    }

    public function test_final_period_stops_at_manual_expiry_and_is_billed_on_next_boundary(): void
    {
        $periods = VendorMonthlyBilling::periods('2026-10-25', '2027-04-25', '2027-05-15', 'fixed_day', 15, 'prorate');
        $last = end($periods);
        $this->assertSame('2027-04-24', $last['period_end']);
        $this->assertSame('2027-05-15', $last['bill_date']);
        $this->assertSame(10, $last['billable_days']);
    }

    public function test_short_month_returns_to_original_day(): void
    {
        $periods = VendorMonthlyBilling::periods('2026-01-31', '2029-01-31', '2026-03-31', 'joining_date', 15, 'prorate');
        $this->assertSame('2026-02-28', $periods[0]['bill_date']);
        $this->assertSame('2026-03-31', $periods[1]['bill_date']);
        $this->assertSame('2026-02-28', $periods[1]['period_start']);
    }

    public function test_unknown_billing_policy_cannot_generate_a_bill(): void
    {
        $this->expectException(ValidationException::class);
        VendorMonthlyBilling::periods('2026-10-25', '2029-10-25', '2026-11-25', '', 15, '');
    }
    public function test_fixed_day_preview_matches_the_bill_period(): void
    {
        $cycle = \App\Services\VendorMonthlySchedule::forBillingDate('2026-10-25', 'fixed_day', 15, '2026-10-26');
        $this->assertSame('2026-10-25', $cycle['period_start']);
        $this->assertSame('2026-11-14', $cycle['period_end']);
        $this->assertSame('2026-11-15', $cycle['next_billing_date']);
    }

    public function test_fixed_day_preview_handles_future_joining(): void
    {
        $cycle = \App\Services\VendorMonthlySchedule::forBillingDate('2026-12-25', 'fixed_day', 15, '2026-10-25');
        $this->assertFalse($cycle['started']);
        $this->assertSame('2026-12-25', $cycle['period_start']);
        $this->assertSame('2027-01-15', $cycle['next_billing_date']);
    }
    public function test_indian_calendar_day_does_not_follow_utc_server_midnight(): void
    {
        $previous = \Carbon\CarbonImmutable::getTestNow();
        try {
            \Carbon\CarbonImmutable::setTestNow(\Carbon\CarbonImmutable::parse('2026-10-24 20:00:00', 'UTC'));
            $this->assertSame('2026-10-25', \App\Services\VendorLifecycleClock::today()->toDateString());
        } finally {
            \Carbon\CarbonImmutable::setTestNow($previous);
        }
    }
}
