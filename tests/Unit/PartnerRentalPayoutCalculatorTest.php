<?php

namespace Tests\Unit;

use App\Services\PartnerRentalPayoutCalculator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PartnerRentalPayoutCalculatorTest extends TestCase
{
    public function test_fixed_rate_uses_actual_hours_instead_of_rounded_days(): void
    {
        $this->assertSame('1125.00', PartnerRentalPayoutCalculator::fixed('900.00', 30 * 3600));
        $this->assertSame('1800.00', PartnerRentalPayoutCalculator::fixed('900.00', 48 * 3600));
        $this->assertSame('37.50', PartnerRentalPayoutCalculator::fixed('900.00', 3600));
    }

    public function test_partial_hours_are_prorated_without_rounding_the_hourly_rate(): void
    {
        $this->assertSame('1143.75', PartnerRentalPayoutCalculator::fixed('900.00', 30 * 3600 + 1800));
        $this->assertSame('0.69', PartnerRentalPayoutCalculator::fixed('999.99', 60));
    }

    public function test_percentage_uses_discounted_rental_and_commission(): void
    {
        $this->assertSame('3500.00', PartnerRentalPayoutCalculator::percentage('5000.00', '30.00'));
        $this->assertSame('1400.00', PartnerRentalPayoutCalculator::percentage('2000.00', '30.00'));
        $this->assertSame('1750.00', PartnerRentalPayoutCalculator::percentage('2500.00', '30.00'));
        $this->assertSame('0.01', PartnerRentalPayoutCalculator::percentage('0.01', '50.00'));
    }

    public function test_fixed_rate_rejects_negative_money(): void
    {
        $this->expectException(ValidationException::class);
        PartnerRentalPayoutCalculator::fixed('-900', 108000);
    }

    public function test_fixed_rate_rejects_fractional_paise(): void
    {
        $this->expectException(ValidationException::class);
        PartnerRentalPayoutCalculator::fixed('900.001', 108000);
    }

    public function test_fixed_rate_rejects_empty_duration(): void
    {
        $this->expectException(ValidationException::class);
        PartnerRentalPayoutCalculator::fixed('900', 0);
    }

    public function test_percentage_rejects_commission_above_one_hundred(): void
    {
        $this->expectException(ValidationException::class);
        PartnerRentalPayoutCalculator::percentage('5000', '100.01');
    }
    public function test_duracabs_seventy_percent_leaves_vendor_thirty_percent(): void
    {
        $this->assertSame('1800.00', PartnerRentalPayoutCalculator::percentage('6000.00', '70.00'));
    }

    public function test_thirty_hours_pays_one_and_a_quarter_days(): void
    {
        $this->assertSame('3750.00', PartnerRentalPayoutCalculator::fixed('3000.00', 30 * 3600));
        $this->assertSame('3125.00', PartnerRentalPayoutCalculator::fixed('2500.00', 30 * 3600));
    }

    public function test_monthly_agreement_amount_is_not_multiplied_by_bookings(): void
    {
        $this->assertSame('10000.00', PartnerRentalPayoutCalculator::monthly('10000'));
        $this->assertSame('60000.00', PartnerRentalPayoutCalculator::monthly('60000'));
    }

    public function test_zero_monthly_amount_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        PartnerRentalPayoutCalculator::monthly('0');
    }

    public function test_monthly_amount_rejects_fractional_paise(): void
    {
        $this->expectException(ValidationException::class);
        PartnerRentalPayoutCalculator::monthly('10000.001');
    }
}
