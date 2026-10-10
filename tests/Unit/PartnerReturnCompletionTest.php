<?php

namespace Tests\Unit;

use App\Services\PartnerReturnCompletionService;
use App\Services\ReturnBillMoney;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PartnerReturnCompletionTest extends TestCase
{
    public function test_admin_adjusted_rental_is_used_without_deposit_becoming_rental(): void
    {
        $this->assertSame('2250.00', ReturnBillMoney::rental('2000.00', ['100.00', '150.00']));
        $this->assertSame('2000.00', ReturnBillMoney::rental('2000.00', []));
    }

    public function test_return_charges_preserve_whole_paise(): void
    {
        $this->assertSame('0.60', ReturnBillMoney::rental('0.10', ['0.20', '0.30']));
    }

    public function test_return_charges_reject_fractional_paise(): void
    {
        $this->expectException(ValidationException::class);
        ReturnBillMoney::rental('2000', ['0.001']);
    }

    public function test_negative_return_charges_cannot_reduce_the_bill(): void
    {
        $this->expectException(ValidationException::class);
        ReturnBillMoney::rental('2000', ['-100']);
    }

    public function test_future_return_is_not_accepted_as_physical_return(): void
    {
        $this->expectException(ValidationException::class);
        app(PartnerReturnCompletionService::class)->data([
            'returned_at' => now()->addDay()->toIso8601String(), 'end_km' => 100, 'fuel' => 'full',
        ]);
    }

    public function test_missing_fuel_and_odometer_cannot_be_skipped(): void
    {
        $this->expectException(ValidationException::class);
        app(PartnerReturnCompletionService::class)->data(['returned_at' => now()->toIso8601String()]);
    }
}
