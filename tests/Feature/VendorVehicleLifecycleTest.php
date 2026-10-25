<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\PartnerController;
use App\Models\FleetManagement\TransporterProfile;
use App\Models\Vehicle;
use App\Services\VehicleAgreementPolicy;
use App\Services\VendorLifecycleDelivery;
use App\Services\VendorMonthlyBilling;
use App\Services\VendorVehicleLifecycle;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Independent SQLite schema; no RefreshDatabase or production migration reset. */
class VendorVehicleLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-25 10:00:00');
        CarbonImmutable::setTestNow('2026-11-25 10:00:00');
        config([
            'database.connections.vendor_lifecycle_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.default' => 'vendor_lifecycle_test', 'vendor_lifecycle.email_enabled' => true,
            'vendor_lifecycle.whatsapp_enabled' => false, 'vendor_lifecycle.billing_basis' => 'joining_date',
            'vendor_lifecycle.partial_period' => 'prorate', 'vendor_lifecycle.billing_since' => '2026-11-25',
            'vendor_lifecycle.billing_day' => 15, 'vendor_lifecycle.reminder_since' => '2026-11-01',
            'vendor_lifecycle.agreement_reminder_days' => [30, 0], 'vendor_lifecycle.document_reminder_days' => [30, 15, 7, 1, 0],
        ]);
        DB::purge('vendor_lifecycle_test');
        Schema::create('vehicles', function (Blueprint $t): void {
            $t->id(); $t->string('vehicle_number')->nullable(); $t->string('service_type');
            $t->unsignedBigInteger('transporter_profile_id')->nullable();
            $t->string('partner_payout_mode')->nullable(); $t->decimal('partner_monthly_amount', 12, 2)->nullable();
            $t->decimal('commission_percentage', 5, 2)->default(30); $t->decimal('partner_fixed_rate_per_24h', 12, 2)->nullable();
            $t->boolean('is_active')->default(true); $t->boolean('is_live')->default(true);
            $t->string('verification_status')->default('approved'); $t->boolean('is_verified')->default(true);
            $t->timestamp('partner_removed_at')->nullable(); $t->timestamps();
        });
        (require database_path('migrations/2026_10_25_140000_add_vehicle_partner_joining_date.php'))->up();
        (require database_path('migrations/2026_10_25_160000_create_vendor_vehicle_lifecycle.php'))->up();
        Schema::create('fleet_transporter_profiles', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->string('company_name')->nullable();
            $t->string('contact_person')->nullable(); $t->string('email')->nullable(); $t->string('mobile')->nullable();
            $t->string('whatsapp_number')->nullable(); $t->softDeletes();
        });
        Schema::create('users', function (Blueprint $t): void { $t->id(); $t->string('email')->nullable(); });
        DB::table('fleet_transporter_profiles')->insert(['id' => 1, 'email' => 'vendor@example.test', 'company_name' => 'Vendor One']);
        DB::table('vehicles')->insert([
            'id' => 1, 'vehicle_number' => 'UP80AB1234', 'vehicle_registration_key' => 'UP80AB1234',
            'service_type' => 'self_drive', 'transporter_profile_id' => 1, 'partner_payout_mode' => 'monthly',
            'partner_monthly_amount' => 30000, 'first_registration_date' => '2025-10-25',
            'partner_joining_date' => '2026-10-25', 'partner_agreement_expires_on' => '2029-10-25',
        ]);
        // Keep admin permission-table fixtures out of this suite; real queue and delivery remain exercised.
        $delivery = \Mockery::mock(VendorLifecycleDelivery::class)->makePartial();
        $delivery->shouldReceive('admins')->andReturnNull();
        $this->app->instance(VendorLifecycleDelivery::class, $delivery);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); CarbonImmutable::setTestNow();
        DB::disconnect('vendor_lifecycle_test');
        parent::tearDown();
    }

    public function test_exact_three_year_age_is_allowed_but_one_day_older_is_rejected(): void
    {
        $vehicle = Vehicle::findOrFail(1);
        $vehicle->first_registration_date = '2023-10-25';
        VehicleAgreementPolicy::validate($vehicle);
        $this->assertSame('2029-10-25', $vehicle->partner_agreement_expires_on->toDateString());
        $vehicle->first_registration_date = '2023-10-24';
        $this->expectException(ValidationException::class);
        VehicleAgreementPolicy::validate($vehicle);
    }

    public function test_custom_six_month_expiry_is_preserved(): void
    {
        $vehicle = Vehicle::findOrFail(1);
        $vehicle->partner_agreement_expires_on = '2027-04-25';
        VehicleAgreementPolicy::validate($vehicle);
        $this->assertSame('2027-04-25', $vehicle->partner_agreement_expires_on->toDateString());
    }

    public function test_expiry_cannot_exceed_three_years_after_joining(): void
    {
        $vehicle = Vehicle::findOrFail(1);
        $vehicle->partner_agreement_expires_on = '2029-10-26';
        $this->expectException(ValidationException::class);
        VehicleAgreementPolicy::validate($vehicle);
    }

    public function test_duplicate_number_is_caught_even_with_a_different_vendor(): void
    {
        $vehicle = new Vehicle();
        $vehicle->forceFill(['service_type' => 'self_drive', 'vehicle_number' => 'up80-ab 1234', 'transporter_profile_id' => 2]);
        $this->expectException(ValidationException::class);
        VehicleAgreementPolicy::validate($vehicle);
    }

    public function test_database_unique_key_also_blocks_concurrent_duplicate_inserts(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('vehicles')->insert(['vehicle_number' => 'UP80 AB1234', 'vehicle_registration_key' => 'UP80AB1234', 'service_type' => 'self_drive']);
    }

    public function test_expiry_hides_vehicle_without_touching_booking_tables(): void
    {
        DB::table('vehicles')->where('id', 1)->update(['partner_agreement_expires_on' => '2026-11-25']);
        $this->assertTrue(Vehicle::findOrFail(1)->attachmentIsExpired());
        $this->assertSame(0, Vehicle::findOrFail(1)->attachmentDaysRemaining());
        $this->assertSame(0, Vehicle::query()->availableForCustomer()->count());
        $result = app(VendorVehicleLifecycle::class)->run();
        $this->assertSame(1, $result['expired_vehicles']);
        $this->assertFalse(Vehicle::findOrFail(1)->is_live);
        // No bookings table exists in this fixture: any attempt to cancel trips fails this test.
    }

    public function test_duplicate_bill_generation_creates_one_bill_and_one_email(): void
    {
        $service = app(VendorMonthlyBilling::class);
        $this->assertSame(1, $service->generateForVehicle(1));
        $this->assertSame(0, $service->generateForVehicle(1));
        $this->assertEquals(30000, DB::table('vendor_monthly_bills')->value('amount'));
        $this->assertSame(1, DB::table('vendor_monthly_bills')->count());
        $this->assertSame(1, DB::table('vendor_delivery_logs')->where('channel', 'email')->count());
    }

    public function test_fixed_day_proration_uses_actual_calendar_days(): void
    {
        config(['vendor_lifecycle.billing_basis' => 'fixed_day', 'vendor_lifecycle.billing_since' => '2026-11-01']);
        app(VendorMonthlyBilling::class)->generateForVehicle(1);
        $this->assertEquals('20322.58', DB::table('vendor_monthly_bills')->value('amount')); // 30000 * 21 / 31
    }

    public function test_bill_cutoff_prevents_accidental_historical_backfill(): void
    {
        config(['vendor_lifecycle.billing_since' => '2026-12-01']);
        $this->assertSame(0, app(VendorMonthlyBilling::class)->generateForVehicle(1));
        $this->assertSame(0, DB::table('vendor_monthly_bills')->count());
    }

    public function test_changing_agreement_dates_cannot_bill_an_overlapping_period_twice(): void
    {
        app(VendorMonthlyBilling::class)->generateForVehicle(1);
        config(['vendor_lifecycle.billing_since' => '2026-11-01']);
        DB::table('vehicles')->where('id', 1)->update(['partner_joining_date' => '2026-10-20']);
        $this->expectException(ValidationException::class);
        app(VendorMonthlyBilling::class)->generateForVehicle(1);
    }

    public function test_reminder_queue_is_deduplicated_and_renewed_expiry_cancels_pending_message(): void
    {
        DB::table('vehicles')->where('id', 1)->update(['partner_agreement_expires_on' => '2026-12-25']);
        app(VendorVehicleLifecycle::class)->run();
        app(VendorVehicleLifecycle::class)->run();
        $this->assertSame(1, DB::table('vendor_delivery_logs')->count());
        DB::table('vehicles')->where('id', 1)->update(['partner_agreement_expires_on' => '2027-04-25']);
        Mail::shouldReceive('raw')->never();
        app(VendorLifecycleDelivery::class)->dispatchPending();
        $this->assertSame('cancelled', DB::table('vendor_delivery_logs')->value('status'));
    }

    public function test_successful_delivery_is_not_sent_again(): void
    {
        $delivery = app(VendorLifecycleDelivery::class);
        $delivery->queue('test-success', 'email', 'vendor@example.test', ['title' => 'Reminder', 'body' => 'Renew car']);
        Mail::shouldReceive('raw')->once()->andReturnNull();
        $this->assertSame(1, $delivery->dispatchPending());
        $this->assertSame(0, $delivery->dispatchPending());
        $this->assertSame('sent', DB::table('vendor_delivery_logs')->value('status'));
    }

    public function test_ambiguous_transport_failure_requires_review_instead_of_resending(): void
    {
        $delivery = app(VendorLifecycleDelivery::class);
        $delivery->queue('test-timeout', 'email', 'vendor@example.test', ['title' => 'Reminder', 'body' => 'Renew car']);
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('SMTP timeout'));
        $this->assertSame(0, $delivery->dispatchPending());
        $this->assertSame('review', DB::table('vendor_delivery_logs')->value('status'));
        $this->assertSame(0, $delivery->dispatchPending(true));
    }

    public function test_partner_cannot_read_another_vendor_bill(): void
    {
        app(VendorMonthlyBilling::class)->generateForVehicle(1);
        $controller = new class extends PartnerController {
            protected function context(Request $request): array
            {
                return [(new TransporterProfile())->setRawAttributes(['id' => 2], true), 'host'];
            }
        };
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->monthlyBill(Request::create('/'), (int) DB::table('vendor_monthly_bills')->value('id'));
    }
    public function test_bill_paid_confirmation_is_idempotent_and_does_not_transfer_money(): void
    {
        app(VendorMonthlyBilling::class)->generateForVehicle(1);
        $actor = new class extends \App\Models\User {
            public function canUseAdminLogin(): bool { return true; }
        };
        $actor->id = 99;
        $billId = (int) DB::table('vendor_monthly_bills')->value('id');
        $service = app(\App\Services\VendorBillSettlement::class);
        $service->markPaid($billId, $actor, 'UTR-ONE');
        $service->markPaid($billId, $actor, 'SECOND-CLICK');
        $row = DB::table('vendor_monthly_bills')->first();
        $this->assertSame('paid', $row->status);
        $this->assertSame('UTR-ONE', $row->payment_reference);
        $this->assertEquals(99, $row->paid_by);
    }

    public function test_bill_download_link_requires_valid_unexpired_signature(): void
    {
        config(['session.driver' => 'array', 'cache.default' => 'array']);
        $mock = \Mockery::mock(VendorMonthlyBilling::class);
        $mock->shouldReceive('billCopy')->once()->with(1)->andReturn([
            'bytes' => 'test bill', 'filename' => 'test.pdf', 'mime' => 'application/pdf',
        ]);
        $this->app->instance(VendorMonthlyBilling::class, $mock);
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('vendor-lifecycle.shared-bill', now()->addMinutes(2), ['bill' => 1]);
        $this->get($url)->assertOk()->assertSee('test bill');
        $this->get(str_replace('/1/download', '/2/download', $url))->assertForbidden();
        Carbon::setTestNow('2026-11-25 10:03:00');
        CarbonImmutable::setTestNow('2026-11-25 10:03:00');
        $this->get($url)->assertForbidden();
    }
}
