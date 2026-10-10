<?php

namespace Tests\Unit;

use App\Models\SelfDriveBooking;
use App\Services\PartnerReturnCompletionService;
use Tests\TestCase;

class PartnerReturnConfirmationTest extends TestCase
{
    public function test_equivalent_form_and_submitted_values_have_the_same_confirmation_input(): void
    {
        $service = app(PartnerReturnCompletionService::class);
        $time = now()->subDay();
        $state = ['returned_at' => $time->format('Y-m-d H:i:s'), 'end_km' => 100, 'fuel' => 'full',
            'damage_amount' => '0.00', 'fuel_charge' => '20.10', 'cleaning_charge' => '0', 'other_charge' => '0',
            'started_at' => null, 'start_km' => null, 'note' => '', 'reason' => ' Confirmed actual return '];
        $submitted = array_reverse($state, true);
        $submitted['returned_at'] = $time->toIso8601String();
        $submitted['end_km'] = '100.0';
        $submitted['damage_amount'] = 0;
        $submitted['fuel_charge'] = 20.10;
        $submitted['note'] = null;
        $submitted['reason'] = 'Confirmed actual return';
        $this->assertSame($service->data($state), $service->data($submitted));
    }

    public function test_fingerprint_ignores_key_order_but_rejects_a_changed_payment(): void
    {
        $service = app(PartnerReturnCompletionService::class);
        $method = new \ReflectionMethod($service, 'fingerprint');
        $booking = new SelfDriveBooking;
        $booking->setRawAttributes(['id' => 72, 'paid_amount' => '500.00', 'total_amount' => '2000.00']);
        $copy = new SelfDriveBooking;
        $copy->setRawAttributes(array_reverse($booking->getAttributes(), true));
        $data = ['end_km' => '100', 'fuel' => 'full'];
        $bill = ['rental' => 2000.00, 'paid' => 500.00];
        $before = $method->invoke($service, $booking, $data, $bill);
        $this->assertSame($before, $method->invoke($service, $copy, array_reverse($data, true), array_reverse($bill, true)));
        $copy->setRawAttributes(['id' => 72, 'paid_amount' => '600.00', 'total_amount' => '2000.00']);
        $this->assertNotSame($before, $method->invoke($service, $copy, $data, $bill));
    }
}
