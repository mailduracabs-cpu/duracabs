<?php

namespace Tests\Feature;

use App\Filament\Resources\VehicleResource\Pages\EditVehicle;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Dedicated in-memory connection: does not migrate or reset the application's database. */
class VendorPayoutModesPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.connections.vendor_modes_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.default' => 'vendor_modes_test',
        ]);
        DB::purge('vendor_modes_test');
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('service_type');
            $table->string('partner_payout_mode')->nullable();
            $table->decimal('commission_percentage', 5, 2)->default(30);
            foreach (['hourly_price', 'daily_price', 'weekly_price', 'monthly_price', 'security_deposit'] as $field) {
                $table->decimal($field, 12, 2)->default(0);
            }
            $table->timestamps();
        });
        DB::table('vehicles')->insert([
            ['id' => 1, 'service_type' => 'self_drive', 'partner_payout_mode' => null],
            ['id' => 2, 'service_type' => 'self_drive', 'partner_payout_mode' => 'fixed'],
            ['id' => 3, 'service_type' => 'taxi', 'partner_payout_mode' => null],
        ]);
        (require database_path('migrations/2026_10_25_120000_add_monthly_vendor_payout_mode.php'))->up();
        $this->useAdminAccess(true);
    }

    protected function tearDown(): void
    {
        DB::disconnect('vendor_modes_test');
        parent::tearDown();
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

    public function test_migration_only_fills_blank_self_drive_modes(): void
    {
        $this->assertSame('percentage', DB::table('vehicles')->where('id', 1)->value('partner_payout_mode'));
        $this->assertSame('fixed', DB::table('vehicles')->where('id', 2)->value('partner_payout_mode'));
        $this->assertNull(DB::table('vehicles')->where('id', 3)->value('partner_payout_mode'));
    }

    public function test_blank_edit_record_has_a_real_form_value_and_does_not_replace_valid_modes(): void
    {
        $hook = new \ReflectionMethod(EditVehicle::class, 'mutateFormDataBeforeFill');
        foreach ([[null, 'percentage'], ['', 'percentage'], ['fixed', 'fixed'], ['monthly', 'monthly']] as [$mode, $expected]) {
            $data = $hook->invoke(new EditVehicle(), ['service_type' => 'self_drive', 'partner_payout_mode' => $mode]);
            $this->assertSame($expected, $data['partner_payout_mode']);
        }
    }

    public function test_each_agreement_survives_save_reopen_and_a_second_save(): void
    {
        foreach ([
            ['partner_payout_mode' => 'percentage', 'commission_percentage' => 70],
            ['partner_payout_mode' => 'fixed', 'partner_fixed_rate_per_24h' => 3000],
            ['partner_payout_mode' => 'monthly', 'partner_monthly_amount' => 60000],
        ] as $terms) {
            $vehicle = Vehicle::findOrFail(1);
            $vehicle->update($terms);
            $fresh = $vehicle->fresh();
            $this->assertSame($terms['partner_payout_mode'], $fresh->partner_payout_mode);
            $fresh->daily_price = (float) $fresh->daily_price + 1;
            $fresh->save();
            $this->assertSame($terms['partner_payout_mode'], $fresh->fresh()->partner_payout_mode);
            foreach ($terms as $field => $value) {
                $this->assertEquals($value, $fresh->fresh()->getAttribute($field));
            }
        }
        $this->assertSame('60000.00', Vehicle::findOrFail(1)->partner_monthly_amount);
    }

    public function test_a_post_migration_blank_value_is_persisted_on_save(): void
    {
        DB::table('vehicles')->where('id', 1)->update(['partner_payout_mode' => null]);
        $vehicle = Vehicle::findOrFail(1);
        $vehicle->daily_price = 1000;
        $vehicle->save();
        $this->assertSame('percentage', $vehicle->fresh()->partner_payout_mode);
    }

    public function test_partner_cannot_change_monthly_agreement(): void
    {
        Vehicle::findOrFail(1)->update(['partner_payout_mode' => 'monthly', 'partner_monthly_amount' => 60000]);
        $this->useAdminAccess(false);
        $this->expectException(ValidationException::class);
        Vehicle::findOrFail(1)->update(['partner_monthly_amount' => 10000]);
    }

    public function test_partner_cannot_change_customer_prices_on_monthly_agreement(): void
    {
        Vehicle::findOrFail(1)->update(['partner_payout_mode' => 'monthly', 'partner_monthly_amount' => 60000]);
        $this->useAdminAccess(false);
        $this->expectException(ValidationException::class);
        Vehicle::findOrFail(1)->update(['daily_price' => 5]);
    }

    public function test_invalid_mode_cannot_silently_fall_back_to_percentage(): void
    {
        $this->expectException(ValidationException::class);
        Vehicle::findOrFail(1)->update(['partner_payout_mode' => 'unknown']);
    }
}
