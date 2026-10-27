<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Read-only API security and ledger-contract checks. No OTP, Razorpay or payouts. */
class PartnerMarketplaceSecurityTest extends TestCase
{
    public function test_every_partner_marketplace_business_endpoint_rejects_anonymous_access(): void
    {
        $cases = [
            ['GET', '/api/v1/partner/marketplace?role=vendor', []],
            ['POST', '/api/v1/partner/marketplace', ['role' => 'vendor']],
            ['POST', '/api/v1/partner/marketplace/999999999/close', ['role' => 'vendor']],
            ['GET', '/api/v1/partner/marketplace/999999999/messages?role=vendor&peer_vendor_id=1', []],
            ['POST', '/api/v1/partner/marketplace/999999999/messages', ['role' => 'vendor']],
            ['POST', '/api/v1/partner/marketplace/999999999/quotes', ['role' => 'vendor']],
            ['POST', '/api/v1/partner/marketplace/999999999/quotes/999999999/select', ['role' => 'vendor']],
            ['GET', '/api/v1/partner/marketplace/payments/999999999?role=vendor', []],
            ['POST', '/api/v1/partner/marketplace/payments/999999999/order', ['role' => 'vendor']],
            ['POST', '/api/v1/partner/marketplace/payments/999999999/verify', ['role' => 'vendor']],
            ['POST', '/api/v1/partner/marketplace/payments/999999999/end', ['role' => 'vendor']],
            ['POST', '/api/v1/partner/marketplace/payments/999999999/confirm-completion', ['role' => 'vendor']],
            ['POST', '/api/v1/partner/marketplace/payments/999999999/cancel', ['role' => 'vendor']],
            ['POST', '/api/v1/partner/marketplace/payments/999999999/retry-refund', ['role' => 'vendor']],
        ];
        foreach ($cases as [$method, $uri, $payload]) {
            $response = $this->withHeader('Accept', 'application/json')->json($method, $uri, $payload);
            $this->assertSame(401, $response->status(), "$method $uri must reject unauthenticated requests");
        }
    }

    public function test_all_15_routes_have_expected_http_methods(): void
    {
        $expected = [
            'GET api/v1/partner/marketplace',
            'POST api/v1/partner/marketplace',
            'POST api/v1/partner/marketplace/payment-webhook',
            'POST api/v1/partner/marketplace/payments/{payment}/order',
            'POST api/v1/partner/marketplace/payments/{payment}/verify',
            'GET api/v1/partner/marketplace/payments/{payment}',
            'POST api/v1/partner/marketplace/payments/{payment}/cancel',
            'POST api/v1/partner/marketplace/payments/{payment}/end',
            'POST api/v1/partner/marketplace/payments/{payment}/confirm-completion',
            'POST api/v1/partner/marketplace/payments/{payment}/retry-refund',
            'POST api/v1/partner/marketplace/{post}/close',
            'GET api/v1/partner/marketplace/{post}/messages',
            'POST api/v1/partner/marketplace/{post}/messages',
            'POST api/v1/partner/marketplace/{post}/quotes',
            'POST api/v1/partner/marketplace/{post}/quotes/{quote}/select',
        ];
        $registered = [];
        foreach (Route::getRoutes() as $route) {
            foreach ($route->methods() as $method) {
                $registered[$method.' '.$route->uri()] = true;
            }
        }
        foreach ($expected as $route) {
            $this->assertArrayHasKey($route, $registered, "Missing route: $route");
        }
    }

    public function test_marketplace_financial_and_chat_schema_contract_is_present(): void
    {
        $columns = [
            'partner_market_posts' => ['id', 'transporter_profile_id', 'kind', 'vendor_commission', 'service_fee', 'vendor_payout', 'secure_booking', 'status'],
            'partner_market_quotes' => ['id', 'post_id', 'transporter_profile_id', 'amount', 'status'],
            'partner_market_messages' => ['id', 'post_id', 'sender_vendor_id', 'recipient_vendor_id', 'message', 'created_at', 'updated_at'],
            'partner_market_secure_payments' => ['id', 'post_id', 'quote_id', 'payer_vendor_id', 'payee_vendor_id', 'commission_paise', 'service_fee_paise', 'payout_paise', 'status', 'refund_status', 'payout_status', 'ended_at', 'completion_confirmed_at', 'settlement_eligible_at'],
        ];
        foreach ($columns as $table => $names) {
            $this->assertTrue(Schema::hasTable($table), "Missing table $table");
            foreach ($names as $name) {
                $this->assertTrue(Schema::hasColumn($table, $name), "Missing column $table.$name");
            }
        }
    }

    public function test_stored_secure_payment_amounts_balance_without_modifying_rows(): void
    {
        $rows = DB::table('partner_market_secure_payments')
            ->select('id', 'commission_paise', 'service_fee_paise', 'payout_paise')
            ->orderBy('id')->limit(100)->get();
        foreach ($rows as $row) {
            $this->assertSame((int) $row->commission_paise,
                (int) $row->service_fee_paise + (int) $row->payout_paise,
                "Payment {$row->id} does not balance");
        }
        $this->assertTrue(true); // Empty ledger is valid; no payment is created by this test.
    }
}
