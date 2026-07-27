<?php

namespace App\Filament\Resources\BannersResource\Pages;

use App\Actions\Media\ProcessMediaUpload;
use App\Actions\Media\ReplaceMedia;
use App\Filament\Resources\BannersResource;
use App\Models\AppMedia;
use App\Models\MediaUsage;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class EditBanners extends EditRecord
{
    protected static string $resource = BannersResource::class;

    /**
     * Update the banner and process its uploaded media.
     *
     * The media_upload field contains a temporary upload path.
     * It is processed through the Dura Media Engine and is never
     * stored directly in the banners table.
     *
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(
        Model $record,
        array $data,
    ): Model {
        $temporaryUpload = $this->extractTemporaryUpload(
            $data['media_upload'] ?? null,
        );

        unset($data['media_upload']);

        try {
            /*
             * Update normal banner fields first so the media metadata can use
             * the latest banner name, title and alternative text.
             */
            $record->fill($data);
            $record->save();

            if (blank($temporaryUpload)) {
                return $record->refresh();
            }

            $oldMedia = $this->resolveCurrentMedia($record);

            $mediaOptions = [
                'name' => $this->resolveMediaName($record, $data),

                'alt_text' => $this->resolveAlternativeText(
                    $record,
                    $data,
                ),

                'is_public' => true,

                'metadata' => [
                    'module' => 'banners',
                    'banner_id' => $record->getKey(),
                    'banner_title' => $record->getAttribute('title'),
                    'banner_name' => $record->getAttribute('name'),
                    'ride_type' => $record->getAttribute('ride_type'),
                    'updated_from' => static::class,
                ],
            ];

            if ($oldMedia instanceof AppMedia) {
                $newMedia = app(ReplaceMedia::class)->replaceForModel(
                    oldMedia: $oldMedia,
                    newFile: $temporaryUpload,
                    mediaType: \App\Enums\MediaType::Banner,
                    owner: $record,
                    fieldName: 'app_media_id',
                    module: 'banners',
                    preferredVariant: 'large',
                    options: $mediaOptions,
                );
            } else {
                $newMedia = $this->createAndAttachMedia(
                    record: $record,
                    temporaryUpload: $temporaryUpload,
                    options: $mediaOptions,
                );
            }

            /*
             * Store only the AppMedia reference in the banner record.
             */
            $record->forceFill([
                'app_media_id' => $newMedia->getKey(),
            ])->save();

            Notification::make()
                ->title('Banner updated')
                ->body('The banner image was processed successfully.')
                ->success()
                ->send();

            return $record->refresh();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Banner update failed')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            throw $exception;
        }
    }

    /**
     * Upload and attach media when the banner does not already have media.
     *
     * @param array<string, mixed> $options
     */
    private function createAndAttachMedia(
        Model $record,
        string $temporaryUpload,
        array $options,
    ): AppMedia {
        $media = app(ProcessMediaUpload::class)->upload(
            file: $temporaryUpload,
            mediaType: \App\Enums\MediaType::Banner,
            module: 'banners',
            options: $options,
        );

        DB::transaction(function () use ($media, $record): void {
            MediaUsage::query()->updateOrCreate(
                [
                    'app_media_id' => $media->getKey(),
                    'usable_type' => $record->getMorphClass(),
                    'usable_id' => $record->getKey(),
                    'field_name' => 'app_media_id',
                ],
                [
                    'preferred_variant' => 'large',
                    'metadata' => [
                        'attached_from' => static::class,
                        'attached_at' => now()->toIso8601String(),
                    ],
                ],
            );

            $referenceCount = MediaUsage::query()
                ->where('app_media_id', $media->getKey())
                ->count();

            $media->forceFill([
                'reference_count' => $referenceCount,
            ])->save();
        });

        return $media->refresh();
    }

    /**
     * Find the media currently connected to the banner.
     */
    private function resolveCurrentMedia(Model $record): ?AppMedia
    {
        $mediaId = $record->getAttribute('app_media_id');

        if (blank($mediaId)) {
            return null;
        }

        return AppMedia::query()->find($mediaId);
    }

    /**
     * Normalize Filament's upload state.
     *
     * FileUpload normally returns a string for a single upload, but this
     * method also safely supports an array-shaped state.
     */
    private function extractTemporaryUpload(mixed $upload): ?string
    {
        if (is_string($upload)) {
            $upload = trim($upload);

            return $upload !== '' ? $upload : null;
        }

        if (is_array($upload)) {
            foreach ($upload as $value) {
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        }

        return null;
    }

    /**
     * Resolve the name stored in AppMedia.
     *
     * @param array<string, mixed> $data
     */
    private function resolveMediaName(
        Model $record,
        array $data,
    ): string {
        $name = $data['title']
            ?? $data['name']
            ?? $record->getAttribute('title')
            ?? $record->getAttribute('name')
            ?? null;

        return filled($name)
            ? (string) $name
            : 'Banner ' . $record->getKey();
    }

    /**
     * Resolve accessible alternative text for the uploaded image.
     *
     * @param array<string, mixed> $data
     */
    private function resolveAlternativeText(
        Model $record,
        array $data,
    ): string {
        $alternativeText = $data['alt']
            ?? $record->getAttribute('alt')
            ?? $data['title']
            ?? $record->getAttribute('title')
            ?? $data['name']
            ?? $record->getAttribute('name')
            ?? null;

        return filled($alternativeText)
            ? (string) $alternativeText
            : 'Dura Cabs banner';
    }

    /**
     * Actions displayed in the page header.
     *
     * @return array<Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}