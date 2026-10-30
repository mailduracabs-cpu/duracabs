<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\PartnerMarketplaceController;
use App\Http\Controllers\Api\V1\PartnerMarketplacePaymentController;
use App\Http\Controllers\Api\V1\PartnerMarketplaceWorkspaceController;
use App\Models\FleetManagement\TransporterProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use App\Services\PartnerMarketplaceAdminService as Admin;
use App\Services\PartnerMarketplaceReservations as Reservations;
use App\Services\PartnerMarketplaceBankAccounts as Banks;
use App\Models\User;
use App\Http\Controllers\Api\V1\PartnerMarketplaceLifecycleController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/** Isolated in-memory schema: never migrates or writes the configured production DB. */
class PartnerMarketplaceIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('t', 32))]);
        app()->forgetInstance('encrypter');
        \Illuminate\Support\Facades\Crypt::clearResolvedInstance('encrypter');
        config(['database.default' => 'marketplace_test', 'database.connections.marketplace_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::purge('marketplace_test');
        Schema::create('fleet_transporter_profiles', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable(); $table->string('company_name'); $table->string('contact_person')->nullable(); $table->string('mobile')->nullable();
            $table->string('partner_type'); $table->boolean('status'); $table->string('verification_status'); $table->softDeletes();
        });
        foreach ([
            '2026_10_27_000001_create_partner_marketplace_tables.php',
            '2026_10_27_000003_create_partner_market_secure_payments.php',
            '2026_10_27_000004_add_partner_market_completion_refund_state.php',
            '2026_10_27_000005_create_partner_market_messages_table.php',
            '2026_10_27_000006_add_missing_partner_market_financial_columns.php',
            '2026_10_29_000001_add_partner_market_message_read_at.php',
            '2026_10_29_000002_add_partner_market_places_and_revisions.php',
            '2026_10_29_000003_add_partner_market_admin_resolution.php',
            '2026_10_29_000004_add_partner_market_payment_reservations.php',
            '2026_10_29_000005_add_partner_market_assignment_terms.php',
            '2026_10_29_000006_add_partner_market_fleet_submissions.php',
            '2026_10_29_000007_add_partner_market_bank_accounts.php',
            '2026_10_30_000001_add_partner_market_location_alerts.php',
        ] as $file) {
            (require database_path('migrations/' . $file))->up();
        }
        foreach ([1, 2, 3] as $id) DB::table('fleet_transporter_profiles')->insert([
            'id' => $id, 'company_name' => 'Test Vendor ' . $id, 'partner_type' => 'vendor', 'status' => true, 'verification_status' => 'verified',
        ]);
    }

    private function profile(int $id): TransporterProfile
    {
        return TransporterProfile::query()->findOrFail($id);
    }

    private function workspace(int $id): PartnerMarketplaceWorkspaceController
    {
        $controller = new class extends PartnerMarketplaceWorkspaceController {
            public TransporterProfile $profile;
            protected function context(Request $request): array { return [$this->profile, 'vendor']; }
        };
        $controller->profile = $this->profile($id);
        return $controller;
    }

    private function admin(bool $financial = true): void
    {
        $actor = new class extends User {
            public bool $financial = true;
            public function isAdmin(): bool { return $this->financial; }
            public function isModerator(): bool { return !$this->financial; }
        };
        $actor->financial = $financial;
        $actor->setRawAttributes(['id' => 991, 'name' => 'Test Admin', 'is_active' => true]);
        Auth::guard('admin')->setUser($actor);
    }

    private function capturedLedger(): int
    {
        if (!Banks::current(1)) Banks::save($this->profile(1), 0, $this->bankInput());
        $post = $this->post(true); $quote = $this->quote($post, 2);
        $result = $this->payment(1)->selectQuote(Request::create('/', 'POST', ['expected_revision' => 1]), $post, $quote)->getData(true);
        $id = (int) $result['data']['payment_id'];
        DB::table('partner_market_secure_payments')->where('id', $id)->update(['status' => 'paid', 'razorpay_payment_id' => 'pay_testcapture', 'paid_at' => now()]);
        DB::table('partner_market_posts')->where('id', $post)->update(['status' => 'assigned', 'active_payment_id' => null, 'assigned_payment_id' => $id]);
        DB::table('partner_market_quotes')->where('id', $quote)->update(['status' => 'accepted']);
        return $id;
    }

    private function bankInput(): array
    {
        return ['account_holder_name' => 'Test Vendor', 'bank_name' => 'Example Bank', 'ifsc' => 'ABCD0123456',
            'replace_account_number' => true, 'account_number' => '001234567890', 'account_number_confirmation' => '001234567890'];
    }

    private function splitInput(): array
    {
        return ['refund' => '100.00', 'payout' => '80.49', 'fee' => '20.06', 'penalty_against' => 'payee', 'penalty' => '5.00', 'reason' => 'Test dispute: poster correction'];
    }

    private function listing(int $id): PartnerMarketplaceController
    {
        $controller = new class extends PartnerMarketplaceController {
            public TransporterProfile $profile;
            protected function context(Request $request): array { return [$this->profile, 'vendor']; }
        };
        $controller->profile = $this->profile($id);
        return $controller;
    }

    private function editInput(int $post): array
    {
        $row = (array) DB::table('partner_market_posts')->find($post);
        $allowed = ['kind', 'trip_type', 'vehicle_class', 'pickup_city', 'drop_city', 'pickup_at', 'return_at',
            'asking_amount', 'pricing_mode', 'negotiable', 'notes', 'vendor_commission', 'secure_booking', 'pickup_place_id', 'drop_place_id'];
        return array_intersect_key($row, array_flip($allowed)) + ['expected_revision' => $row['revision']];
    }

    private function payment(int $id): PartnerMarketplacePaymentController
    {
        $controller = new class extends PartnerMarketplacePaymentController {
            public TransporterProfile $profile;
            protected function context(Request $request): array { return [$this->profile, 'vendor']; }
        };
        $controller->profile = $this->profile($id);
        return $controller;
    }

    private function post(bool $secure = false): int
    {
        $id = DB::table('partner_market_posts')->insertGetId([
            'transporter_profile_id' => 1, 'kind' => 'booking', 'trip_type' => 'one_way', 'vehicle_class' => 'Sedan',
            'pickup_city' => 'Agra', 'drop_city' => 'Delhi', 'pickup_at' => now()->addDay(), 'asking_amount' => 2700,
            'pricing_mode' => 'fixed', 'vendor_commission' => '200.55', 'secure_booking' => $secure, 'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
        // Pre-submitted fleet snapshots isolate payment/assignment tests from fleet UI tests.
        foreach ([2, 3] as $vendor) $this->fleetSnapshot($id, $vendor);
        return $id;
    }

    private function fleetSnapshot(int $post, int $vendor, int $revision = 1): int
    {
        return DB::table('partner_market_fleet_submissions')->insertGetId([
            'post_id' => $post, 'vendor_id' => $vendor, 'post_revision' => $revision,
            'driver_id' => 9000 + $vendor, 'vehicle_id' => 8000 + $vendor, 'driver_name' => 'Saved Driver ' . $vendor,
            'taxi_name' => 'Saved Sedan', 'taxi_number' => 'UP80TEST' . $vendor, 'car_classification' => 'Sedan',
            'photo_path' => 'partner-market-fleet/test-' . $post . '-' . $vendor . '-' . $revision . '.png', 'photo_mime' => 'image/png',
            'client_request_id' => 'fixture-' . $post . '-' . $vendor . '-' . $revision, 'created_at' => now(),
        ]);
    }

    private function quote(int $post, int $vendor, string $status = 'pending'): int
    {
        return DB::table('partner_market_quotes')->insertGetId([
            'post_id' => $post, 'transporter_profile_id' => $vendor, 'amount' => 2600, 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_non_secure_assignment_rejects_other_quotes_without_creating_payment(): void
    {
        $post = $this->post(); $selected = $this->quote($post, 2); $other = $this->quote($post, 3);
        $response = $this->payment(1)->selectQuote(Request::create('/', 'POST', ['expected_revision' => 1]), $post, $selected)->getData(true);
        $this->assertSame('not_required', $response['data']['payment_status']);
        $this->assertSame('accepted', DB::table('partner_market_quotes')->where('id', $selected)->value('status'));
        $this->assertSame('rejected', DB::table('partner_market_quotes')->where('id', $other)->value('status'));
        $this->assertSame('assigned', DB::table('partner_market_posts')->where('id', $post)->value('status'));
        $this->assertSame(0, DB::table('partner_market_secure_payments')->count());
    }

    public function test_secure_assignment_uses_stored_commission_and_does_not_claim_payment(): void
    {
        $post = $this->post(true); $quote = $this->quote($post, 2);
        $result = $this->payment(1)->selectQuote(Request::create('/', 'POST', ['amount' => 1, 'expected_revision' => 1]), $post, $quote)->getData(true);
        $ledger = DB::table('partner_market_secure_payments')->find($result['data']['payment_id']);
        $this->assertSame('pending', $ledger->status);
        $this->assertSame(20055, (int) $ledger->commission_paise);
        $this->assertSame(2006, (int) $ledger->service_fee_paise);
        $this->assertSame(18049, (int) $ledger->payout_paise);
    }

    public function test_a_different_vendor_cannot_assign_the_post(): void
    {
        $post = $this->post(); $quote = $this->quote($post, 2);
        try { $this->payment(3)->selectQuote(Request::create('/', 'POST', ['expected_revision' => 1]), $post, $quote); $this->fail('Assignment must be forbidden.'); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
        $this->assertSame('open', DB::table('partner_market_posts')->where('id', $post)->value('status'));
    }

    public function test_bidder_cannot_read_competing_quotes(): void
    {
        $post = $this->post(); $own = $this->quote($post, 2); $this->quote($post, 3);
        $data = $this->workspace(2)->details(Request::create('/'), $post)->getData(true)['data'];
        $this->assertCount(1, $data['quotes']); $this->assertEquals($own, $data['quotes'][0]['id']);
        $this->assertFalse($data['is_owner']);
    }

    public function test_received_bookings_only_include_accepted_quotes(): void
    {
        $assigned = $this->post(); $pending = $this->post();
        $this->quote($assigned, 2, 'accepted'); $this->quote($pending, 2);
        $items = $this->workspace(2)->bookings(Request::create('/', 'GET', ['scope' => 'received']))->getData(true)['data']['items'];
        $this->assertCount(1, $items); $this->assertEquals($assigned, $items[0]['id']);
    }

    public function test_read_receipts_only_change_visible_messages_for_the_authenticated_recipient(): void
    {
        $post = $this->post();
        foreach ([1, 2, 3] as $id) DB::table('partner_market_messages')->insert([
            'id' => $id, 'post_id' => $post, 'sender_vendor_id' => 1, 'recipient_vendor_id' => $id === 3 ? 3 : 2,
            'message' => 'test', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->workspace(2)->readMessages(Request::create('/', 'POST', ['peer_vendor_id' => 1, 'through_id' => 1]), $post);
        $this->assertNotNull(DB::table('partner_market_messages')->where('id', 1)->value('read_at'));
        $this->assertNull(DB::table('partner_market_messages')->where('id', 2)->value('read_at'));
        $this->assertNull(DB::table('partner_market_messages')->where('id', 3)->value('read_at'));
        $inbox = $this->workspace(2)->inbox(Request::create('/'))->getData(true)['data']['items'];
        $this->assertCount(1, $inbox); $this->assertSame(1, (int) $inbox[0]['unread_count']);
    }

    public function test_checkout_rejects_missing_signed_authorization(): void
    {
        $this->get('/api/v1/partner/marketplace/payments/1/checkout')->assertForbidden();
    }
    public function test_edit_invalidates_pending_quotes_preserves_audit_and_allows_fresh_quote(): void
    {
        $post = $this->post(); $quote = $this->quote($post, 2);
        $input = $this->editInput($post); $input['notes'] = 'Pickup corrected';
        $this->listing(1)->edit(Request::create('/', 'POST', $input), $post);
        $this->assertSame(2, (int) DB::table('partner_market_posts')->where('id', $post)->value('revision'));
        $this->assertSame('superseded', DB::table('partner_market_quotes')->where('id', $quote)->value('status'));
        $this->assertSame(1, DB::table('partner_market_post_edits')->where('post_id', $post)->count());
        $this->assertSame(1, DB::table('partner_market_messages')->where('post_id', $post)->where('recipient_vendor_id', 2)->count());
        try {
            $this->listing(2)->quote(Request::create('/', 'POST', ['amount' => 2600, 'expected_revision' => 1]), $post);
            $this->fail('Stale quote must be rejected.');
        } catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
        $this->listing(2)->quote(Request::create('/', 'POST', ['amount' => 2650, 'expected_revision' => 2]), $post);
        $this->assertSame(1, DB::table('partner_market_quotes')->where('post_id', $post)->count());
        $fresh = DB::table('partner_market_quotes')->find($quote);
        $this->assertSame('pending', $fresh->status); $this->assertSame(2, (int) $fresh->post_revision);
    }

    public function test_only_owner_can_edit_and_assignment_blocks_edit(): void
    {
        $post = $this->post(); $input = $this->editInput($post);
        try { $this->listing(2)->edit(Request::create('/', 'POST', $input), $post); $this->fail('Foreign edit allowed.'); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
        DB::table('partner_market_posts')->where('id', $post)->update(['status' => 'closed']);
        try { $this->listing(1)->edit(Request::create('/', 'POST', $input), $post); $this->fail('Closed post edit allowed.'); }
        catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
    }

    public function test_concurrent_stale_edits_cannot_overwrite_a_newer_revision(): void
    {
        $post = $this->post(); $input = $this->editInput($post); $input['notes'] = 'First edit';
        $this->listing(1)->edit(Request::create('/', 'POST', $input), $post);
        $input['notes'] = 'Stale second edit';
        try { $this->listing(1)->edit(Request::create('/', 'POST', $input), $post); $this->fail('Stale edit overwrote the newer revision.'); }
        catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
        $this->assertSame('First edit', DB::table('partner_market_posts')->where('id', $post)->value('notes'));
    }

    public function test_google_prediction_adapter_preserves_place_ids_and_description(): void
    {
        $legacy = \App\Services\PartnerMarketplacePlaces::normalizeSuggestions(['predictions' => [['place_id' => 'place_agra', 'description' => 'Agra, Uttar Pradesh, India']]]);
        $this->assertSame([['place_id' => 'place_agra', 'label' => 'Agra, Uttar Pradesh, India']], $legacy);
        $modern = \App\Services\PartnerMarketplacePlaces::normalizeSuggestions(['suggestions' => [['placePrediction' => ['placeId' => 'place_delhi', 'text' => ['text' => 'Delhi, India']]]]]);
        $this->assertSame('place_delhi', $modern[0]['place_id']);
    }

    public function test_admin_split_and_penalty_conserve_the_captured_commission_exactly(): void
    {
        $plan = Admin::allocation(20055, '100.00', '80.49', '20.06', 'payee', '5.00');
        $this->assertSame(10500, $plan['payer_refund_paise']);
        $this->assertSame(7549, $plan['payee_payout_paise']);
        $this->assertSame(20055, $plan['payer_refund_paise'] + $plan['payee_payout_paise'] + $plan['platform_fee_paise']);
        try { Admin::allocation(20055, '100.00', '80.49', '20.06', 'payer', '100.01'); $this->fail('Penalty exceeded available allocation.'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('penalty', $e->errors()); }
        try { Admin::allocation(20055, '100.00', '81.49', '20.06', 'none', '0'); $this->fail('Allocation created money.'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('allocation', $e->errors()); }
    }

    public function test_moderator_cannot_forge_a_financial_action(): void
    {
        $id = $this->capturedLedger(); $this->admin(false);
        try { Admin::hold($id, 1, 'Forged moderator action'); $this->fail('Moderator adjusted money.'); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
        $this->assertFalse((bool) DB::table('partner_market_secure_payments')->where('id', $id)->value('admin_hold'));
        $this->assertSame(0, DB::table('partner_market_admin_audits')->count());
    }

    public function test_hold_blocks_vendor_completion_and_stale_admin_release(): void
    {
        $id = $this->capturedLedger(); $this->admin(); Admin::hold($id, 1, 'Review dispute');
        $controller = new class extends PartnerMarketplaceLifecycleController {
            public TransporterProfile $profile;
            protected function context(Request $request): array { return [$this->profile, 'vendor']; }
        };
        $controller->profile = $this->profile(2);
        try { $controller->end(Request::create('/', 'POST'), $id); $this->fail('Vendor ended held payment.'); }
        catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
        try { Admin::release($id, 1, 'Stale revision'); $this->fail('Stale release succeeded.'); }
        catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
        $this->assertTrue((bool) DB::table('partner_market_secure_payments')->where('id', $id)->value('admin_hold'));
    }

    public function test_approved_partial_refund_is_exact_and_bank_payout_is_only_recorded_after_refund(): void
    {
        $id = $this->capturedLedger(); $this->admin();
        Admin::plan($id, 1, $this->splitInput()); Admin::release($id, 2, 'Approve final split');
        try { Admin::recordPayout($id, 3, 'UTR_test', 'Premature payout'); $this->fail('Payout preceded refund.'); }
        catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
        config(['services.razorpay.key' => 'rzp_test_fake', 'services.razorpay.secret' => 'fake_test_secret']);
        Http::fake(fn () => Http::response(['id' => 'rfnd_testpartial', 'payment_id' => 'pay_testcapture', 'amount' => 10500, 'status' => 'processed'], 200));
        Admin::issueRefund($id, 3, 'Execute approved partial refund');
        Http::assertSent(fn ($request) => $request->method() === 'POST' && (int) $request['amount'] === 10500);
        $row = DB::table('partner_market_secure_payments')->find($id);
        $this->assertSame('eligible', $row->payout_status);
        Admin::recordPayout($id, (int) $row->admin_revision, 'UTR_completed_7549', 'Bank transfer completed for 75.49');
        $plan = DB::table('partner_market_admin_settlements')->find($row->admin_settlement_id);
        $this->assertSame('processed', $plan->refund_status); $this->assertSame('paid', $plan->payout_status);
        $this->assertSame('UTR_completed_7549', $plan->payout_reference);
    }

    public function test_uncertain_refund_never_retries_the_post_or_allows_a_second_issue(): void
    {
        $id = $this->capturedLedger(); $this->admin();
        Admin::plan($id, 1, $this->splitInput()); Admin::release($id, 2, 'Approve split');
        config(['services.razorpay.key' => 'rzp_test_fake', 'services.razorpay.secret' => 'fake_test_secret']);
        $calls = 0;
        Http::fake(function () use (&$calls) { $calls++; return Http::response(['error' => ['description' => 'Uncertain gateway result']], 503); });
        try { Admin::issueRefund($id, 3, 'Refund attempt'); $this->fail('Uncertain refund reported success.'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('refund', $e->errors()); }
        $row = DB::table('partner_market_secure_payments')->find($id);
        try { Admin::issueRefund($id, (int) $row->admin_revision, 'Do not repeat'); $this->fail('Refund repeated.'); }
        catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
        $this->assertSame(1, $calls);
        $this->assertSame('review_required', DB::table('partner_market_admin_settlements')->where('id', $row->admin_settlement_id)->value('refund_status'));
    }

    public function test_admin_reply_appears_in_both_vendor_inboxes_with_independent_read_receipts(): void
    {
        $post = $this->post(); $this->quote($post, 2); $this->admin(false);
        Admin::reply($post, 2, 'Admin mediation: please confirm the corrected pickup.');
        $ownerInbox = $this->workspace(1)->inbox(Request::create('/'))->getData(true)['data']['items'];
        $peerInbox = $this->workspace(2)->inbox(Request::create('/'))->getData(true)['data']['items'];
        $this->assertCount(1, $ownerInbox); $this->assertCount(1, $peerInbox);
        $this->assertSame(1, (int) $ownerInbox[0]['unread_count']);
        $this->assertSame(0, DB::table('partner_market_messages')->count()); // No vendor impersonation.
        $messageId = (int) DB::table('partner_market_admin_messages')->value('id');
        $this->workspace(2)->readMessages(Request::create('/', 'POST', ['peer_vendor_id' => 1, 'through_admin_id' => $messageId]), $post);
        $this->assertNull(DB::table('partner_market_admin_messages')->value('owner_read_at'));
        $this->assertNotNull(DB::table('partner_market_admin_messages')->value('peer_read_at'));
        $unrelated = $this->workspace(3)->inbox(Request::create('/'))->getData(true)['data']['items'];
        $this->assertSame([], $unrelated);
    }

    public function test_admin_migration_recovers_a_partial_run_without_resetting_existing_data(): void
    {
        $id = $this->capturedLedger(); $this->admin(); Admin::hold($id, 1, 'Existing hold must survive migration recovery');
        $auditCount = DB::table('partner_market_admin_audits')->count();
        // Isolated test DB: reproduce a failed final CREATE after earlier DDL succeeded.
        Schema::drop('partner_market_admin_messages');
        $migration = require database_path('migrations/2026_10_29_000003_add_partner_market_admin_resolution.php');
        $migration->up(); $migration->up();
        $this->assertTrue(Schema::hasTable('partner_market_admin_messages'));
        $this->assertSame($auditCount, DB::table('partner_market_admin_audits')->count());
        $row = DB::table('partner_market_secure_payments')->find($id);
        $this->assertTrue((bool) $row->admin_hold); $this->assertSame(2, (int) $row->admin_revision);
        $post = (int) $row->post_id;
        DB::table('partner_market_admin_messages')->insert(['post_id' => $post, 'peer_vendor_id' => 2, 'admin_user_id' => 991,
            'admin_name' => 'Test Admin', 'message' => 'Recovered migration', 'created_at' => now()]);
        $migration->up();
        $this->assertSame(1, DB::table('partner_market_admin_messages')->count());
    }

    public function test_admin_queue_filters_by_vendor_payment_and_the_selected_date_basis(): void
    {
        $this->admin();
        $first = $this->post(); $second = $this->post();
        DB::table('partner_market_posts')->where('id', $second)->update(['transporter_profile_id' => 2]);
        $payment = $this->capturedLedger(); Admin::hold($payment, 1, 'Queue test hold');
        $console = new class extends \App\Filament\Pages\PartnerMarketplaceConsole {
            public function listingData(): array { return $this->getViewData(); }
        };
        $console->vendorFilterId = 2;
        $data = $console->listingData();
        $this->assertSame(1, $data['posts']->total());
        $this->assertSame($second, (int) $data['posts']->items()[0]->id);
        $this->assertSame(0, $data['counts']['hold']);
        $console->vendorFilterId = null; $console->paymentFilter = 'hold';
        $data = $console->listingData();
        $this->assertSame(1, $data['posts']->total()); $this->assertSame(3, $data['counts']['all']);
        $console->paymentFilter = 'all'; $console->dateFrom = now()->addDay()->format('Y-m-d'); $console->dateTo = $console->dateFrom;
        $console->dateBasis = 'posted'; $this->assertSame(0, $console->listingData()['posts']->total());
        $console->dateBasis = 'pickup'; $this->assertSame(3, $console->listingData()['posts']->total());
        $console->dateFrom = '2026-02-31';
        $data = $console->listingData();
        $this->assertNotNull($data['filterError']); $this->assertSame(0, $data['posts']->total());
    }

    private function gatewayFor(string $orderId, string $paymentId, string $state = 'captured'): void
    {
        config(['services.razorpay.key' => 'rzp_test_key', 'services.razorpay.secret' => 'test_secret']);
        Http::fake([
            'api.razorpay.com/v1/orders/' . $orderId => Http::response(['id' => $orderId, 'amount' => 20055, 'amount_paid' => $state === 'captured' ? 20055 : 0, 'currency' => 'INR', 'status' => $state === 'captured' ? 'paid' : 'attempted']),
            'api.razorpay.com/v1/orders/' . $orderId . '/payments' => Http::response(['count' => 1, 'items' => [['id' => $paymentId, 'order_id' => $orderId, 'status' => $state]]]),
            'api.razorpay.com/v1/payments/' . $paymentId => Http::response(['id' => $paymentId, 'order_id' => $orderId, 'amount' => 20055, 'currency' => 'INR', 'status' => $state]),
            'api.razorpay.com/v1/payments/' . $paymentId . '/refund' => Http::response(['id' => 'rfnd_' . $paymentId, 'payment_id' => $paymentId, 'amount' => 20055, 'status' => 'processed']),
        ]);
    }

    public function test_reservation_is_exclusive_and_repeated_click_does_not_extend_deadline(): void
    {
        $post = $this->post(true); DB::table('partner_market_posts')->where('id', $post)->update(['pricing_mode' => 'fixed']);
        $first = $this->payment(2)->claim(Request::create('/', 'POST', ['expected_revision' => 1]), $post)->getData(true)['data']['payment_id'];
        $deadline = DB::table('partner_market_secure_payments')->where('id', $first)->value('reserved_until');
        $again = $this->payment(2)->claim(Request::create('/', 'POST', ['expected_revision' => 1]), $post)->getData(true)['data']['payment_id'];
        $this->assertEquals($first, $again);
        $this->assertSame($deadline, DB::table('partner_market_secure_payments')->where('id', $first)->value('reserved_until'));
        try { $this->payment(3)->claim(Request::create('/', 'POST', ['expected_revision' => 1]), $post); $this->fail('Second payer must wait.'); }
        catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
        $this->assertSame(1, DB::table('partner_market_secure_payments')->where('post_id', $post)->count());
        $other = Reservations::snapshot(DB::table('partner_market_posts')->find($post), 3);
        $this->assertTrue($other['busy']); $this->assertFalse($other['is_payer']); $this->assertNull($other['payment_id']);
        $this->assertLessThanOrEqual(120, $other['remaining_seconds']);
        try { $this->listing(1)->edit(Request::create('/', 'POST'), $post); $this->fail('Paying booking cannot edit.'); }
        catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
        try { $this->listing(1)->close(Request::create('/', 'POST'), $post); $this->fail('Paying booking cannot cancel.'); }
        catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
    }

    public function test_expired_unstarted_slot_releases_and_same_quote_gets_new_immutable_ledger(): void
    {
        $post = $this->post(true); $quote = $this->quote($post, 2);
        $first = $this->payment(1)->selectQuote(Request::create('/', 'POST', ['expected_revision' => 1]), $post, $quote)->getData(true)['data']['payment_id'];
        DB::table('partner_market_secure_payments')->where('id', $first)->update(['reserved_until' => now()->subSecond()]);
        Reservations::reconcile($post);
        $this->assertSame('open', DB::table('partner_market_posts')->where('id', $post)->value('status'));
        $second = $this->payment(1)->selectQuote(Request::create('/', 'POST', ['expected_revision' => 1]), $post, $quote)->getData(true)['data']['payment_id'];
        $this->assertNotEquals($first, $second); $this->assertSame(2, DB::table('partner_market_secure_payments')->where('quote_id', $quote)->count());
        $this->assertSame('failed', DB::table('partner_market_secure_payments')->where('id', $first)->value('status'));
    }

    public function test_authorized_or_unavailable_gateway_never_releases_slot_on_timer_only(): void
    {
        $post = $this->post(true); $quote = $this->quote($post, 2);
        $id = $this->payment(1)->selectQuote(Request::create('/', 'POST', ['expected_revision' => 1]), $post, $quote)->getData(true)['data']['payment_id'];
        DB::table('partner_market_secure_payments')->where('id', $id)->update(['reserved_until' => now()->subSecond(), 'razorpay_order_id' => 'order_wait']);
        $this->gatewayFor('order_wait', 'pay_wait', 'authorized'); Reservations::reconcile($post);
        $this->assertEquals($id, DB::table('partner_market_posts')->where('id', $post)->value('active_payment_id'));
        $this->assertSame('checking', DB::table('partner_market_secure_payments')->where('id', $id)->value('reservation_state'));
        DB::table('partner_market_secure_payments')->where('id', $id)->update(['gateway_checked_at' => now()->subSeconds(6)]);
        Http::fake(['*' => Http::response([], 503)]); Reservations::reconcile($post);
        $this->assertEquals($id, DB::table('partner_market_posts')->where('id', $post)->value('active_payment_id'));
    }

    public function test_capture_assigns_once_and_delayed_capture_refunds_without_stealing_new_slot(): void
    {
        $post = $this->post(true); $quote = $this->quote($post, 2); $other = $this->quote($post, 3);
        $first = $this->payment(1)->selectQuote(Request::create('/', 'POST', ['expected_revision' => 1]), $post, $quote)->getData(true)['data']['payment_id'];
        DB::table('partner_market_secure_payments')->where('id', $first)->update(['razorpay_order_id' => 'order_old', 'reserved_until' => now()->subSecond()]);
        $this->gatewayFor('order_old', 'pay_old', 'failed'); Reservations::reconcile($post);
        $second = $this->payment(1)->selectQuote(Request::create('/', 'POST', ['expected_revision' => 1]), $post, $other)->getData(true)['data']['payment_id'];
        $this->gatewayFor('order_old', 'pay_old');
        $this->assertSame('refunded', Reservations::capture((int) $first, 'order_old', 'pay_old'));
        $this->assertSame('refunded', Reservations::capture((int) $first, 'order_old', 'pay_old'));
        $this->assertEquals($second, DB::table('partner_market_posts')->where('id', $post)->value('active_payment_id'));
        $this->assertCount(1, Http::recorded(fn ($request) => str_ends_with($request->url(), '/refund')));
        DB::table('partner_market_secure_payments')->where('id', $second)->update(['razorpay_order_id' => 'order_winner']);
        $this->gatewayFor('order_winner', 'pay_winner');
        $this->assertSame('paid', Reservations::capture((int) $second, 'order_winner', 'pay_winner'));
        $this->assertSame('paid', Reservations::capture((int) $second, 'order_winner', 'pay_winner'));
        $this->assertSame('assigned', DB::table('partner_market_posts')->where('id', $post)->value('status'));
        $this->assertEquals($second, DB::table('partner_market_posts')->where('id', $post)->value('assigned_payment_id'));
        $this->assertSame(1, DB::table('partner_market_quotes')->where('post_id', $post)->where('status', 'accepted')->count());
    }

    public function test_paid_cancellation_marks_cancelled_and_initiates_full_refund_once(): void
    {
        $id = $this->capturedLedger(); $row = DB::table('partner_market_secure_payments')->find($id);
        config(['services.razorpay.key' => 'rzp_test_key', 'services.razorpay.secret' => 'test_secret']);
        Http::fake(['*/payments/pay_testcapture/refund' => Http::response(['id' => 'rfnd_cancel', 'payment_id' => 'pay_testcapture', 'amount' => 20055, 'status' => 'processed'])]);
        $controller = new class extends PartnerMarketplaceLifecycleController {
            public TransporterProfile $profile;
            protected function context(Request $request): array { return [$this->profile, 'vendor']; }
        };
        $controller->profile = $this->profile(1);
        $controller->cancel(Request::create('/', 'POST', ['reason' => 'Incorrect booking']), (int) $id);
        $controller->cancel(Request::create('/', 'POST'), (int) $id);
        $this->assertSame('cancelled', DB::table('partner_market_posts')->where('id', $row->post_id)->value('status'));
        $this->assertSame('refunded', DB::table('partner_market_secure_payments')->where('id', $id)->value('status'));
        Http::assertSentCount(1);
    }

    public function test_quote_post_does_not_require_or_keep_posting_fare_commission(): void
    {
        $post = $this->post();
        DB::table('partner_market_posts')->where('id', $post)->update(['pricing_mode' => 'quote']);
        $input = $this->editInput($post);
        unset($input['asking_amount'], $input['vendor_commission']);
        $this->listing(1)->edit(Request::create('/', 'POST', $input), $post);
        $row = DB::table('partner_market_posts')->find($post);
        $this->assertNull($row->asking_amount); $this->assertEquals(0, $row->vendor_commission); $this->assertFalse((bool) $row->secure_booking);
    }

    public function test_owner_request_from_chat_stores_terms_without_assigning_until_capture(): void
    {
        $post = $this->post();
        DB::table('partner_market_posts')->where('id', $post)->update(['pricing_mode' => 'quote', 'asking_amount' => null, 'vendor_commission' => 0]);
        DB::table('partner_market_messages')->insert(['post_id' => $post, 'sender_vendor_id' => 2, 'recipient_vendor_id' => 1, 'message' => 'Agreed in chat', 'created_at' => now(), 'updated_at' => now()]);
        $input = ['vendor_id' => 2, 'fleet_submission_id' => DB::table('partner_market_fleet_submissions')->where('post_id', $post)->where('vendor_id', 2)->value('id'), 'expected_revision' => 1, 'booking_amount' => '2700.50', 'commission' => '200.55'];
        try { $this->payment(3)->assignmentRequest(Request::create('/', 'POST', $input), $post); $this->fail('Non-owner request accepted'); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
        $response = $this->payment(1)->assignmentRequest(Request::create('/', 'POST', $input), $post)->getData(true);
        $id = (int) $response['data']['payment_id'];
        $row = DB::table('partner_market_secure_payments')->find($id);
        $this->assertEquals(270050, $row->booking_amount_paise); $this->assertEquals(20055, $row->commission_paise);
        $this->assertSame('payment_pending', DB::table('partner_market_posts')->where('id', $post)->value('status'));
        $this->assertSame('reserved', DB::table('partner_market_quotes')->where('post_id', $post)->where('transporter_profile_id', 2)->value('status'));
        $this->assertEquals($id, $this->payment(1)->assignmentRequest(Request::create('/', 'POST', $input), $post)->getData(true)['data']['payment_id']);
        $received = $this->workspace(2)->bookings(Request::create('/', 'GET', ['scope' => 'received']))->getData(true)['data'];
        $this->assertSame(1, $received['total']);
        $other = $this->workspace(3)->bookings(Request::create('/', 'GET', ['scope' => 'received']))->getData(true)['data'];
        $this->assertSame(0, $other['total']);
        $peers = $this->workspace(1)->details(Request::create('/'), $post)->getData(true)['data']['interested_vendors'];
        $this->assertEquals(2, $peers['items'][0]['id']);
        DB::table('partner_market_secure_payments')->where('id', $id)->update(['razorpay_order_id' => 'order_request']);
        $this->gatewayFor('order_request', 'pay_request');
        Reservations::capture($id, 'order_request', 'pay_request');
        $this->assertSame('assigned', DB::table('partner_market_posts')->where('id', $post)->value('status'));
    }

    public function test_legacy_quote_selection_cannot_skip_agreed_amount_and_commission(): void
    {
        $post = $this->post(); $quote = $this->quote($post, 2);
        DB::table('partner_market_posts')->where('id', $post)->update(['pricing_mode' => 'quote']);
        try { $this->payment(1)->selectQuote(Request::create('/', 'POST', ['expected_revision' => 1]), $post, $quote); $this->fail('Assignment bypassed payment request'); }
        catch (HttpException $e) { $this->assertSame(422, $e->getStatusCode()); }
        $this->assertSame('open', DB::table('partner_market_posts')->where('id', $post)->value('status'));
        $this->assertSame(0, DB::table('partner_market_secure_payments')->where('post_id', $post)->count());
    }

    public function test_missing_or_outdated_fleet_details_block_payment_and_non_secure_assignment(): void
    {
        foreach ([false, true] as $secure) {
            $post = $this->post($secure); $quote = $this->quote($post, 2);
            DB::table('partner_market_fleet_submissions')->where('post_id', $post)->where('vendor_id', 2)->delete();
            try { $this->payment(1)->selectQuote(Request::create('/', 'POST', ['expected_revision' => 1]), $post, $quote); $this->fail('Missing fleet details allowed assignment'); }
            catch (HttpException $e) { $this->assertSame(422, $e->getStatusCode()); }
            $this->assertSame('open', DB::table('partner_market_posts')->where('id', $post)->value('status'));
            $this->assertSame(0, DB::table('partner_market_secure_payments')->where('post_id', $post)->count());
            $this->fleetSnapshot($post, 2, 1);
            DB::table('partner_market_posts')->where('id', $post)->update(['revision' => 2]);
            DB::table('partner_market_quotes')->where('id', $quote)->update(['post_revision' => 2]);
            try { $this->payment(1)->selectQuote(Request::create('/', 'POST', ['expected_revision' => 2]), $post, $quote); $this->fail('Old revision fleet details allowed assignment'); }
            catch (HttpException $e) { $this->assertSame(422, $e->getStatusCode()); }
        }
    }

    public function test_shared_fleet_text_has_only_requested_fields_and_preserves_snapshot(): void
    {
        $text = \App\Services\PartnerMarketplaceFleet::text("Ravi\nKumar", 'Maruti Dzire', 'UP80AB1234');
        $this->assertSame("Interested in this booking.\nCar Photo: attached\nDriver Name: Ravi Kumar\nTaxi Name: Maruti Dzire\nTaxi Number: UP80AB1234", $text);
        $this->assertTrue(\App\Services\PartnerMarketplaceFleet::matches('Sedan', 'sedan', 'Maruti Dzire'));
        $this->assertFalse(\App\Services\PartnerMarketplaceFleet::matches('Innova Crysta', 'SUV', 'Maruti Ertiga'));
    }

    public function test_bank_save_is_encrypted_masked_versioned_and_owner_scoped(): void
    {
        $first = Banks::save($this->profile(1), 0, $this->bankInput());
        $this->assertStringNotContainsString('001234567890', $first->encrypted_details);
        $this->assertArrayNotHasKey('account_number', Banks::summary($first));
        $this->assertSame('•••• 7890', Banks::summary($first)['masked_account_number']);
        $this->assertNull(Banks::current(2));
        $this->assertEquals($first->id, Banks::save($this->profile(1), 0, $this->bankInput())->id);
        $input = $this->bankInput(); $input['replace_account_number'] = false; $input['bank_name'] = 'Changed Bank';
        unset($input['account_number'], $input['account_number_confirmation']);
        $second = Banks::save($this->profile(1), 1, $input);
        $this->assertEquals(2, $second->revision); $this->assertSame('7890', $second->last_four);
        $this->assertSame('Example Bank', Banks::summary(DB::table('partner_market_bank_accounts')->find($first->id))['bank_name']);
        try { Banks::save($this->profile(1), 1, $this->bankInput()); $this->fail('Stale bank change accepted'); }
        catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
    }

    public function test_bank_edit_cannot_redirect_an_existing_plan_and_moderator_cannot_reveal(): void
    {
        $id = $this->capturedLedger(); $this->admin();
        Admin::plan($id, 1, $this->splitInput());
        $ledger = DB::table('partner_market_secure_payments')->find($id);
        $plan = DB::table('partner_market_admin_settlements')->find($ledger->admin_settlement_id);
        $pinned = (int) $plan->payout_bank_account_id;
        $input = $this->bankInput(); $input['account_number'] = $input['account_number_confirmation'] = '009999998888';
        Banks::save($this->profile(1), 1, $input);
        $this->assertEquals($pinned, DB::table('partner_market_admin_settlements')->where('id', $plan->id)->value('payout_bank_account_id'));
        $this->assertSame('001234567890', Banks::revealForSettlement($plan, 1)['account_number']);
        $this->admin(false);
        try { Banks::revealForSettlement($plan, 1); $this->fail('Moderator saw full bank account'); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
    }

    public function test_account_confirmation_and_ifsc_are_checked_before_persisting(): void
    {
        foreach ([['account_number_confirmation' => '999999'], ['ifsc' => 'INVALID']] as $invalid) {
            try { Banks::save($this->profile(1), 0, array_replace($this->bankInput(), $invalid)); $this->fail('Invalid bank details saved'); }
            catch (ValidationException $expected) {}
            $this->assertNull(Banks::current(1));
        }
    }


    public function test_matching_outbox_gives_free_cabs_first_access_and_deduplicates(): void
    {
        \Illuminate\Support\Facades\Queue::fake(); config(['queue.default' => 'database']);
        Schema::create('vehicles', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('transporter_profile_id')->nullable(); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('car_classification'); $t->string('car_company_name'); $t->string('model_name'); $t->string('service_type');
            $t->boolean('is_active'); $t->boolean('is_verified'); $t->string('verification_status'); $t->dateTime('partner_removed_at')->nullable();
        });
        DB::table('vehicles')->insert(['id'=>20,'transporter_profile_id'=>2,'car_classification'=>'Sedan','car_company_name'=>'Maruti','model_name'=>'Dzire',
            'service_type'=>'taxi','is_active'=>true,'is_verified'=>true,'verification_status'=>'approved']);
        $id=$this->post(); DB::table('partner_market_posts')->where('id',$id)->update(['pickup_at'=>now()->addHours(2),'pickup_latitude'=>27.1767,'pickup_longitude'=>78.0081]);
        foreach([1,2,3] as $v) DB::table('partner_market_presence')->insert(['vendor_id'=>$v,'app_alerts'=>true,'gps_enabled'=>true,'latitude'=>27.1767,'longitude'=>78.0081,'accuracy'=>20,'located_at'=>now()]);
        DB::table('partner_market_free_cabs')->insert(['vehicle_id'=>20,'vendor_id'=>2,'is_free'=>true,'city'=>'Agra','latitude'=>27.1767,'longitude'=>78.0081,'available_from'=>now(),'available_until'=>now()->addHours(12)]);
        \App\Services\PartnerMarketplaceAlerts::plan($id); \App\Services\PartnerMarketplaceAlerts::plan($id);
        $post=DB::table('partner_market_posts')->find($id);
        $this->assertSame(2,DB::table('partner_market_alerts')->where('post_id',$id)->count());
        $free=DB::table('partner_market_alerts')->where('post_id',$id)->where('vendor_id',2)->first();
        $near=DB::table('partner_market_alerts')->where('post_id',$id)->where('vendor_id',3)->first();
        $this->assertEquals(0,$free->tier); $this->assertEquals(1,$near->tier);
        $this->assertSame($post->priority_until,$near->available_at);
        $this->assertTrue(\App\Services\PartnerMarketplaceAlerts::visible($post,1));
        $this->assertTrue(\App\Services\PartnerMarketplaceAlerts::visible($post,2));
        $this->assertFalse(\App\Services\PartnerMarketplaceAlerts::visible($post,3));
        DB::table('partner_market_posts')->where('id',$id)->update(['priority_until'=>now()->subSecond()]);
        $this->assertTrue(\App\Services\PartnerMarketplaceAlerts::visible(DB::table('partner_market_posts')->find($id),3));
        // Only the selected saved taxi is made busy after assignment.
        $submission=DB::table('partner_market_fleet_submissions')->where('post_id',$id)->where('vendor_id',2)->value('id');
        DB::table('partner_market_fleet_submissions')->where('id',$submission)->update(['vehicle_id'=>20]);
        \App\Services\PartnerMarketplaceAlerts::booked($post,2,$submission);
        $this->assertEquals(0,DB::table('partner_market_free_cabs')->where('vehicle_id',20)->value('is_free'));
    }

    public function test_alert_preferences_are_owned_revisioned_and_location_opt_in(): void
    {
        $controller=new class extends \App\Http\Controllers\Api\V1\PartnerMarketplacePresenceController {
            public TransporterProfile $profile;
            protected function context(Request $request): array { return [$this->profile,'vendor']; }
        };
        $controller->profile=$this->profile(2);
        $controller->save(Request::create('/','POST',['role'=>'vendor','vendor_id'=>1,'expected_revision'=>0,'app_alerts'=>true,'whatsapp_alerts'=>false,'gps_enabled'=>true]));
        $this->assertNull(DB::table('partner_market_presence')->where('vendor_id',1)->first());
        $controller->location(Request::create('/','POST',['latitude'=>27.1767,'longitude'=>78.0081,'accuracy'=>20,'captured_at'=>now()->toIso8601String()]));
        $this->assertNotNull(DB::table('partner_market_presence')->where('vendor_id',2)->value('located_at'));
        $controller->save(Request::create('/','POST',['expected_revision'=>1,'app_alerts'=>true,'whatsapp_alerts'=>false,'gps_enabled'=>false]));
        $this->assertNull(DB::table('partner_market_presence')->where('vendor_id',2)->value('latitude'));
        try {$controller->location(Request::create('/','POST',['latitude'=>27.1767,'longitude'=>78.0081,'accuracy'=>20,'captured_at'=>now()->toIso8601String()])); $this->fail('Location updated without opt in');}
        catch(HttpException $e){$this->assertSame(409,$e->getStatusCode());}
        try {$controller->save(Request::create('/','POST',['expected_revision'=>1,'app_alerts'=>false,'whatsapp_alerts'=>false,'gps_enabled'=>false])); $this->fail('Stale settings replaced latest revision');}
        catch(HttpException $e){$this->assertSame(409,$e->getStatusCode());}
        $token='fake-test-device-token-123456';
        $controller->device(Request::create('/','POST',['vendor_id'=>1,'token'=>$token,'platform'=>'android']));
        $device=DB::table('partner_market_devices')->first();
        $this->assertEquals(2,$device->vendor_id); $this->assertStringNotContainsString($token,$device->encrypted_token);
        $this->assertSame($token,\Illuminate\Support\Facades\Crypt::decryptString($device->encrypted_token));
    }
}
