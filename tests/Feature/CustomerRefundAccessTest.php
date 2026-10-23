<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerRefundAccessTest extends TestCase
{
    public function test_refund_status_does_not_expose_booking_money_to_a_guest(): void
    {
        $this->getJson('/api/v1/self-drive/refund-status/99999999')->assertStatus(401);
    }
}
