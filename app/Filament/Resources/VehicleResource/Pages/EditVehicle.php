<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Actions\Media\ProcessMediaUpload;
use App\Enums\MediaType;
use App\Filament\Resources\VehicleResource;
use App\Models\AppMedia;
use App\Services\Media\MediaDeleteService;
use App\Services\Media\MediaUsageService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    /**
     * Newly uploaded media that must be attached to this vehicle.
     *
     * @var array<int, array{
     *     new_media_id:int,
     *     old_media_id:?int,
     *     usage_field:string,
     *     preferred_variant:string
     * }>
     */
    private array $pendingMediaChanges = [];

    /**
     * Temporary local upload paths.
     *
     * @var array<int, string>
     */
    private array $temporaryUploadPaths = [];

    /**
     * Newly created or reused media IDs.
     *
     * These are checked for cleanup when updating the vehicle fails.
     *
     * @var array<int, int>
     */
    private array $newMediaIds = [];

    /**
     * Old media IDs that may become unused after replacement.
     *
     * @var array<int, int>
     */
    private array $oldMediaIds = [];

    /**
     * Update vehicle details and process all selected media uploads.
     *
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(
        Model $record,
        array $data
    ): Model {
        $this->applyPartnerProtection(
            data: $data
        );

        $vehicleName = $this->resolveVehicleName(
            data: $data,
            record: $record,
        );

        try {
            /*
            |--------------------------------------------------------------------------
            | Vehicle Photos
            |--------------------------------------------------------------------------
            */

            $this->processUploadField(
                data: $data,
                record: $record,
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
                record: $record,
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
                record: $record,
                uploadField: 'left_side_upload',
                mediaIdField: 'left_side_media_id',
                legacyField: 'left_side_image',
                usageField: 'left_side_image',
                type: MediaType::Vehicle,
                module: 'vehicle-left-side',
                name: $vehicleName . ' Left Side Photo',
                isPublic: true,
                preferredVariant: 'medium',
            );

            $this->processUploadField(
                data: $data,
                record: $record,
                uploadField: 'right_side_upload',
                mediaIdField: 'right_side_media_id',
                legacyField: 'right_side_image',
                usageField: 'right_side_image',
                type: MediaType::Vehicle,
                module: 'vehicle-right-side',
                name: $vehicleName . ' Right Side Photo',
                isPublic: true,
                preferredVariant: 'medium',
            );

            $this->processUploadField(
                data: $data,
                record: $record,
                uploadField: 'front_left_upload',
                mediaIdField: 'front_left_media_id',
                legacyField: 'front_left_image',
                usageField: 'front_left_image',
                type: MediaType::Vehicle,
                module: 'vehicle-front-left',
                name: $vehicleName . ' Front Left Angle Photo',
                isPublic: true,
                preferredVariant: 'medium',
            );

            $this->processUploadField(
                data: $data,
                record: $record,
                uploadField: 'front_right_upload',
                mediaIdField: 'front_right_media_id',
                legacyField: 'front_right_image',
                usageField: 'front_right_image',
                type: MediaType::Vehicle,
                module: 'vehicle-front-right',
                name: $vehicleName . ' Front Right Angle Photo',
                isPublic: true,
                preferredVariant: 'medium',
            );

            $this->processUploadField(
                data: $data,
                record: $record,
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

            $this->processUploadField(
                data: $data,
                record: $record,
                uploadField: 'front_seats_upload',
                mediaIdField: 'front_seats_media_id',
                legacyField: 'front_seats_image',
                usageField: 'front_seats_image',
                type: MediaType::Vehicle,
                module: 'vehicle-front-seats',
                name: $vehicleName . ' Front Seats Photo',
                isPublic: true,
                preferredVariant: 'medium',
            );

            $this->processUploadField(
                data: $data,
                record: $record,
                uploadField: 'rear_seats_upload',
                mediaIdField: 'rear_seats_media_id',
                legacyField: 'rear_seats_image',
                usageField: 'rear_seats_image',
                type: MediaType::Vehicle,
                module: 'vehicle-rear-seats',
                name: $vehicleName . ' Rear Seats Photo',
                isPublic: true,
                preferredVariant: 'medium',
            );

            $this->processUploadField(
                data: $data,
                record: $record,
                uploadField: 'boot_upload',
                mediaIdField: 'boot_media_id',
                legacyField: 'boot_image',
                usageField: 'boot_image',
                type: MediaType::Vehicle,
                module: 'vehicle-boot',
                name: $vehicleName . ' Boot Luggage Photo',
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
                record: $record,
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
                record: $record,
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
                record: $record,
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

            DB::transaction(function () use (
                $record,
                $data
            ): void {
                $record->update($data);

                $this->synchronizeMediaUsages(
                    record: $record
                );
            });

            $this->deleteUnusedOldMedia();

            $this->deleteTemporaryUploads();

            return $record->refresh();
        } catch (Throwable $exception) {
            /*
             * Newly uploaded media is removed only when it has no usage.
             * Duplicate media reused from the library remains safe.
             */
            $this->cleanupUnusedNewMedia();

            Log::error(
                'Vehicle update with media failed.',
                [
                    'vehicle_id' => $record->getKey(),
                    'new_media_ids' => $this->newMediaIds,
                    'old_media_ids' => $this->oldMediaIds,
                    'exception' => $exception->getMessage(),
                ]
            );

            throw new RuntimeException(
                'Unable to update vehicle: '
                . $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    /**
     * Protect ownership and business workflow fields for Partner users.
     *
     * Media processing itself does not require approval.
     *
     * @param array<string, mixed> $data
     */
    private function applyPartnerProtection(
        array &$data
    ): void {
        if (!VehicleResource::isPartnerPanel()) {
            return;
        }

        $profileId =
            VehicleResource::getCurrentPartnerProfileId();

        if ($profileId === null) {
            throw new RuntimeException(
                'A transporter profile is required before updating a vehicle.'
            );
        }

        $data['user_id'] = auth()->id();

        $data['transporter_profile_id'] =
            $profileId;

        /*
         * Existing vehicle workflow values are preserved.
         * These rules do not affect image or document processing.
         */
        $data['verification_status'] =
            $this->record->verification_status;

        $data['is_active'] =
            $this->record->is_active;

        if (
            $this->record->verification_status
            === 'approved'
        ) {
            $data['vehicle_number'] =
                $this->record->vehicle_number;
        }
    }

    /**
     * Process one temporary upload.
     *
     * @param array<string, mixed> $data
     */
    private function processUploadField(
        array &$data,
        Model $record,
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
         * Temporary form fields must never be sent to the vehicles table.
         */
        unset(
            $data[$uploadField],
            $data[$originalNameField]
        );

        if ($temporaryPath === null) {
            return;
        }

        $localDisk = Storage::disk('local');

        if (!$localDisk->exists($temporaryPath)) {
            throw new RuntimeException(
                "Temporary upload not found for {$uploadField}: {$temporaryPath}"
            );
        }

        $oldMediaId = filled(
            $record->getAttribute($mediaIdField)
        )
            ? (int) $record->getAttribute(
                $mediaIdField
            )
            : null;

        /** @var ProcessMediaUpload $processor */
        $processor = app(
            ProcessMediaUpload::class
        );

        $newMedia = $processor->upload(
            file: $temporaryPath,
            mediaType: $type,
            module: $module,
            options: [
                'source_disk' => 'local',

                /*
                 * Delete only after the whole vehicle update succeeds.
                 */
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

                    'vehicle_id' =>
                        $record->getKey(),

                    'vehicle_media_field' =>
                        $usageField,

                    'is_vehicle_document' =>
                        $type->isDocument(),

                    'preferred_variant' =>
                        $preferredVariant,

                    'replaced_media_id' =>
                        $oldMediaId,
                ],
            ],
        );

        $data[$mediaIdField] =
            $newMedia->id;

        /*
        |--------------------------------------------------------------------------
        | Legacy compatibility
        |--------------------------------------------------------------------------
        |
        | Old website/API fields remain populated while the project migrates
        | to app_media_id fields.
        |
        */

        $data[$legacyField] =
            $type->isDocument()
                ? $newMedia->original_path
                : (
                    $newMedia->medium_path
                    ?: $newMedia->large_path
                    ?: $newMedia->original_path
                );

        $this->pendingMediaChanges[] = [
            'new_media_id' => $newMedia->id,
            'old_media_id' => $oldMediaId,
            'usage_field' => $usageField,
            'preferred_variant' =>
                $preferredVariant,
        ];

        $this->newMediaIds[] =
            $newMedia->id;

        if (
            $oldMediaId !== null
            && $oldMediaId !== $newMedia->id
        ) {
            $this->oldMediaIds[] =
                $oldMediaId;
        }

        $this->temporaryUploadPaths[] =
            $temporaryPath;
    }

    /**
     * Attach new media and detach only this vehicle's old usage.
     */
    private function synchronizeMediaUsages(
        Model $record
    ): void {
        if ($this->pendingMediaChanges === []) {
            return;
        }

        /** @var MediaUsageService $usageService */
        $usageService = app(
            MediaUsageService::class
        );

        foreach (
            $this->pendingMediaChanges as $change
        ) {
            $newMedia = AppMedia::query()
                ->find($change['new_media_id']);

            if (!$newMedia instanceof AppMedia) {
                throw new RuntimeException(
                    'The newly uploaded media record could not be found.'
                );
            }

            $usageService->attach(
                media: $newMedia,
                owner: $record,
                fieldName:
                    $change['usage_field'],
                preferredVariant:
                    $change['preferred_variant'],
                metadata: [
                    'resource' =>
                        VehicleResource::class,

                    'attached_from' =>
                        VehicleResource::isPartnerPanel()
                            ? 'partner_vehicle_edit'
                            : 'admin_vehicle_edit',

                    'attached_at' =>
                        now()->toIso8601String(),
                ],
            );

            $oldMediaId =
                $change['old_media_id'];

            if (
                $oldMediaId === null
                || $oldMediaId ===
                    $newMedia->id
            ) {
                continue;
            }

            $oldMedia = AppMedia::query()
                ->find($oldMediaId);

            if (!$oldMedia instanceof AppMedia) {
                continue;
            }

            /*
             * Only this vehicle field is detached.
             * Other records sharing the same duplicate media remain safe.
             */
            $usageService->detach(
                media: $oldMedia,
                owner: $record,
                fieldName:
                    $change['usage_field'],
            );
        }
    }

    /**
     * Permanently remove replaced media when no record uses it.
     */
    private function deleteUnusedOldMedia(): void
    {
        if ($this->oldMediaIds === []) {
            return;
        }

        /** @var MediaDeleteService $deleteService */
        $deleteService = app(
            MediaDeleteService::class
        );

        foreach (
            array_unique($this->oldMediaIds)
            as $mediaId
        ) {
            $media = AppMedia::withTrashed()
                ->find($mediaId);

            if (!$media instanceof AppMedia) {
                continue;
            }

            try {
                $deleteService->deleteIfUnused(
                    media: $media,
                    force: true,
                );
            } catch (Throwable $exception) {
                Log::warning(
                    'Unable to remove replaced vehicle media.',
                    [
                        'vehicle_id' =>
                            $this->record->getKey(),

                        'media_id' => $mediaId,

                        'exception' =>
                            $exception->getMessage(),
                    ]
                );
            }
        }
    }

    /**
     * Clean newly uploaded media after a failed vehicle update.
     */
    private function cleanupUnusedNewMedia(): void
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

            if (!$media instanceof AppMedia) {
                continue;
            }

            try {
                $deleteService->deleteIfUnused(
                    media: $media,
                    force: true,
                );
            } catch (Throwable $exception) {
                Log::warning(
                    'Unable to clean unused new vehicle media after update failure.',
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
     * Delete local temporary files after successful update.
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
     * Filament FileUpload state may be a string or keyed array.
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
     * Generate readable media names.
     *
     * @param array<string, mixed> $data
     */
    private function resolveVehicleName(
        array $data,
        Model $record
    ): string {
        $brand = trim(
            (string) (
                $data['car_company_name']
                ?? $record->getAttribute(
                    'car_company_name'
                )
                ?? ''
            )
        );

        $model = trim(
            (string) (
                $data['model_name']
                ?? $record->getAttribute(
                    'model_name'
                )
                ?? ''
            )
        );

        $number = trim(
            (string) (
                $data['vehicle_number']
                ?? $record->getAttribute(
                    'vehicle_number'
                )
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

    protected function afterSave(): void
    {
        if (VehicleResource::isPartnerPanel()) {
            Notification::make()
                ->title('Vehicle Updated Successfully')
                ->success()
                ->body(
                    'Vehicle details, images and documents were updated successfully.'
                )
                ->send();

            return;
        }

        Notification::make()
            ->title('Vehicle Updated Successfully')
            ->success()
            ->body(
                'Vehicle files were processed and replaced automatically.'
            )
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(
                    fn (): bool =>
                        !VehicleResource::isPartnerPanel()
                        || $this->record
                            ->verification_status
                            !== 'approved'
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'index'
        );
    }
}