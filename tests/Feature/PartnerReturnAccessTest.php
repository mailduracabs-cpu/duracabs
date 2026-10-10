<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PartnerReturnAccessTest extends TestCase
{
    public function test_return_flow_requires_authentication(): void
    {
        $this->getJson('/api/v1/partner/bookings/1/return?role=host')->assertUnauthorized();
    }

    public function test_customer_session_cannot_open_partner_return_flow(): void
    {
        // A transient/customer session must fail before any booking data is queried.
        $user = new User;
        $user->setRawAttributes(['id' => 123, 'name' => 'Customer']);
        Sanctum::actingAs($user, []);
        $this->getJson('/api/v1/partner/bookings/1/return?role=host')->assertForbidden();
    }
}
