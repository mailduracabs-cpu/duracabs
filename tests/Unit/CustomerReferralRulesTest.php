<?php
namespace Tests\Unit;
use App\Services\CustomerReferralRules;
use PHPUnit\Framework\TestCase;
class CustomerReferralRulesTest extends TestCase {
    public function test_only_fully_paid_completed_trips_qualify(): void {
        $trip = (object) ['grand_total' => 1000, 'paid_amount' => 1000, 'status' => 'completed', 'payment_status' => 'paid'];
        $this->assertSame('credit', CustomerReferralRules::bookingState($trip));
        $trip->paid_amount = 500; $this->assertSame('pending', CustomerReferralRules::bookingState($trip));
        $trip->paid_amount = 1000; $trip->status = 'running'; $this->assertSame('pending', CustomerReferralRules::bookingState($trip));
    }
    public function test_cancelled_and_refunded_trips_reverse_even_if_other_status_says_completed(): void {
        $trip = (object) ['grand_total' => 1000, 'paid_amount' => 1000, 'status' => 'completed', 'booking_status' => 'cancelled', 'payment_status' => 'paid'];
        $this->assertSame('reverse', CustomerReferralRules::bookingState($trip));
        $trip->booking_status = 'completed'; $trip->refund_amount = 100;
        $this->assertSame('reverse', CustomerReferralRules::bookingState($trip));
    }
    public function test_legacy_order_money_metadata_is_supported_without_fabricating_payment(): void {
        $trip = (object) ['grand_total' => 1000, 'status' => 'completed', 'extraOptions' => json_encode(['payment_status' => 'paid', 'reservation' => ['paid_amount' => 1000]])];
        $this->assertSame('credit', CustomerReferralRules::bookingState($trip));
        $trip->extraOptions = '{}'; $this->assertSame('pending', CustomerReferralRules::bookingState($trip));
    }
}
