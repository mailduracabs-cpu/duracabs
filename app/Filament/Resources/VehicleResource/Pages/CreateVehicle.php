<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Actions\Media\ProcessMediaUpload;
use App\Enums\MediaType;
use App\Filament\Resources\VehicleResource;
use App\Models\AppMedia;
use App\Models\Vehicle;
use App\Services\Media\MediaDeleteService;
use App\Services\Media\MediaUsageService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;

    /**
     * Media uploaded before the vehicle record is created.
     *
     * @var array<int, array{
     *     media_id:int,
     *     usage_field:string,
     *     preferred_variant:string
     * }>
     */
    private array $pendingMedia = [];

    /**
     * Newly created or reused media IDs.
     *
     * @var array<int, int>
     */
    private array $newMediaIds = [];

    /**
     * Temporary upload paths that should be deleted after success.
     *
     * @var array<int, string>
     */
    private array $temporaryUploadPaths = [];

    /**
     * Create vehicle, process media and attach media usages.
     *
     * @param array<string, mixed> $data
     */
    protected function handleRecordCreation(
        array $data
    ): Model {
        $this->applyOwnershipAndDefaults($data);

        $vehicleName = $this->resolveVehicleName($data);

        try {
            /*
            |--------------------------------------------------------------------------
            | Vehicle Photos
            |--------------------------------------------------------------------------
            */

            $this->processUploadField(
                data: $data,
                uploadField: 'front_upload',
                mediaIdField: 'front_media_id',
                legacyField: 'front_image',
                usageField: 'front_image',
                type: MediaType::Vehicle,
                module: 'vehicle-front',
                name: $vehicleName . ' Front Photo',
                isPublic: true,
                preferredVariant: 'medium',
            );

            $this->processUploadField(
                data: $data,
                uploadField: 'back_upload',
                mediaIdField: 'back_media_id',
                legacyField: 'back_image',
                usageField: 'back_image',
                type: MediaType::Vehicle,
                module: 'vehicle-back',
                name: $vehicleName . ' Back Photo',
                isPublic: true,
                preferredVariant: 'medium',
            );

            $this->processUploadField(
                data: $data,
                uploadField: 'interior_upload',
                mediaIdField: 'interior_media_id',
                legacyField: 'interior_image',
                usageField: 'interior_image',
                type: MediaType::Vehicle,
                module: 'vehicle-interior',
                name: $vehicleName . ' Interior Photo',
                isPublic: true,
                preferredVariant: 'medium',
            );

            /*
            |--------------------------------------------------------------------------
            | Vehicle Documents
            |--------------------------------------------------------------------------
            */

            $this->processUploadField(
                data: $data,
                uploadField: 'rc_upload',
                mediaIdField: 'rc_media_id',
                legacyField: 'rc_image',
                usageField: 'rc_image',
                type: MediaType::Document,
                module: 'vehicle-rc',
                name: $vehicleName . ' RC Document',
                isPublic: false,
                preferredVariant: 'original',
            );

            $this->processUploadField(
                data: $data,
                uploadField: 'insurance_upload',
                mediaIdField: 'insurance_media_id',
                legacyField: 'insurance_image',
                usageField: 'insurance_image',
                type: MediaType::Document,
                module: 'vehicle-insurance',
                name: $vehicleName . ' Insurance Document',
                isPublic: false,
                preferredVariant: 'original',
            );

            $this->processUploadField(
                data: $data,
                uploadField: 'pollution_upload',
                mediaIdField: 'pollution_media_id',
                legacyField: 'polution_image',
                usageField: 'polution_image',
                type: MediaType::Document,
                module: 'vehicle-puc',
                name: $vehicleName . ' Pollution Document',
                isPublic: false,
                preferredVariant: 'original',
            );

            /** @var Vehicle $vehicle */
            $vehicle = DB::transaction(
                function () use ($data): Vehicle {
                    /** @var Vehicle $vehicle */
                    $vehicle = static::getModel()::create($data);

                    $this->attachMediaUsages($vehicle);

                    return $vehicle;
                }
            );

            $this->deleteTemporaryUploads();

            return $vehicle->refresh();
        } catch (Throwable $exception) {
            $this->cleanupUnusedMedia();

            Log::error(
                'Vehicle creation with media failed.',
                [
                    'service_type' =>
                        $data['service_type'] ?? null,

                    'vehicle_number' =>
                        $data['vehicle_number'] ?? null,

                    'media_ids' =>
                        $this->newMediaIds,

                    'exception' =>
                        $exception->getMessage(),
                ]
            );

            throw new RuntimeException(
                'Unable to create vehicle: '
                . $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    /**
     * Apply ownership protection and service-specific defaults.
     *
     * @param array<string, mixed> $data
     */
    private function applyOwnershipAndDefaults(
        array &$data
    ): void {
        if (VehicleResource::isPartnerPanel()) {
            $profileId =
                VehicleResource::getCurrentPartnerProfileId();

            if ($profileId === null) {
                throw new RuntimeException(
                    'Please complete the transporter profile before adding a vehicle.'
                );
            }

            $data['user_id'] = auth()->id();

            $data['transporter_profile_id'] =
                $profileId;

            /*
             * Partner cannot approve or verify own vehicle.
             */
            $data['verification_status'] =
                Vehicle::STATUS_PENDING;

            $data['is_active'] = true;
            $data['is_live'] = false;
            $data['is_verified'] = false;
        }

        $serviceType = $data['service_type']
            ?? Vehicle::SERVICE_SELF_DRIVE;

        $data['service_type'] = $serviceType;

        if (
            $serviceType === Vehicle::SERVICE_BIKE_RENTAL
        ) {
            $this->applyBikeRentalDefaults($data);

            return;
        }

        if (
            $serviceType === Vehicle::SERVICE_SELF_DRIVE
        ) {
            $this->applySelfDriveDefaults($data);

            return;
        }

        $this->applyTaxiDefaults($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyBikeRentalDefaults(
        array &$data
    ): void {
        if (
            ! in_array(
                $data['vehicle_type'] ?? null,
                [
                    Vehicle::TYPE_BIKE,
                    Vehicle::TYPE_SCOOTER,
                ],
                true
            )
        ) {
            $data['vehicle_type'] =
                Vehicle::TYPE_BIKE;
        }

        $data['seats'] = max(
            1,
            (int) ($data['seats'] ?? 2)
        );

        $data['bags'] = 0;

        $data['security_deposit'] =
            (float) ($data['security_deposit'] ?? 0) > 0
                ? $data['security_deposit']
                : 2000;

        $data['minimum_booking_hours'] = max(
            1,
            (int) (
                $data['minimum_booking_hours']
                ?? 1
            )
        );

        $helmetAvailable =
            (bool) ($data['helmet_available'] ?? false);

        $data['helmet_available'] =
            $helmetAvailable;

        if (! $helmetAvailable) {
            $data['included_helmets'] = 0;
            $data['maximum_helmets'] = 0;
            $data['helmet_charge'] = 0;

            return;
        }

        $includedHelmets = max(
            0,
            min(
                2,
                (int) (
                    $data['included_helmets']
                    ?? 0
                )
            )
        );

        $maximumHelmets = max(
            $includedHelmets,
            min(
                2,
                (int) (
                    $data['maximum_helmets']
                    ?? 2
                )
            )
        );

        $data['included_helmets'] =
            $includedHelmets;

        $data['maximum_helmets'] =
            $maximumHelmets;

        $data['helmet_charge'] =
            (float) ($data['helmet_charge'] ?? 0) > 0
                ? $data['helmet_charge']
                : 100;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applySelfDriveDefaults(
        array &$data
    ): void {
        $data['vehicle_type'] =
            Vehicle::TYPE_CAR;

        $data['minimum_booking_hours'] = max(
            1,
            (int) (
                $data['minimum_booking_hours']
                ?? 24
            )
        );

        $data['security_deposit'] =
            (float) ($data['security_deposit'] ?? 0) > 0
                ? $data['security_deposit']
                : 5000;

        $this->clearBikeFields($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyTaxiDefaults(
        array &$data
    ): void {
        $data['service_type'] =
            Vehicle::SERVICE_TAXI;

        $data['vehicle_type'] =
            Vehicle::TYPE_CAR;

        /*
         * Taxi fare existing route-pricing system se aayega.
         */
        $data['hourly_price'] = 0;
        $data['daily_price'] = 0;
        $data['weekly_price'] = 0;
        $data['monthly_price'] = 0;
        $data['security_deposit'] = 0;
        $data['free_km'] = 0;
        $data['extra_km_rate'] = 0;
        $data['extra_hour_rate'] = 0;

        $data['is_live'] = false;

        $this->clearBikeFields($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function clearBikeFields(
        array &$data
    ): void {
        $data['bike_category'] = null;
        $data['engine_cc'] = null;
        $data['gear_type'] = null;
        $data['helmet_available'] = false;
        $data['included_helmets'] = 0;
        $data['maximum_helmets'] = 0;
        $data['helmet_charge'] = 0;
        $data['fuel_capacity'] = null;
        $data['mileage'] = null;
    }

    /**
     * Process one temporary upload.
     *
     * @param array<string, mixed> $data
     */
    private function processUploadField(
        array &$data,
        string $uploadField,
        string $mediaIdField,
        string $legacyField,
        string $usageField,
        MediaType $type,
        string $module,
        string $name,
        bool $isPublic,
        string $preferredVariant,
    ): void {
        $originalNameField =
            $uploadField . '_original_name';

        $temporaryPath = $this->extractSingleValue(
            $data[$uploadField] ?? null
        );

        $originalName = $this->extractSingleValue(
            $data[$originalNameField] ?? null
        );

        /*
         * Temporary form fields vehicles table me nahi jayenge.
         */
        unset(
            $data[$uploadField],
            $data[$originalNameField]
        );

        if ($temporaryPath === null) {
            return;
        }

        $localDisk = Storage::disk('local');

        if (! $localDisk->exists($temporaryPath)) {
            throw new RuntimeException(
                "Temporary upload not found for {$uploadField}: {$temporaryPath}"
            );
        }

        /** @var ProcessMediaUpload $processor */
        $processor = app(
            ProcessMediaUpload::class
        );

        $media = $processor->upload(
            file: $temporaryPath,
            mediaType: $type,
            module: $module,
            options: [
                'source_disk' => 'local',
                'cleanup_source' => false,

                'disk' => $isPublic
                    ? 'public'
                    : 'local',

                'is_public' => $isPublic,

                'name' => $name,
                'alt_text' => $name,
                'original_name' => $originalName,

                'uploaded_by' => auth()->id(),

                'uploader_type' =>
                    VehicleResource::isPartnerPanel()
                        ? 'vendor'
                        : 'admin',

                'upload_source' =>
                    VehicleResource::isPartnerPanel()
                        ? 'vendor_panel'
                        : 'admin_panel',

                'metadata' => [
                    'resource' =>
                        VehicleResource::class,

                    'vehicle_media_field' =>
                        $usageField,

                    'is_vehicle_document' =>
                        $type->isDocument(),

                    'preferred_variant' =>
                        $preferredVariant,

                    'service_type' =>
                        $data['service_type'] ?? null,
                ],
            ],
        );

        $data[$mediaIdField] = $media->id;

        /*
         * Legacy fields existing website/API compatibility ke liye.
         */
        $data[$legacyField] =
            $type->isDocument()
                ? $media->original_path
                : (
                    $media->medium_path
                    ?: $media->large_path
                    ?: $media->original_path
                );

        $this->pendingMedia[] = [
            'media_id' => $media->id,
            'usage_field' => $usageField,
            'preferred_variant' =>
                $preferredVariant,
        ];

        $this->newMediaIds[] =
            $media->id;

        $this->temporaryUploadPaths[] =
            $temporaryPath;
    }

    /**
     * Attach media usage entries after vehicle creation.
     */
    private function attachMediaUsages(
        Vehicle $vehicle
    ): void {
        if ($this->pendingMedia === []) {
            return;
        }

        /** @var MediaUsageService $usageService */
        $usageService = app(
            MediaUsageService::class
        );

        foreach ($this->pendingMedia as $item) {
            $media = AppMedia::query()
                ->find($item['media_id']);

            if (! $media instanceof AppMedia) {
                throw new RuntimeException(
                    'Uploaded media record could not be found.'
                );
            }

            $usageService->attach(
                media: $media,
                owner: $vehicle,
                fieldName: $item['usage_field'],
                preferredVariant:
                    $item['preferred_variant'],
                metadata: [
                    'resource' =>
                        VehicleResource::class,

                    'vehicle_id' =>
                        $vehicle->getKey(),

                    'service_type' =>
                        $vehicle->service_type,

                    'attached_from' =>
                        VehicleResource::isPartnerPanel()
                            ? 'partner_vehicle_create'
                            : 'admin_vehicle_create',

                    'attached_at' =>
                        now()->toIso8601String(),
                ],
            );
        }
    }

    /**
     * Remove media created during a failed request when unused.
     */
    private function cleanupUnusedMedia(): void
    {
        if ($this->newMediaIds === []) {
            return;
        }

        /** @var MediaDeleteService $deleteService */
        $deleteService = app(
            MediaDeleteService::class
        );

        foreach (
            array_unique($this->newMediaIds)
            as $mediaId
        ) {
            $media = AppMedia::withTrashed()
                ->find($mediaId);

            if (! $media instanceof AppMedia) {
                continue;
            }

            try {
                $deleteService->deleteIfUnused(
                    media: $media,
                    force: true,
                );
            } catch (Throwable $exception) {
                Log::warning(
                    'Unable to clean failed vehicle media.',
                    [
                        'media_id' => $mediaId,

                        'exception' =>
                            $exception->getMessage(),
                    ]
                );
            }
        }
    }

    /**
     * Delete local temporary uploads after successful creation.
     */
    private function deleteTemporaryUploads(): void
    {
        if ($this->temporaryUploadPaths === []) {
            return;
        }

        $disk = Storage::disk('local');

        foreach (
            array_unique(
                $this->temporaryUploadPaths
            ) as $path
        ) {
            try {
                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
            } catch (Throwable $exception) {
                Log::warning(
                    'Unable to delete temporary vehicle upload.',
                    [
                        'path' => $path,

                        'exception' =>
                            $exception->getMessage(),
                    ]
                );
            }
        }

        $this->temporaryUploadPaths = [];
    }

    /**
     * Filament upload state string ya keyed array ho sakti hai.
     */
    private function extractSingleValue(
        mixed $state
    ): ?string {
        if (is_string($state)) {
            $value = trim($state);

            return $value !== ''
                ? $value
                : null;
        }

        if (is_array($state)) {
            foreach ($state as $value) {
                if (
                    is_string($value)
                    && trim($value) !== ''
                ) {
                    return trim($value);
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveVehicleName(
        array $data
    ): string {
        $brand = trim(
            (string) (
                $data['car_company_name']
                ?? ''
            )
        );

        $model = trim(
            (string) (
                $data['model_name']
                ?? ''
            )
        );

        $number = trim(
            (string) (
                $data['vehicle_number']
                ?? ''
            )
        );

        $name = trim(
            "{$brand} {$model}"
        );

        if ($name === '') {
            $name = 'Vehicle';
        }

        if ($number !== '') {
            $name .= " ({$number})";
        }

        return $name;
    }

    protected function afterCreate(): void
    {
        /** @var Vehicle|null $vehicle */
        $vehicle = $this->record;

        $serviceName = $vehicle instanceof Vehicle
            ? $vehicle->service_type_label
            : 'Vehicle';

        if (VehicleResource::isPartnerPanel()) {
            Notification::make()
                ->title(
                    "{$serviceName} Submitted Successfully"
                )
                ->success()
                ->body(
                    'Your vehicle, images and documents were submitted successfully. It will be visible to customers after Admin approval.'
                )
                ->send();

            return;
        }

        Notification::make()
            ->title(
                "{$serviceName} Created Successfully"
            )
            ->success()
            ->body(
                'Vehicle images and documents were processed successfully.'
            )
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'index'
        );
    }
}