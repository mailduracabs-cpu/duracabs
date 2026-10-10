<?php

namespace Tests\Unit;

use App\Services\SelfDriveSecurityRefundService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SelfDriveSecurityRefundTest extends TestCase
{
    public function test_online_refund_requires_a_transaction_reference_before_database_write(): void
    {
        try {
            app(SelfDriveSecurityRefundService::class)->record(1, [
                'amount' => '1000', 'method' => 'online', 'refunded_at' => now()->toIso8601String(),
                'note' => 'Returned by bank', 'request_key' => '8f832ad7-691c-4a95-9b11-1b4bcf69fb66', 'confirmed' => true,
            ], 1);
            $this->fail('Missing transaction reference was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reference', $e->errors());
        }
    }

    public function test_refund_requires_confirmation_of_actual_payment_before_database_write(): void
    {
        try {
            app(SelfDriveSecurityRefundService::class)->record(1, [
                'amount' => '1000', 'method' => 'cash', 'refunded_at' => now()->toIso8601String(),
                'note' => 'Cash returned', 'request_key' => '8f832ad7-691c-4a95-9b11-1b4bcf69fb66', 'confirmed' => false,
            ], 1);
            $this->fail('Unconfirmed refund was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('confirmed', $e->errors());
        }
    }

    public function test_partial_refunds_keep_exact_balance_and_history(): void
    {
        $summary = SelfDriveSecurityRefundService::summary((object) [
            'security_deposit' => '5000.00', 'security_refund_ledger' => [
                ['amount' => '1000.10', 'method' => 'cash', 'request_key' => 'private'],
                ['amount' => '2000.20', 'method' => 'online', 'reference' => 'TX123'],
            ],
        ]);
        $this->assertSame('3000.30', $summary['refunded']);
        $this->assertSame('1999.70', $summary['pending']);
        $this->assertSame('partial', $summary['status']);
        $this->assertArrayNotHasKey('request_key', $summary['history'][0]);
        $this->assertSame('TX123', $summary['history'][1]['reference']);
    }
    public function test_legacy_refund_is_flagged_instead_of_offered_again(): void
    {
        $summary = SelfDriveSecurityRefundService::summary((object) [
            'security_deposit' => '5000', 'refund_status' => 'refunded',
        ]);
        $this->assertNull($summary['pending']);
        $this->assertSame('legacy_review_required', $summary['status']);
    }
    public function test_full_refund_and_zero_deposit_have_no_pending_amount(): void
    {
        $summary = SelfDriveSecurityRefundService::summary((object) [
            'security_deposit' => '5000', 'security_refund_ledger' => [['amount' => '5000.00', 'method' => 'cash']],
        ]);
        $this->assertSame('refunded', $summary['status']);
        $this->assertSame('0.00', $summary['pending']);
        $this->assertSame('not_applicable', SelfDriveSecurityRefundService::summary((object) [])['status']);
    }

    public function test_editing_deposit_after_a_refund_does_not_increase_refund_limit(): void
    {
        $summary = SelfDriveSecurityRefundService::summary((object) [
            'security_deposit' => '9000', 'security_refund_ledger' => [
                ['amount' => '1000.00', 'method' => 'cash', 'security_deposit_snapshot' => '5000.00'],
            ],
        ]);
        $this->assertSame('5000.00', $summary['deposit']);
        $this->assertSame('4000.00', $summary['pending']);
    }
}
