<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Read-only smoke checks; no OTP, payment, refund, or payout is executed. */
class PartnerMarketplaceSmokeTest extends TestCase
{
    public function test_marketplace_requires_authentication(): void
    {
        $this->withHeader('Accept', 'application/json')
            ->get('/api/v1/partner/marketplace?role=vendor')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_phase_four_marketplace_routes_are_registered(): void
    {
        $routes = [
            ['GET', 'api/v1/partner/marketplace'],
            ['POST', 'api/v1/partner/marketplace'],
            ['POST', 'api/v1/partner/marketplace/payment-webhook'],
            ['POST', 'api/v1/partner/marketplace/payments/{payment}/order'],
            ['POST', 'api/v1/partner/marketplace/payments/{payment}/verify'],
            ['GET', 'api/v1/partner/marketplace/payments/{payment}'],
            ['POST', 'api/v1/partner/marketplace/payments/{payment}/cancel'],
            ['POST', 'api/v1/partner/marketplace/payments/{payment}/end'],
            ['POST', 'api/v1/partner/marketplace/payments/{payment}/confirm-completion'],
            ['POST', 'api/v1/partner/marketplace/payments/{payment}/retry-refund'],
            ['POST', 'api/v1/partner/marketplace/{post}/close'],
            ['GET', 'api/v1/partner/marketplace/{post}/messages'],
            ['POST', 'api/v1/partner/marketplace/{post}/messages'],
            ['POST', 'api/v1/partner/marketplace/{post}/quotes'],
            ['POST', 'api/v1/partner/marketplace/{post}/quotes/{quote}/select'],
        ];
        $registered = Route::getRoutes();
        foreach ($routes as [$method, $uri]) {
            $found = false;
            foreach ($registered as $route) {
                if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "$method $uri route missing");
        }
    }

    public function test_marketplace_tables_exist_without_modifying_data(): void
    {
        foreach (['partner_market_posts', 'partner_market_quotes', 'partner_market_messages', 'partner_market_secure_payments'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: $table");
        }
    }

    public function test_secure_payment_lifecycle_columns_exist(): void
    {
        $table = 'partner_market_secure_payments';
        foreach (['status', 'refund_status', 'ended_at', 'completion_confirmed_at', 'settlement_eligible_at', 'payout_status'] as $column) {
            $this->assertTrue(Schema::hasColumn($table, $column), "Missing $table.$column");
        }
    }
}
