<?php
namespace Tests\Unit;

use App\Services\RoundTripFareService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RoundTripCategoryQuoteTest extends TestCase
{
    private function category(): object
    {
        return (object) ['id' => 1, 'name' => 'Sedan', 'range' => 250, 'km_charge' => 11, 'driver_charge' => 250];
    }

    public function test_category_minimum_and_both_fare_types_share_one_formula(): void
    {
        $normal = RoundTripFareService::categoryQuote($this->category(), 400, 2, 'normal');
        $inclusive = RoundTripFareService::categoryQuote($this->category(), 400, 2, 'all_inclusive');
        $this->assertSame(500.0, $normal['billable_km']);
        $this->assertSame(6000.0, $normal['grand_total']);
        $this->assertSame(7000.0, $inclusive['grand_total']);
        $this->assertSame(0, $inclusive['driver_total']);
        $this->assertSame('extra_as_actual', $inclusive['parking_policy']);
    }

    public function test_quantity_is_applied_once(): void
    {
        $quote = RoundTripFareService::categoryQuote($this->category(), 400, 2, 'all_inclusive', 2);
        $this->assertSame(14000.0, $quote['grand_total']);
        $this->assertSame(11000.0, $quote['base_fare']);
    }

    public function test_actual_distance_above_minimum_preserves_paise(): void
    {
        $quote = RoundTripFareService::categoryQuote($this->category(), 501.25, 2);
        $this->assertSame(7017.50, $quote['grand_total']);
    }

    public function test_missing_minimum_uses_legacy_300_km_fallback(): void
    {
        $category = $this->category(); $category->range = null;
        $this->assertEquals(600.0, RoundTripFareService::categoryQuote($category, 400, 2)['billable_km']);
    }

    public function test_missing_end_time_is_not_manufactured(): void
    {
        $this->expectException(InvalidArgumentException::class);
        RoundTripFareService::validatePeriod('2027-01-02', '10:00', '2027-01-03', null);
    }

    public function test_reversed_dates_do_not_become_positive_trip_days(): void
    {
        $this->expectException(InvalidArgumentException::class);
        RoundTripFareService::tripDays('2027-01-04', '2027-01-02');
    }
}
