<?php

namespace App\Filament\Resources\BannersResource\Pages;

use App\Actions\Media\ProcessMediaUpload;
use App\Enums\MediaType;
use App\Filament\Resources\BannersResource;
use App\Models\AppMedia;
use App\Services\Media\MediaUsageService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateBanners extends CreateRecord
{
    protected static string $resource = BannersResource::class;

    /**
     * Newly created AppMedia record ID.
     *
     * Banner create hone ke baad isi media ko banner record
     * ke saath media_usages table me attach kiya jayega.
     */
    private ?int $createdMediaId = null;

    /**
     * Filament banner record create karne se pehle
     * temporary upload ko Dura Media Engine se process karega.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $temporaryPath = $this->extractTemporaryPath(
            $data['media_upload'] ?? null
        );

        if ($temporaryPath === null) {
            throw new RuntimeException(
                'Please select a banner image before saving.'
            );
        }

        $originalName = $this->extractOriginalName(
            $data['media_upload_original_name']
                ?? null
        );

        /** @var ProcessMediaUpload $processor */
        $processor = app(
            ProcessMediaUpload::class
        );

        $media = $processor->upload(
            file: $temporaryPath,
            mediaType: MediaType::Banner,
            module: 'banners',
            options: [
                'source_disk' => 'local',
                'cleanup_source' => false,

                'disk' => 'public',
                'is_public' => true,

                'name' => $data['name']
                    ?? $data['title']
                    ?? 'Banner',

                'alt_text' => $data['alt']
                    ?? $data['title']
                    ?? $data['name']
                    ?? 'Banner',

                'original_name' => $originalName,

                'uploaded_by' => auth()->id(),
                'uploader_type' => 'admin',
                'upload_source' => 'admin_panel',

                'metadata' => [
                    'resource' => BannersResource::class,
                    'ride_type' => $data['ride_type']
                        ?? null,
                ],
            ],
        );

        $this->createdMediaId = $media->id;

        /*
        |--------------------------------------------------------------------------
        | Backward compatibility
        |--------------------------------------------------------------------------
        |
        | app_media_id naya Media Engine relation hai.
        | image existing website aur Flutter API ko break hone se bachata hai.
        |
        */

        $data['app_media_id'] = $media->id;

        $data['image'] = $media->image_url
    ?? $media->medium_url
    ?? $media->large_url
    ?? $media->original_url;

        unset(
            $data['media_upload'],
            $data['media_upload_original_name']
        );

        return $data;
    }

    /**
     * Banner create hone ke baad media_usages relation banata hai.
     */
    protected function afterCreate(): void
    {
        if ($this->createdMediaId === null) {
            return;
        }

        $media = AppMedia::query()
            ->find($this->createdMediaId);

        if (!$media instanceof AppMedia) {
            return;
        }

        /** @var MediaUsageService $usageService */
        $usageService = app(
            MediaUsageService::class
        );

        $usageService->attach(
            media: $media,
            owner: $this->record,
            fieldName: 'image',
            preferredVariant: 'medium',
            metadata: [
                'resource' => BannersResource::class,
                'attached_from' => 'create_banner',
            ],
        );
    }

    /**
     * Filament FileUpload state se single temporary path nikalega.
     */
    private function extractTemporaryPath(
        mixed $state
    ): ?string {
        if (is_string($state)) {
            $path = trim($state);

            return $path !== ''
                ? $path
                : null;
        }

        if (is_array($state)) {
            $first = collect($state)
                ->filter(
                    static fn (mixed $value): bool =>
                        is_string($value)
                        && trim($value) !== ''
                )
                ->first();

            return is_string($first)
                ? trim($first)
                : null;
        }

        return null;
    }

    /**
     * storeFileNamesIn field kabhi string aur kabhi array ho sakta hai.
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
            $first = collect($state)
                ->filter(
                    static fn (mixed $value): bool =>
                        is_string($value)
                        && trim($value) !== ''
                )
                ->first();

            return is_string($first)
                ? trim($first)
                : null;
        }

        return null;
    }
}