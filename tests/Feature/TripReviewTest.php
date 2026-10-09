<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\TripReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TripReviewTest extends TestCase
{
    use RefreshDatabase;
    private User $customer;
    private User $partner;
    private int $trip;

    protected function beforeRefreshingDatabase(): void
    {
        $database = (string) DB::connection()->getDatabaseName();
        if ($database !== ':memory:' && ! preg_match('/(?:_test|_testing|testing)(?:\.sqlite)?$/', $database)) {
            throw new \RuntimeException('Use an isolated _test / _testing database or SQLite :memory: for these tests.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::withoutEvents(fn () => User::forceCreate(['name' => 'Review customer', 'email' => 'review-customer@example.test', 'password' => Hash::make('testing-only'), 'mobile' => '9000000101']));
        $this->partner = User::withoutEvents(fn () => User::forceCreate(['name' => 'Review vendor', 'email' => 'review-vendor@example.test', 'password' => Hash::make('testing-only'), 'mobile' => '9000000102']));
        $this->trip = DB::table('orders')->insertGetId(['user_id' => $this->customer->id, 'transporter_id' => $this->partner->id,
            'booking_no' => 'REVIEW-TEST-1', 'status' => 'closed', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_only_real_participants_can_review(): void
    {
        $outsider = User::withoutEvents(fn () => User::forceCreate(['name' => 'Other', 'email' => 'review-other@example.test', 'password' => Hash::make('testing-only')]));
        $this->expectException(HttpException::class);
        app(TripReviewService::class)->submit($outsider, 'taxi', (string) $this->trip, 5, null);
    }

    public function test_pending_and_cancelled_trips_cannot_be_reviewed(): void
    {
        DB::table('orders')->where('id', $this->trip)->update(['status' => 'cancelled']);
        $this->expectException(HttpException::class);
        app(TripReviewService::class)->submit($this->customer, 'taxi', (string) $this->trip, 5, null);
    }

    public function test_both_directions_are_independent_and_any_one_star_blocks_subject(): void
    {
        $service = app(TripReviewService::class);
        $service->submit($this->customer, 'taxi', (string) $this->trip, 5, 'Helpful vendor');
        $service->submit($this->partner, 'taxi', (string) $this->trip, 1, 'Reported inappropriate behaviour');
        $this->assertFalse($service->blocked($this->partner->id));
        $this->assertTrue($service->blocked($this->customer->id));
        $this->assertSame(5.0, (float) $service->score($this->partner->id)['average']);
        $this->assertSame(2, DB::table('trip_reviews')->count());
    }

    public function test_duplicate_review_is_rejected_without_second_restriction(): void
    {
        $service = app(TripReviewService::class);
        $service->submit($this->customer, 'taxi', (string) $this->trip, 1, 'Bad behaviour');
        try { $service->submit($this->customer, 'taxi', (string) $this->trip, 1, 'Again'); $this->fail('Duplicate accepted'); }
        catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
        $this->assertSame(1, DB::table('trip_account_restrictions')->count());
    }

    public function test_non_admin_cannot_reactivate(): void
    {
        $this->expectException(HttpException::class);
        app(TripReviewService::class)->reactivate($this->customer, $this->partner->id, 'Please reactivate');
    }

    public function test_admin_reactivation_preserves_history_and_future_one_star_blocks_again(): void
    {
        $service = app(TripReviewService::class);
        $service->submit($this->customer, 'taxi', (string) $this->trip, 1, 'Bad behaviour');
        $admin = Mockery::mock(User::class)->makePartial();
        $admin->setRawAttributes($this->customer->getAttributes());
        $admin->shouldReceive('isAdmin')->andReturn(true);
        $service->reactivate($admin, $this->partner->id, 'Case reviewed and resolved');
        $this->assertFalse($service->blocked($this->partner->id));
        $this->assertSame(1, $service->score($this->partner->id)['count']);
        $second = DB::table('orders')->insertGetId(['user_id' => $this->customer->id, 'transporter_id' => $this->partner->id,
            'booking_no' => 'REVIEW-TEST-2', 'status' => 'closed', 'created_at' => now(), 'updated_at' => now()]);
        $service->submit($this->customer, 'taxi', (string) $second, 1, 'New case after resolution');
        $this->assertTrue($service->blocked($this->partner->id));
        $this->assertNotNull(DB::table('trip_account_restrictions')->whereNotNull('resolved_at')->value('resolution_note'));
    }

    public function test_blocked_vendor_cannot_attach_car_or_accept_booking(): void
    {
        app(TripReviewService::class)->submit($this->customer, 'taxi', (string) $this->trip, 1, 'Bad behaviour');
        \Laravel\Sanctum\Sanctum::actingAs($this->partner);
        $this->postJson('/api/v1/partner/vehicles', ['role' => 'vendor'])->assertStatus(403)->assertJson(['code' => 'ACCOUNT_REVIEW_BLOCKED']);
        $this->postJson('/api/v1/partner/bookings/'.$this->trip.'/accept', ['role' => 'vendor'])->assertStatus(403)->assertJson(['code' => 'ACCOUNT_REVIEW_BLOCKED']);
    }

    public function test_blocked_customer_api_booking_is_rejected_before_creation(): void
    {
        $service = app(TripReviewService::class);
        $service->submit($this->partner, 'taxi', (string) $this->trip, 1, 'Bad behaviour');
        \Laravel\Sanctum\Sanctum::actingAs($this->customer);
        $this->postJson('/api/v1/booking', ['user_id' => $this->customer->id])->assertStatus(403)->assertJson(['code' => 'ACCOUNT_REVIEW_BLOCKED']);
        $this->assertSame(1, DB::table('orders')->count());
    }
}
