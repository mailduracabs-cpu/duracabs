<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\WalletRepository;
use App\Services\BookingPaymentCheckoutService;
use App\Services\CustomerReferralService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;
/** Isolated SQLite only; no RefreshDatabase and no production migrations. */
class CustomerWalletReferralTest extends TestCase {
    private WalletRepository $repo;
    protected function setUp(): void {
        parent::setUp();
        config(['database.connections.wallet_referral_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'database.default' => 'wallet_referral_test', 'customer_referrals.enabled' => true]);
        DB::purge('wallet_referral_test');
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name')->nullable(); $t->timestamps(); });
        foreach (['2026_07_01_000001_create_wallets_table.php', '2026_07_01_000002_create_wallet_transactions_table.php', '2026_10_24_120000_create_customer_referrals.php'] as $file) {
            (require database_path('migrations/' . $file))->up();
        }
        Schema::create('orders', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->decimal('grand_total', 12, 2)->default(1000); $t->decimal('paid_amount', 12, 2)->default(0);
            $t->string('status')->default('new'); $t->string('payment_status')->default('pending'); $t->decimal('refund_amount', 12, 2)->default(0); $t->timestamps();
        });
        Schema::create('self_drive_bookings', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('customer_id'); $t->decimal('grand_total', 12, 2)->default(1000); $t->decimal('paid_amount', 12, 2)->default(0);
            $t->string('status')->default('new'); $t->string('payment_status')->default('pending'); $t->decimal('refund_amount', 12, 2)->default(0); $t->timestamps();
        });
        Schema::create('booking_payment_checkouts', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('customer_id'); $t->string('source'); $t->text('payload'); $t->decimal('amount', 12, 2); $t->decimal('total', 12, 2);
            $t->string('payment_type'); $t->string('state'); $t->string('gateway_order_id')->nullable(); $t->string('gateway_payment_id')->nullable(); $t->unsignedBigInteger('booking_id')->nullable(); $t->timestamps();
        });
        $this->repo = app(WalletRepository::class);
    }
    protected function tearDown(): void { DB::disconnect('wallet_referral_test'); parent::tearDown(); }
    private function user(int $id): User {
        DB::table('users')->insert(['id' => $id, 'name' => 'Test', 'created_at' => now(), 'updated_at' => now()]);
        return (new User())->setRawAttributes(['id' => $id, 'created_at' => now()->toDateTimeString()], true);
    }
    private function service(bool $fail = false): BookingPaymentCheckoutService {
        return new class($fail) extends BookingPaymentCheckoutService {
            public function __construct(private bool $fail) {}
            protected function persistPaidBooking(object $row, float $received, string $paymentId, string $method): array {
                $table = $row->source === 'self_drive' ? 'self_drive_bookings' : 'orders';
                $key = $row->source === 'self_drive' ? 'customer_id' : 'user_id';
                $id = DB::table($table)->insertGetId([$key => $row->customer_id, 'paid_amount' => $received, 'created_at' => now(), 'updated_at' => now()]);
                if ($this->fail) throw new RuntimeException('Simulated booking persistence failure');
                DB::table('booking_payment_checkouts')->where('id', $row->id)->update(['booking_id' => $id, 'gateway_payment_id' => $paymentId, 'state' => 'completed']);
                $row->booking_id = $id; $row->gateway_payment_id = $paymentId;
                return $this->response($row, false);
            }
            protected function response(object $row, bool $duplicate): array { return ['booking_id' => $row->booking_id, 'booking_created' => true, 'duplicate' => $duplicate]; }
        };
    }
    public function test_held_money_cannot_be_spent_and_invalid_amounts_are_rejected(): void {
        $this->user(1); $this->repo->credit(1, 1000); Wallet::query()->where('user_id', 1)->update(['hold_balance' => 900]);
        $this->assertFalse($this->repo->debit(1, 101));
        $this->assertEquals(1000, $this->repo->balance(1)['balance']);
        $this->expectException(\InvalidArgumentException::class); $this->repo->debit(1, -10);
    }
    public function test_wallet_and_booking_rollback_together_when_save_fails(): void {
        $user = $this->user(1); $this->repo->credit(1, 1000);
        $service = $this->service(true); $checkout = $service->stage(1, 'order', [], 1000, 'advance', 500);
        try { $service->payWithWallet(-$checkout->id, $user, 500); $this->fail('Expected failure'); } catch (RuntimeException $e) { $this->assertStringContainsString('Simulated', $e->getMessage()); }
        $this->assertEquals(1000, $this->repo->balance(1)['balance']);
        $this->assertSame(0, DB::table('orders')->count()); $this->assertSame(0, DB::table('wallet_transactions')->where('type', 'debit')->count());
        $this->assertNull(DB::table('booking_payment_checkouts')->value('booking_id'));
    }
    public function test_each_booking_source_debits_only_once_on_checkout_retry(): void {
        $user = $this->user(1); $this->repo->credit(1, 2000); $service = $this->service();
        foreach (['order', 'self_drive'] as $source) {
            $checkout = $service->stage(1, $source, [], 1000, 'advance', 500);
            $first = $service->payWithWallet(-$checkout->id, $user, 500);
            $retry = $service->payWithWallet(-$checkout->id, $user, 500);
            $this->assertSame($first['booking_id'], $retry['booking_id']); $this->assertTrue($retry['duplicate']);
        }
        $this->assertEquals(1000, $this->repo->balance(1)['balance']);
        $this->assertSame(2, DB::table('wallet_transactions')->where('type', 'debit')->count());
    }
    public function test_insufficient_balance_or_tampered_amount_never_creates_a_booking(): void {
        $user = $this->user(1); $this->repo->credit(1, 100); $service = $this->service(); $checkout = $service->stage(1, 'self_drive', [], 1000, 'advance', 500);
        foreach ([1, 500] as $amount) {
            try { $service->payWithWallet(-$checkout->id, $user, $amount); $this->fail('Expected rejection'); }
            catch (RuntimeException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->assertEquals(100, $this->repo->balance(1)['balance']);
        $this->assertSame(0, DB::table('self_drive_bookings')->count());
    }
    public function test_pending_or_cancelled_booking_cannot_earn_reward(): void {
        $a = $this->user(1); $b = $this->user(2); $service = app(CustomerReferralService::class);
        $service->apply($b, $service->summary($a)['code']);
        $id = DB::table('orders')->insertGetId(['user_id' => 2, 'grand_total' => 1000, 'paid_amount' => 500, 'status' => 'completed', 'payment_status' => 'partial', 'created_at' => now(), 'updated_at' => now()]);
        $service->syncBooking('order', $id);
        DB::table('orders')->where('id', $id)->update(['paid_amount' => 1000, 'payment_status' => 'paid', 'status' => 'cancelled']);
        $service->syncBooking('order', $id);
        $this->assertSame(0, DB::table('customer_referral_rewards')->count());
    }
    public function test_other_customer_cannot_pay_a_checkout(): void {
        $this->user(1); $other = $this->user(2); $service = $this->service(); $checkout = $service->stage(1, 'order', [], 1000, 'advance', 500);
        $this->expectException(RuntimeException::class); $service->payWithWallet(-$checkout->id, $other, 500);
    }
    public function test_gateway_attempt_cannot_switch_to_wallet(): void {
        $user = $this->user(1); $this->repo->credit(1, 1000); $service = $this->service(); $checkout = $service->stage(1, 'order', [], 1000, 'advance', 500);
        DB::table('booking_payment_checkouts')->where('id', $checkout->id)->update(['gateway_order_id' => 'order_in_flight']);
        try { $service->payWithWallet(-$checkout->id, $user, 500); $this->fail('Expected failure'); } catch (RuntimeException $e) { $this->assertStringContainsString('fresh checkout', $e->getMessage()); }
        $this->assertEquals(1000, $this->repo->balance(1)['balance']);
    }
    public function test_referrer_gets_100_per_completed_paid_booking_once_and_refunds_reverse_once(): void {
        $referrer = $this->user(1); $customer = $this->user(2); $service = app(CustomerReferralService::class);
        $code = $service->summary($referrer)['code']; $service->apply($customer, $code);
        foreach (['order' => ['orders', 'user_id'], 'self_drive' => ['self_drive_bookings', 'customer_id']] as $source => [$table, $key]) {
            $id = DB::table($table)->insertGetId([$key => 2, 'grand_total' => 1000, 'paid_amount' => 1000, 'status' => 'completed', 'payment_status' => 'paid', 'created_at' => now(), 'updated_at' => now()]);
            $service->syncBooking($source, $id); $service->syncBooking($source, $id);
        }
        $this->assertEquals(200, $this->repo->balance(1)['balance']);
        DB::table('orders')->where('id', 1)->update(['refund_amount' => 100]);
        $service->syncBooking('order', 1); $service->syncBooking('order', 1);
        $this->assertEquals(100, $this->repo->balance(1)['balance']);
        $this->assertSame(2, DB::table('customer_referral_rewards')->count());
        $this->assertSame(1, DB::table('customer_referral_rewards')->where('status', 'reversed')->count());
    }
    public function test_customer_with_a_previous_booking_cannot_attach_a_referrer(): void {
        $referrer = $this->user(1); $customer = $this->user(2); $service = app(CustomerReferralService::class);
        $code = $service->summary($referrer)['code']; DB::table('orders')->insert(['user_id' => 2, 'created_at' => now(), 'updated_at' => now()]);
        $this->expectException(RuntimeException::class); $service->apply($customer, $code);
    }
    public function test_own_code_and_circular_referral_are_rejected(): void {
        $a = $this->user(1); $b = $this->user(2); $service = app(CustomerReferralService::class);
        $codeA = $service->summary($a)['code']; $codeB = $service->summary($b)['code'];
        try { $service->apply($a, $codeA); $this->fail('Expected self referral rejection'); } catch (RuntimeException $e) { $this->assertStringContainsString('own', $e->getMessage()); }
        $service->apply($b, $codeA); $this->expectException(RuntimeException::class); $service->apply($a, $codeB);
    }
}
