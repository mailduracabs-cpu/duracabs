<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Uses its own SQLite in-memory connection, never the application database. */
class VendorJoiningDateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.connections.vendor_joining_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.default' => 'vendor_joining_test',
        ]);
        DB::purge('vendor_joining_test');
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('service_type');
            $table->string('partner_payout_mode');
            $table->decimal('partner_monthly_amount', 12, 2)->nullable();
            $table->decimal('partner_fixed_rate_per_24h', 12, 2)->nullable();
            $table->decimal('commission_percentage', 5, 2)->default(30);
            $table->decimal('daily_price', 12, 2)->default(6000);
            $table->date('first_registration_date')->nullable();
            $table->date('partner_agreement_expires_on')->nullable();
            $table->timestamps();
        });
        DB::table('vehicles')->insert(['id' => 1, 'service_type' => 'self_drive', 'partner_payout_mode' => 'monthly', 'partner_monthly_amount' => 30000, 'first_registration_date' => '2026-01-01']);
        (require database_path('migrations/2026_10_25_140000_add_vehicle_partner_joining_date.php'))->up();
        $this->useAdminAccess(true);
    }

    private function useAdminAccess(bool $admin): void
    {
        $user = new class extends User {
            public bool $payoutAdmin = false;
            public function canUseAdminLogin(): bool { return $this->payoutAdmin; }
        };
        $user->payoutAdmin = $admin;
        auth()->setUser($user);
    }

    protected function tearDown(): void
    {
        DB::disconnect('vendor_joining_test');
        parent::tearDown();
    }

    public function test_migration_does_not_invent_historical_joining_dates(): void
    {
        $this->assertNull(Vehicle::findOrFail(1)->partner_joining_date);
        $this->assertNull(Vehicle::findOrFail(1)->partnerMonthlySchedule());
    }

    public function test_date_survives_reopen_and_an_unrelated_save(): void
    {
        Vehicle::findOrFail(1)->update(['partner_joining_date' => '2026-10-15']);
        $vehicle = Vehicle::findOrFail(1);
        $this->assertSame('2026-10-15', $vehicle->partner_joining_date->format('Y-m-d'));
        $vehicle->update(['daily_price' => 7000]);
        $this->assertSame('2026-10-15', $vehicle->fresh()->partnerPayoutTerms()['joining_date']);
        $this->assertSame('2026-11-15', $vehicle->fresh()->partnerMonthlySchedule('2026-10-25')['next_billing_date']);
    }

    public function test_partner_cannot_change_admin_agreement_date(): void
    {
        Vehicle::findOrFail(1)->update(['partner_joining_date' => '2026-10-15']);
        $this->useAdminAccess(false);
        $this->expectException(ValidationException::class);
        Vehicle::findOrFail(1)->update(['partner_joining_date' => '2026-10-25']);
    }

    public function test_invalid_date_is_rejected_before_storage(): void
    {
        $this->expectException(ValidationException::class);
        Vehicle::findOrFail(1)->update(['partner_joining_date' => '2026-02-31']);
    }

    public function test_joining_date_can_be_saved_for_percentage_and_fixed_agreements(): void
    {
        $vehicle = Vehicle::findOrFail(1);
        $vehicle->update(['partner_payout_mode' => 'percentage', 'partner_joining_date' => '2026-10-15']);
        $this->assertSame('2026-10-15', $vehicle->fresh()->partnerPayoutTerms()['joining_date']);
        $this->assertNull($vehicle->fresh()->partnerMonthlySchedule());
        $vehicle->update(['partner_payout_mode' => 'fixed', 'partner_fixed_rate_per_24h' => 3000, 'partner_joining_date' => '2026-10-20']);
        $this->assertSame('2026-10-20', $vehicle->fresh()->partnerPayoutTerms()['joining_date']);
        $this->assertSame('3000.00', $vehicle->fresh()->partnerPayoutTerms()['fixed_rate_per_24h']);
        $this->assertNull($vehicle->fresh()->partnerMonthlySchedule());
    }
}
