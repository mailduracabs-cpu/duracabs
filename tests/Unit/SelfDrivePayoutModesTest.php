<?php

namespace Tests\Unit;

use App\Models\SelfDriveBooking;
use App\Models\Vehicle;
use App\Models\User;
use App\Services\SelfDriveVendorPayoutService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SelfDrivePayoutModesTest extends TestCase
{
    private function booking(array $terms): SelfDriveBooking
    {
        $booking = new SelfDriveBooking([
            'booking_type' => 'car', 'booking_no' => 'TEST', 'vehicle_id' => 10,
            'start_datetime' => '2026-10-12 08:00:00', 'end_datetime' => '2026-10-13 14:00:00',
            'booked_hours' => 30, 'price_per_day' => '3000.00',
            'total_amount' => '5000.00', 'security_deposit' => '5000.00',
        ]);
        $booking->partner_payout_terms = $terms;
        $vehicle = new Vehicle(['service_type' => Vehicle::SERVICE_SELF_DRIVE,
            'daily_price' => '9000.00', 'commission_percentage' => 80,
            'partner_payout_mode' => 'fixed', 'partner_fixed_rate_per_24h' => '2000.00']);
        $booking->setRelation('vehicle', $vehicle);
        return $booking;
    }

    public function test_fixed_uses_booking_terms_after_admin_changes_vehicle(): void
    {
        $booking = $this->booking(['version' => 1, 'mode' => 'fixed', 'fixed_rate_per_24h' => '900.00']);
        $result = app(SelfDriveVendorPayoutService::class)->calculateBookingPayout($booking);
        $this->assertSame('1125.00', $result['payout_amount']);
        $this->assertSame(108000, $result['payout_terms']['duration_seconds']);
    }

    public function test_percentage_uses_saved_commission_and_adjusted_rental_without_deposit(): void
    {
        $booking = $this->booking(['version' => 1, 'mode' => 'percentage', 'commission_percentage' => '30.00']);
        $booking->manual_price = '2000.00';
        $result = app(SelfDriveVendorPayoutService::class)->calculateBookingPayout($booking);
        $this->assertSame('1400.00', $result['payout_amount']);
    }

    public function test_return_penalties_do_not_become_percentage_rental(): void
    {
        $booking = $this->booking(['version' => 1, 'mode' => 'percentage', 'commission_percentage' => '30.00']);
        $booking->return_completion_audit = ['base_rental' => 2000];
        $booking->final_amount = 3500;
        $result = app(SelfDriveVendorPayoutService::class)->calculateBookingPayout($booking);
        $this->assertSame('1400.00', $result['payout_amount']);
    }

    public function test_legacy_retains_rounded_day_rule_and_frozen_commission(): void
    {
        $booking = $this->booking(['version' => 0, 'mode' => 'legacy', 'commission_percentage' => '30.00']);
        $result = app(SelfDriveVendorPayoutService::class)->calculateBookingPayout($booking);
        $this->assertEquals(4200, $result['payout_amount']);
    }

    public function test_vehicle_policy_locks_fixed_and_allows_percentage_prices(): void
    {
        $vehicle = $this->booking(['mode' => 'fixed'])->vehicle;
        $this->assertFalse($vehicle->partnerPricingPolicy()['can_edit_rental_prices']);
        $vehicle->partner_payout_mode = 'percentage';
        $vehicle->commission_percentage = '30.00';
        $this->assertTrue($vehicle->partnerPricingPolicy()['can_edit_rental_prices']);
        $this->assertEquals(70, $vehicle->partnerPricingPolicy()['vendor_share_percentage']);
    }

    private function validateAsVendor(Vehicle $vehicle): void
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('canUseAdminLogin')->andReturn(false);
        Auth::shouldReceive('user')->andReturn($user);
        (new \ReflectionMethod(Vehicle::class, 'validatePartnerPayoutPolicy'))->invoke($vehicle);
    }

    public function test_fixed_price_change_is_rejected_before_database_write(): void
    {
        $vehicle = $this->booking(['mode' => 'fixed'])->vehicle;
        $vehicle->exists = true;
        $vehicle->syncOriginal();
        $vehicle->daily_price = '1000.00';
        $this->expectException(ValidationException::class);
        $this->validateAsVendor($vehicle);
    }

    public function test_vendor_cannot_switch_mode_to_unlock_prices(): void
    {
        $vehicle = $this->booking(['mode' => 'fixed'])->vehicle;
        $vehicle->exists = true;
        $vehicle->syncOriginal();
        $vehicle->partner_payout_mode = 'percentage';
        $this->expectException(ValidationException::class);
        $this->validateAsVendor($vehicle);
    }

    public function test_percentage_vendor_can_change_rental_but_not_commission(): void
    {
        $vehicle = $this->booking(['mode' => 'fixed'])->vehicle;
        $vehicle->partner_payout_mode = 'percentage';
        $vehicle->exists = true;
        $vehicle->syncOriginal();
        $vehicle->daily_price = '1000.00';
        $this->validateAsVendor($vehicle);
        $vehicle->commission_percentage = '5.00';
        $this->expectException(ValidationException::class);
        $this->validateAsVendor($vehicle);
    }
}
