<?php

namespace App\Filament\Resources\AppMediaResource\Pages;

use App\Actions\Media\ProcessMediaUpload;
use App\Enums\MediaType;
use App\Filament\Resources\AppMediaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateAppMedia extends CreateRecord
{
    protected static string $resource = AppMediaResource::class;

    /**
     * Create AppMedia through the central Dura Media Engine.
     *
     * @param array<string, mixed> $data
     */
    protected function handleRecordCreation(
        array $data
    ): Model {
        $temporaryPath = $this->extractUploadPath(
            $data['upload'] ?? null
        );

        if ($temporaryPath === null) {
            throw new RuntimeException(
                'Please select a media file before saving.'
            );
        }

        $originalName = $this->extractOriginalName(
            $data['uploaded_file_name'] ?? null
        );

        $mediaType = MediaType::tryFrom(
            (string) (
                $data['media_type']
                ?? MediaType::Other->value
            )
        ) ?? MediaType::Other;

        /*
         * Important:
         *
         * source_disk = local
         * FileUpload temporary file local disk me save karta hai.
         *
         * disk = public/local
         * Final optimized media kis disk me save hoga.
         */
        $targetDisk = (string) (
            $data['disk']
            ?? (
                $mediaType->isDocument()
                    ? 'local'
                    : 'public'
            )
        );

        /** @var ProcessMediaUpload $processor */
        $processor = app(
            ProcessMediaUpload::class
        );

        return $processor->upload(
            file: $temporaryPath,
            mediaType: $mediaType,
            module: $data['module'] ?? null,
            options: [
                'source_disk' => 'local',
                'cleanup_source' => true,

                'disk' => $targetDisk,

                'name' => $data['name']
                    ?? $originalName
                    ?? $mediaType->label(),

                'slug' => $data['slug']
                    ?? null,

                'original_name' => $originalName,

                'alt_text' => $data['alt_text']
                    ?? null,

                'caption' => $data['caption']
                    ?? null,

                'quality' => $data['quality']
                    ?? null,

                'is_active' => (bool) (
                    $data['is_active']
                    ?? true
                ),

                'is_public' => (bool) (
                    $data['is_public']
                    ?? !$mediaType->isDocument()
                ),

                'sort_order' => max(
                    0,
                    (int) (
                        $data['sort_order']
                        ?? 0
                    )
                ),

                'uploaded_by' => auth()->id(),

                'uploader_type' => 'admin',

                'upload_source' => 'admin_panel',

                'metadata' => (array) (
                    $data['metadata']
                    ?? []
                ),
            ],
        );
    }

    /**
     * Filament FileUpload state se temporary path nikalega.
     */
    private function extractUploadPath(
        mixed $state
    ): ?string {
        if (is_string($state)) {
            $path = trim($state);

            return $path !== ''
                ? $path
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
     * Original filename string ya array dono support karega.
     */
    private function extractOriginalName(
        mixed $state
    ): ?string {
        if (is_string($state)) {
            $name = trim($state);

            return $name !== ''
                ? $name
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
}