<?php

namespace Tests\Unit;

use App\Models\SelfDriveBooking;
use App\Models\Vehicle;
use App\Services\SelfDriveVendorPayoutService;
use Tests\TestCase;

class VendorPayoutBookingTermsTest extends TestCase
{
    private function bookingWithTerms(array $terms): SelfDriveBooking
    {
        $vehicle = (new Vehicle())->setRawAttributes([
            'id' => 1, 'service_type' => 'self_drive', 'daily_price' => 6000,
            'commission_percentage' => 70, 'partner_payout_mode' => 'monthly',
            'partner_monthly_amount' => 60000, 'car_company_name' => 'Test',
        ], true);
        $booking = (new SelfDriveBooking())->setRawAttributes([
            'id' => 1, 'vehicle_id' => 1, 'booking_no' => 'PAYOUT-TEST',
            'booked_hours' => 30, 'price_per_day' => 6000,
            'start_datetime' => '2026-10-25 08:00:00',
            'end_datetime' => '2026-10-26 14:00:00',
            'total_amount' => 6000, 'final_amount' => 6500,
            'return_completion_audit' => json_encode(['base_rental' => '6000.00']),
            'partner_payout_terms' => json_encode($terms),
        ], true);
        $booking->setRelation('vehicle', $vehicle);

        return $booking;
    }

    public function test_percentage_uses_booking_commission_and_excludes_return_charges(): void
    {
        $booking = $this->bookingWithTerms(['mode' => 'percentage', 'commission_percentage' => '70.00']);
        $result = app(SelfDriveVendorPayoutService::class)->calculateBookingPayout($booking);
        $this->assertSame('1800.00', $result['payout_amount']);
        $this->assertSame('6000.00', $result['payout_terms']['rental_basis']);
    }

    public function test_fixed_booking_snapshot_survives_current_vehicle_mode_change(): void
    {
        $booking = $this->bookingWithTerms(['mode' => 'fixed', 'fixed_rate_per_24h' => '3000.00']);
        $result = app(SelfDriveVendorPayoutService::class)->calculateBookingPayout($booking);
        $this->assertSame('3750.00', $result['payout_amount']);
        $this->assertSame(108000, $result['payout_terms']['duration_seconds']);
        $this->assertSame(1.25, $result['payout_terms']['prorated_days']);
    }

    public function test_monthly_booking_is_never_a_booking_wise_vendor_payment(): void
    {
        $booking = $this->bookingWithTerms(['mode' => 'monthly', 'monthly_amount' => '60000.00']);
        $booking->vehicle->partner_payout_mode = 'percentage';
        $this->assertNull(app(SelfDriveVendorPayoutService::class)->calculateBookingPayout($booking));
    }

    public function test_monthly_snapshot_keeps_owner_amount_separate_from_customer_monthly_price(): void
    {
        $vehicle = $this->bookingWithTerms([])->vehicle;
        $vehicle->monthly_price = '120000.00';
        $this->assertSame('60000.00', $vehicle->partnerPayoutTerms()['monthly_amount']);
        $this->assertNull($vehicle->partnerPricingPolicy()['vendor_rate_per_24h']);
        $this->assertFalse($vehicle->partnerPricingPolicy()['can_edit_rental_prices']);
    }
}
