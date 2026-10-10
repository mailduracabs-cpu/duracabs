<?php
namespace Tests\Unit;
use App\Services\PartnerPayoutPaymentService as Money;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class PartnerPayoutMoneyTest extends TestCase {
    public function test_partial_payment_balance_is_exact(): void {
        $this->assertSame('5000.00',Money::money(Money::cents('25000.00')-Money::cents('20000.00')));
        $this->assertSame('0.20',Money::money(Money::cents('0.30')-Money::cents('0.10')));
        $this->assertSame('0.00',Money::money(Money::cents('25000')-Money::cents('25000')));
    }
    public function test_negative_payments_are_rejected(): void {
        $this->expectException(ValidationException::class); Money::cents('-1');
    }
    public function test_non_numeric_payments_are_rejected(): void {
        $this->expectException(ValidationException::class); Money::cents('25000xyz');
    }
    public function test_fractional_paise_are_rejected(): void {
        $this->expectException(ValidationException::class); Money::cents('0.001');
    }
}
