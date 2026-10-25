<?php

namespace Tests\Unit;

use App\Services\VendorMonthlySchedule;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VendorMonthlyScheduleTest extends TestCase
{
    public function test_normal_joining_date_anchors_calendar_months(): void
    {
        $cycle = VendorMonthlySchedule::forDate('2026-10-15', '2026-10-25');
        $this->assertSame('2026-10-15', $cycle['period_start']);
        $this->assertSame('2026-11-14', $cycle['period_end']);
        $this->assertSame('2026-11-15', $cycle['next_billing_date']);
    }

    public function test_anniversary_starts_the_new_period(): void
    {
        $cycle = VendorMonthlySchedule::forDate('2026-10-15', '2026-11-15');
        $this->assertSame('2026-11-15', $cycle['period_start']);
        $this->assertSame('2026-12-15', $cycle['next_billing_date']);
    }

    public function test_day_31_uses_short_month_end_without_permanent_drift(): void
    {
        $february = VendorMonthlySchedule::forDate('2026-01-31', '2026-02-28');
        $this->assertSame('2026-02-28', $february['period_start']);
        $this->assertSame('2026-03-31', $february['next_billing_date']);
        $march = VendorMonthlySchedule::forDate('2026-01-31', '2026-03-30');
        $this->assertSame('2026-02-28', $march['period_start']);
        $this->assertSame('2026-03-31', $march['next_billing_date']);
    }

    public function test_leap_year_and_year_boundary(): void
    {
        $this->assertSame('2028-02-29', VendorMonthlySchedule::forDate('2028-01-31', '2028-02-01')['next_billing_date']);
        $this->assertSame('2027-01-15', VendorMonthlySchedule::forDate('2026-12-15', '2026-12-31')['next_billing_date']);
    }

    public function test_future_joining_date_is_an_upcoming_period(): void
    {
        $cycle = VendorMonthlySchedule::forDate('2026-11-15', '2026-10-25');
        $this->assertFalse($cycle['started']);
        $this->assertSame('2026-11-15', $cycle['period_start']);
    }

    public function test_invalid_calendar_date_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        VendorMonthlySchedule::forDate('2026-02-31', '2026-10-25');
    }
}
