<?php

namespace Tests\Unit;

use App\Services\CategoryBookingOptions;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CategoryBookingOptionsTest extends TestCase
{
    public function test_unselected_services_cost_nothing(): void
    {
        $result = CategoryBookingOptions::selected(['pet_friendly_charge' => 200], []);
        $this->assertSame([], $result['items']);
        $this->assertSame(0.0, $result['total']);
    }

    public function test_only_selected_services_use_category_prices_once(): void
    {
        $result = CategoryBookingOptions::selected([
            'pet_friendly_charge' => 200, 'roof_carrier_charge' => 300,
            'extra_pickup_charge' => 500, 'extra_drop_charge' => 500,
        ], ['pet_friendly', 'roof_carrier', 'pet_friendly']);
        $this->assertSame(500.0, $result['total']);
        $this->assertCount(2, $result['items']);
    }

    public function test_missing_charge_is_not_invented_as_free(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CategoryBookingOptions::selected([], ['roof_carrier']);
    }

    public function test_configured_zero_is_a_valid_free_optional_service(): void
    {
        $result = CategoryBookingOptions::selected(['roof_carrier_charge' => 0], ['roof_carrier']);
        $this->assertSame(0.0, $result['total']);
        $this->assertCount(1, $result['items']);
    }
}
