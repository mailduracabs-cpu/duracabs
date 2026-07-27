<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fleets') || ! Schema::hasTable('vehicles')) {
            return;
        }

        if (! Schema::hasColumn('vehicles', 'legacy_fleet_id')) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->unsignedBigInteger('legacy_fleet_id')
                    ->nullable()
                    ->unique()
                    ->after('id');
            });
        }

        $fleets = DB::table('fleets')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        foreach ($fleets as $fleet) {
            $alreadyMigrated = DB::table('vehicles')
                ->where('legacy_fleet_id', $fleet->id)
                ->exists();

            if ($alreadyMigrated) {
                continue;
            }

            $profile = null;

            if (! empty($fleet->transporter_profile_id)
                && Schema::hasTable('fleet_transporter_profiles')) {
                $profile = DB::table('fleet_transporter_profiles')
                    ->where('id', $fleet->transporter_profile_id)
                    ->first();
            }

            $vehicleNumber = $this->nullableString($fleet->vehicle_number ?? null);

            // Avoid creating a duplicate when the same registered vehicle
            // already exists in the vehicles table.
            if ($vehicleNumber !== null) {
                $existingVehicleId = DB::table('vehicles')
                    ->where('vehicle_number', $vehicleNumber)
                    ->value('id');

                if ($existingVehicleId) {
                    DB::table('vehicles')
                        ->where('id', $existingVehicleId)
                        ->update([
                            'legacy_fleet_id' => $fleet->id,
                            'updated_at' => now(),
                        ]);

                    continue;
                }
            }

            $vehicleType = strtolower(
                $this->nullableString($fleet->vehicle_type ?? null) ?: 'car'
            );

            if (! in_array($vehicleType, ['car', 'bike', 'scooter'], true)) {
                $vehicleType = 'car';
            }

            $name = $this->nullableString($fleet->name ?? null);
            $modelSimilar = $this->nullableString($fleet->model_similar ?? null);

            $seats = $this->firstInteger($fleet->passenger_capacity ?? null);
            $bags = $this->firstInteger($fleet->luggage_capacity ?? null);

            $hourlyPrice = $this->money($fleet->hour_charge ?? 0);
            $weeklyPrice = $this->money($fleet->weekly_charge ?? 0);
            $monthlyPrice = $this->money($fleet->monthly_charge ?? 0);
            $securityDeposit = $this->money($fleet->security_deposit ?? 0);
            $freeKm = $this->money($fleet->range_km ?? 0);
            $extraKmRate = $this->money($fleet->km_charge ?? 0);

            $status = strtolower(
                $this->nullableString($fleet->status ?? null) ?: 'inactive'
            );

            $isActive = $status === 'active';

            $payload = [
                'legacy_fleet_id' => $fleet->id,
                'service_type' => $vehicleType === 'car'
                    ? 'self_drive'
                    : 'bike_rental',
                'vehicle_type' => $vehicleType,
                'transporter_profile_id' => $fleet->transporter_profile_id,
                'user_id' => $profile->user_id ?? null,
                'vehicle_number' => $vehicleNumber,
                'chassis_number' => $this->nullableString($fleet->chassis_number ?? null),
                'engine_number' => $this->nullableString($fleet->engine_number ?? null),
                'owner_name' => $this->nullableString($fleet->owner_name ?? null),
                'car_company_name' => $name,
                'model_name' => $modelSimilar ?: $name,
                'fuel_type' => $this->nullableString($fleet->fuel_type ?? null),
                'transmission' => $this->nullableString($fleet->transmission ?? null),
                'seats' => $seats,
                'bags' => $bags,
                'car_color' => $this->nullableString($fleet->car_color ?? null),
                'hourly_price' => $hourlyPrice,
                'weekly_price' => $weeklyPrice,
                'monthly_price' => $monthlyPrice,
                'security_deposit' => $securityDeposit,
                'free_km' => $freeKm,
                'extra_km_rate' => $extraKmRate,
                'front_image' => $this->firstNonEmpty([
                    $fleet->photo_front ?? null,
                    $fleet->image ?? null,
                ]),
                'back_image' => $this->nullableString($fleet->photo_back ?? null),
                'interior_image' => $this->nullableString($fleet->photo_interior ?? null),
                'rc_image' => $this->nullableString($fleet->rc_image ?? null),
                'insurance_image' => $this->nullableString($fleet->insurance_image ?? null),
                'polution_image' => $this->nullableString($fleet->pollution_image ?? null),
                'minimum_booking_hours' => 1,
                'is_active' => $isActive,
                'is_live' => $isActive,
                'is_verified' => false,
                'verification_status' => 'pending',
                'created_at' => $fleet->created_at ?? now(),
                'updated_at' => now(),
            ];

            $payload = $this->onlyExistingVehicleColumns($payload);

            DB::table('vehicles')->insert($payload);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')
            || ! Schema::hasColumn('vehicles', 'legacy_fleet_id')) {
            return;
        }

        DB::table('vehicles')
            ->whereNotNull('legacy_fleet_id')
            ->delete();

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropColumn('legacy_fleet_id');
        });
    }

    private function onlyExistingVehicleColumns(array $payload): array
    {
        return collect($payload)
            ->filter(
                fn (mixed $value, string $column): bool =>
                    Schema::hasColumn('vehicles', $column)
            )
            ->all();
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function firstNonEmpty(array $values): ?string
    {
        foreach ($values as $value) {
            $value = $this->nullableString($value);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function firstInteger(mixed $value): ?int
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        preg_match('/\d+/', $value, $matches);

        if (empty($matches[0])) {
            return null;
        }

        return max(0, (int) $matches[0]);
    }

    private function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return round(max(0, (float) $value), 2);
    }
};
