<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Phase 5B: HTTP integration tests using verified vendor accounts from a CLONED test database.
 * No gateway calls. Paid state is simulated by a direct update in the test transaction.
 */
class PartnerMarketplaceWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private const ROOT = '/api/v1/partner/marketplace';

    protected function setUp(): void
    {
        parent::setUp();
        // Disable only request throttling for this isolated HTTP workflow test.
        // Production rate limits and other middleware remain unchanged.
        $this->withoutMiddleware(ThrottleRequests::class);
        $name = strtolower((string) DB::connection()->getDatabaseName());
        if (app()->environment() !== 'testing' || !str_contains($name, 'test') ||
            env('MARKETPLACE_TEST_DB_APPROVED') !== '1') {
            throw new \RuntimeException('REFUSING marketplace workflow tests: use APP_ENV=testing, a cloned DB whose name contains test, and MARKETPLACE_TEST_DB_APPROVED=1.');
        }
    }

    /** @return array<int, array{0:int,1:string}> */
    private function vendors(int $count): array
    {
        $rows = DB::table('fleet_transporter_profiles')
            ->whereNull('deleted_at')->where('status', true)
            ->where('verification_status', 'verified')->where('partner_type', 'vendor')
            ->whereNotNull('user_id')->orderBy('id')->get(['id', 'user_id']);
        $result = [];
        foreach ($rows as $row) {
            $user = User::find($row->user_id);
            if (!$user || !$user->canUseTransporterLogin()) continue;
            if (DB::table('fleet_transporter_profiles')->where('user_id', $user->id)->count() !== 1) continue;
            $token = $user->createToken('duracabs_partner_app', ['partner:access'])->plainTextToken;
            $result[] = [(int) $row->id, $token];
            if (count($result) === $count) break;
        }
        if (count($result) < $count) $this->markTestSkipped("Clone at least $count eligible verified vendor profiles into the test database.");
        return $result;
    }

    private function postAs(string $token, string $path, array $payload = [])
    {
        // Sanctum's RequestGuard caches the authenticated user between HTTP
        // requests in the same PHPUnit process. Reset it when switching tokens.
        auth()->forgetGuards();
        auth('sanctum')->forgetUser();

        return $this->withToken($token)->postJson(self::ROOT . $path, ['role' => 'vendor'] + $payload);
    }

    private function getAs(string $token, string $path, array $query = [])
    {
        auth()->forgetGuards();
        auth('sanctum')->forgetUser();

        return $this->withToken($token)->getJson(self::ROOT . $path . '?' . http_build_query(['role' => 'vendor'] + $query));
    }

    private function createBooking(string $ownerToken): int
    {
        $response = $this->postAs($ownerToken, '', [
            'kind' => 'booking', 'trip_type' => 'one_way', 'vehicle_class' => 'Sedan',
            'pickup_city' => 'Agra', 'drop_city' => 'Delhi',
            'pickup_at' => now()->addDays(7)->toDateTimeString(),
            'asking_amount' => 2500, 'vendor_commission' => 500, 'secure_booking' => true,
        ]);
        $response->assertCreated()->assertJsonPath('status', true);
        $id = (int) $response->json('data.id');
        $this->assertGreaterThan(0, $id);
        $this->assertDatabaseHas('partner_market_posts', [
            'id' => $id, 'vendor_commission' => 500, 'service_fee' => 50,
            'vendor_payout' => 450, 'secure_booking' => 1,
        ]);
        return $id;
    }

    private function select(string $ownerToken, string $bidderToken): int
    {
        $post = $this->createBooking($ownerToken);
        $quoteResponse = $this->postAs($bidderToken, "/$post/quotes", ['amount' => 2000]);
        $quoteResponse->assertCreated();
        $quote = (int) $quoteResponse->json('data.id');
        $this->postAs($ownerToken, "/$post/quotes/$quote/select")
            ->assertCreated()->assertJsonPath('data.payment_status', 'pending');
        $ledger = DB::table('partner_market_secure_payments')->where('post_id', $post)->first();
        $this->assertNotNull($ledger);
        $this->assertSame(50000, (int) $ledger->commission_paise);
        $this->assertSame(5000, (int) $ledger->service_fee_paise);
        $this->assertSame(45000, (int) $ledger->payout_paise);
        return (int) $ledger->id;
    }

    public function test_booking_quote_chat_and_selection_permissions(): void
    {
        [[$ownerId, $owner], [$bidderId, $bidder], [$outsiderId, $outsider]] = $this->vendors(3);
        $post = $this->createBooking($owner);
        $this->postAs($owner, "/$post/quotes", ['amount' => 2000])->assertForbidden();
        $this->postAs($bidder, "/$post/quotes", ['amount' => 2000])->assertCreated();
        $this->postAs($bidder, "/$post/quotes", ['amount' => 2100])->assertStatus(409);
        $this->postAs($bidder, "/$post/messages", ['recipient_vendor_id' => $ownerId, 'message' => 'Available'])->assertCreated();
        $this->getAs($owner, "/$post/messages", ['peer_vendor_id' => $bidderId])
            ->assertOk()->assertJsonPath('data.items.0.message', 'Available');
        $this->postAs($outsider, "/$post/messages", ['recipient_vendor_id' => $bidderId, 'message' => 'Intrusion'])->assertForbidden();
        $this->getAs($outsider, "/$post/messages", ['peer_vendor_id' => $bidderId])->assertForbidden();
        $quote = (int) DB::table('partner_market_quotes')->where('post_id', $post)->value('id');
        $this->postAs($bidder, "/$post/quotes/$quote/select")->assertForbidden();
        $this->postAs($owner, "/$post/quotes/$quote/select")->assertCreated();
        $this->postAs($owner, "/$post/quotes/$quote/select")->assertStatus(403);
        $this->assertDatabaseHas('partner_market_posts', ['id' => $post, 'status' => 'closed']);
    }

    public function test_paid_completion_requires_both_vendors_and_blocks_cancellation(): void
    {
        [[$ownerId, $owner], [$bidderId, $bidder], [$outsiderId, $outsider]] = $this->vendors(3);
        $payment = $this->select($owner, $bidder);
        $this->postAs($owner, "/payments/$payment/confirm-completion")->assertStatus(409);
        $this->postAs($bidder, "/payments/$payment/end")->assertStatus(409);
        $this->getAs($outsider, "/payments/$payment")->assertForbidden();
        // Gateway capture is simulated in a rolled-back test transaction; no Razorpay API call.
        DB::table('partner_market_secure_payments')->where('id', $payment)
            ->update(['status' => 'paid', 'refund_status' => 'none', 'paid_at' => now()]);
        $this->postAs($owner, "/payments/$payment/end")->assertForbidden();
        $this->postAs($bidder, "/payments/$payment/end")->assertOk();
        $this->postAs($bidder, "/payments/$payment/confirm-completion")->assertForbidden();
        $this->postAs($owner, "/payments/$payment/confirm-completion")
            ->assertOk()->assertJsonPath('data.payout_status', 'eligible');
        $this->postAs($owner, "/payments/$payment/cancel")->assertStatus(409);
        $this->assertNotNull(DB::table('partner_market_secure_payments')->where('id', $payment)->value('settlement_eligible_at'));
    }

    public function test_pending_payment_cancellation_never_calls_gateway(): void
    {
        [[$ownerId, $owner], [$bidderId, $bidder]] = $this->vendors(2);
        $payment = $this->select($owner, $bidder);
        $this->postAs($bidder, "/payments/$payment/cancel", ['reason' => 'Trip withdrawn'])
            ->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->postAs($owner, "/payments/$payment/cancel")->assertOk();
        $this->postAs($bidder, "/payments/$payment/retry-refund")->assertStatus(409);
        $this->assertDatabaseHas('partner_market_secure_payments', [
            'id' => $payment, 'status' => 'cancelled', 'refund_status' => 'none',
        ]);
    }
}
