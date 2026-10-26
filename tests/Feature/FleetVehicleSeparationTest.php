<?php

namespace Tests\Feature;

use App\Filament\Resources\BikeRentalFleetResource;
use App\Filament\Resources\SelfDriveFleetResource;
use App\Filament\Resources\VehicleResource;
use App\Filament\Resources\WithDriverFleetResource;
use App\Models\Vehicle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FleetVehicleSeparationTest extends TestCase
{
    private string $originalConnection;
    private string $testConnection = 'fleet_separation_test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.' . $this->testConnection, [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::setDefaultConnection($this->testConnection);
        Schema::clearResolvedInstance('db.schema');
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('service_type');
            $table->string('vehicle_type');
            $table->string('vehicle_number');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('transporter_profile_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_live')->default(true);
            $table->boolean('is_verified')->default(true);
            $table->string('verification_status')->default('approved');
            $table->softDeletes();
            $table->timestamps();
        });
        DB::table('vehicles')->insert([
            ['id' => 1, 'service_type' => Vehicle::SERVICE_SELF_DRIVE,
                'vehicle_type' => Vehicle::TYPE_CAR, 'vehicle_number' => 'UP80AA1001'],
            ['id' => 2, 'service_type' => Vehicle::SERVICE_TAXI,
                'vehicle_type' => Vehicle::TYPE_CAR, 'vehicle_number' => 'UP80AA1002'],
            ['id' => 3, 'service_type' => Vehicle::SERVICE_BIKE_RENTAL,
                'vehicle_type' => Vehicle::TYPE_BIKE, 'vehicle_number' => 'UP80AA1003'],
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge($this->testConnection);
        DB::setDefaultConnection($this->originalConnection);
        Schema::clearResolvedInstance('db.schema');
        parent::tearDown();
    }

    public static function fleets(): array
    {
        return [
            'self drive' => [SelfDriveFleetResource::class, 1],
            'with driver' => [WithDriverFleetResource::class, 2],
            'bike rental' => [BikeRentalFleetResource::class, 3],
        ];
    }

    #[DataProvider('fleets')]
    public function test_list_and_direct_record_lookup_exclude_other_fleets(string $resource, int $expected): void
    {
        $ids = $resource::getEloquentQuery()->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertSame([$expected], $ids);
        foreach ([1, 2, 3] as $id) {
            $record = $resource::getEloquentQuery()->setEagerLoads([])->find($id);
            if ($id === $expected) {
                $this->assertNotNull($record);
            } else {
                $this->assertNull($record, 'A direct edit lookup must not cross the fleet scope.');
            }
        }
    }

    public function test_legacy_resource_keeps_all_existing_vehicles(): void
    {
        $this->assertSame(3, VehicleResource::getEloquentQuery()->count());
        $this->assertSame(3, DB::table('vehicles')->count());
    }

    #[DataProvider('fleets')]
    public function test_conflicting_service_filter_cannot_show_another_fleet(string $resource, int $expected): void
    {
        $otherService = $expected === 2 ? Vehicle::SERVICE_SELF_DRIVE : Vehicle::SERVICE_TAXI;
        $this->assertSame(0, $resource::getEloquentQuery()->where('service_type', $otherService)->count());
    }
}
