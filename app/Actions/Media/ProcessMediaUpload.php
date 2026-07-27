<?php

namespace App\Actions\Media;

use App\Enums\MediaType;
use App\Models\AppMedia;
use App\Services\Media\MediaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class ProcessMediaUpload
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {
    }

    /**
     * Process a media upload from Admin, Vendor, Customer,
     * Website, API or console.
     *
     * Images are stored as one optimized WebP file.
     * PDF documents are stored as one original PDF file.
     *
     * The supplied file can be:
     *
     * - UploadedFile instance
     * - Absolute local file path
     * - Path stored on a Laravel filesystem disk
     *
     * @param UploadedFile|string $file
     * @param MediaType|string $mediaType
     * @param array<string, mixed> $options
     */
    public function handle(
        UploadedFile|string $file,
        MediaType|string $mediaType,
        ?string $module = null,
        array $options = [],
        ?Model $owner = null,
        ?string $fieldName = null,
        string $preferredVariant = 'original',
    ): AppMedia {
        $type = $this->resolveMediaType(
            $mediaType
        );

        $sourceDisk = trim(
            (string) (
                $options['source_disk']
                ?? 'local'
            )
        );

        if ($sourceDisk === '') {
            $sourceDisk = 'local';
        }

        $cleanupSource = (bool) (
            $options['cleanup_source']
            ?? false
        );

        $temporarySourcePath = is_string($file)
            ? trim($file)
            : null;

        $uploadSucceeded = false;

        try {
            $uploadedFile = $this->resolveUploadedFile(
                file: $file,
                sourceDisk: $sourceDisk,
                originalName:
                    $options['original_name']
                    ?? null,
            );

            $this->validateFile(
                file: $uploadedFile,
                mediaType: $type,
                options: $options,
            );

            $normalizedOptions =
                $this->normalizeOptions(
                    mediaType: $type,
                    module: $module,
                    options: $options,
                    owner: $owner,
                );

            if ($owner !== null) {
                if (!$owner->exists) {
                    throw new InvalidArgumentException(
                        'The related model must be saved before media can be attached.'
                    );
                }

                if (blank($fieldName)) {
                    throw new InvalidArgumentException(
                        'The field name is required when attaching media to a model.'
                    );
                }

                /*
                 * preferredVariant is accepted for backward
                 * compatibility, but single-file storage always
                 * attaches the original_path file.
                 */
                $media = $this->mediaService
                    ->uploadAndAttach(
                        file: $uploadedFile,
                        type: $type,
                        usable: $owner,
                        fieldName: trim(
                            (string) $fieldName
                        ),
                        module: $module,
                        preferredVariant: 'original',
                        options: $normalizedOptions,
                    );
            } else {
                $media = $this->mediaService->upload(
                    file: $uploadedFile,
                    type: $type,
                    module: $module,
                    options: $normalizedOptions,
                );
            }

            $uploadSucceeded = true;

            Log::info(
                'Dura Media upload completed.',
                [
                    'media_id' => $media->id,
                    'media_uuid' => $media->uuid,
                    'media_type' => $type->value,
                    'module' => $module,

                    'storage_mode' =>
                        'single_file',

                    'stored_path' =>
                        $media->storedPath(),

                    'stored_mime_type' =>
                        $media->mime_type,

                    'stored_size' =>
                        $media->optimized_size,

                    'uploaded_by' =>
                        $normalizedOptions[
                            'uploaded_by'
                        ] ?? null,

                    'uploader_type' => data_get(
                        $normalizedOptions,
                        'metadata.uploader_type'
                    ),

                    'owner_type' =>
                        $owner?->getMorphClass(),

                    'owner_id' =>
                        $owner?->getKey(),

                    'field_name' =>
                        $fieldName,
                ]
            );

            return $media;
        } catch (Throwable $exception) {
            Log::error(
                'Dura Media upload failed.',
                [
                    'media_type' => $type->value,
                    'module' => $module,

                    'storage_mode' =>
                        'single_file',

                    'source_disk' =>
                        $sourceDisk,

                    'source_path' =>
                        $temporarySourcePath,

                    'owner_type' =>
                        $owner?->getMorphClass(),

                    'owner_id' =>
                        $owner?->getKey(),

                    'field_name' =>
                        $fieldName,

                    'exception_class' =>
                        $exception::class,

                    'exception' =>
                        $exception->getMessage(),
                ]
            );

            throw new RuntimeException(
                'Unable to process the media upload: '
                . $exception->getMessage(),
                previous: $exception,
            );
        } finally {
            /*
             * Delete the Filament/API temporary upload only after
             * the final WebP/PDF and database record were created.
             */
            if (
                $uploadSucceeded
                && $cleanupSource
                && filled($temporarySourcePath)
            ) {
                $this->cleanupSourceFile(
                    path: $temporarySourcePath,
                    disk: $sourceDisk,
                );
            }
        }
    }

    /**
     * Upload media without attaching it to a model.
     *
     * @param UploadedFile|string $file
     * @param MediaType|string $mediaType
     * @param array<string, mixed> $options
     */
    public function upload(
        UploadedFile|string $file,
        MediaType|string $mediaType,
        ?string $module = null,
        array $options = [],
    ): AppMedia {
        return $this->handle(
            file: $file,
            mediaType: $mediaType,
            module: $module,
            options: $options,
            preferredVariant: 'original',
        );
    }

    /**
     * Upload media and attach it to an existing model.
     *
     * @param UploadedFile|string $file
     * @param MediaType|string $mediaType
     * @param array<string, mixed> $options
     */
    public function uploadForModel(
        UploadedFile|string $file,
        MediaType|string $mediaType,
        Model $owner,
        string $fieldName,
        ?string $module = null,
        string $preferredVariant = 'original',
        array $options = [],
    ): AppMedia {
        return $this->handle(
            file: $file,
            mediaType: $mediaType,
            module: $module,
            options: $options,
            owner: $owner,
            fieldName: $fieldName,
            preferredVariant: 'original',
        );
    }

    /**
     * Resolve enum from enum instance or string.
     */
    private function resolveMediaType(
        MediaType|string $mediaType,
    ): MediaType {
        if ($mediaType instanceof MediaType) {
            return $mediaType;
        }

        $value = strtolower(
            trim($mediaType)
        );

        if ($value === '') {
            throw new InvalidArgumentException(
                'A valid media type is required.'
            );
        }

        /*
         * Backward compatibility for older callers that send
         * generic values instead of MediaType enum values.
         */
        $aliases = [
            'image' => MediaType::Other,
            'photo' => MediaType::Other,
            'file' => MediaType::Document,
            'pdf' => MediaType::Document,
        ];

        if (array_key_exists($value, $aliases)) {
            return $aliases[$value];
        }

        $type = MediaType::tryFrom($value);

        if ($type === null) {
            throw new InvalidArgumentException(
                "Unsupported media type: {$value}"
            );
        }

        return $type;
    }

    /**
     * Convert a path or UploadedFile into a valid UploadedFile.
     */
    private function resolveUploadedFile(
        UploadedFile|string $file,
        string $sourceDisk,
        mixed $originalName,
    ): UploadedFile {
        if ($file instanceof UploadedFile) {
            if (!$file->isValid()) {
                throw new InvalidArgumentException(
                    'The uploaded file is invalid.'
                );
            }

            $realPath = $file->getRealPath();

            if (
                !is_string($realPath)
                || !is_file($realPath)
                || !is_readable($realPath)
            ) {
                throw new InvalidArgumentException(
                    'The uploaded file cannot be read.'
                );
            }

            return $file;
        }

        $path = trim($file);

        if ($path === '') {
            throw new InvalidArgumentException(
                'The uploaded file path is empty.'
            );
        }

        $absolutePath =
            $this->resolveAbsolutePath(
                path: $path,
                sourceDisk: $sourceDisk,
            );

        if (!is_file($absolutePath)) {
            throw new InvalidArgumentException(
                "The uploaded file does not exist: {$path}"
            );
        }

        if (!is_readable($absolutePath)) {
            throw new InvalidArgumentException(
                "The uploaded file is not readable: {$path}"
            );
        }

        $resolvedOriginalName =
            is_string($originalName)
            && trim($originalName) !== ''
                ? basename(
                    trim($originalName)
                )
                : basename($absolutePath);

        $mimeType = mime_content_type(
            $absolutePath
        );

        return new UploadedFile(
            path: $absolutePath,
            originalName: $resolvedOriginalName,
            mimeType: is_string($mimeType)
                ? $mimeType
                : null,
            error: UPLOAD_ERR_OK,
            test: true,
        );
    }

    /**
     * Resolve a local absolute path from a local or
     * Laravel filesystem path.
     */
    private function resolveAbsolutePath(
        string $path,
        string $sourceDisk,
    ): string {
        if (is_file($path)) {
            $realPath = realpath($path);

            return $realPath !== false
                ? $realPath
                : $path;
        }

        $storage = Storage::disk(
            $sourceDisk
        );

        if (!$storage->exists($path)) {
            throw new InvalidArgumentException(
                "File not found on the '{$sourceDisk}' disk: {$path}"
            );
        }

        try {
            $resolvedPath = $storage->path(
                $path
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "The '{$sourceDisk}' disk does not expose a local file path.",
                previous: $exception,
            );
        }

        if (
            !is_file($resolvedPath)
            || !is_readable($resolvedPath)
        ) {
            throw new RuntimeException(
                "The file on '{$sourceDisk}' disk cannot be read: {$path}"
            );
        }

        return $resolvedPath;
    }

    /**
     * Validate MIME type and uploaded source size.
     *
     * @param array<string, mixed> $options
     */
    private function validateFile(
        UploadedFile $file,
        MediaType $mediaType,
        array $options,
    ): void {
        $mimeType = strtolower(
            trim(
                (string) (
                    $file->getMimeType()
                    ?: $file->getClientMimeType()
                )
            )
        );

        $imageMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'image/gif',
        ];

        $allowedMimeTypes =
            $mediaType->isDocument()
                ? [
                    ...$imageMimeTypes,
                    'application/pdf',
                ]
                : $imageMimeTypes;

        if (
            $mimeType === ''
            || !in_array(
                $mimeType,
                $allowedMimeTypes,
                true
            )
        ) {
            throw new InvalidArgumentException(
                "Unsupported media MIME type: "
                . ($mimeType !== ''
                    ? $mimeType
                    : 'unknown')
            );
        }

        $defaultMaximumKilobytes =
            $mediaType->isDocument()
                ? (int) config(
                    'dura-media.max_document_size_kb',
                    51200
                )
                : (int) config(
                    'dura-media.max_image_size_kb',
                    25600
                );

        $maximumKilobytes = max(
            1,
            (int) (
                $options['max_size_kb']
                ?? $defaultMaximumKilobytes
            )
        );

        $fileSizeBytes = max(
            0,
            (int) (
                $file->getSize()
                ?: 0
            )
        );

        if (
            $fileSizeBytes >
            ($maximumKilobytes * 1024)
        ) {
            throw new InvalidArgumentException(
                "The uploaded file exceeds the {$maximumKilobytes} KB limit."
            );
        }
    }

    /**
     * Normalize storage and audit options.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function normalizeOptions(
        MediaType $mediaType,
        ?string $module,
        array $options,
        ?Model $owner,
    ): array {
        $uploaderType = trim(
            (string) (
                $options['uploader_type']
                ?? $this->detectUploaderType()
            )
        );

        if ($uploaderType === '') {
            $uploaderType = 'guest';
        }

        $isPublic = array_key_exists(
            'is_public',
            $options
        )
            ? (bool) $options['is_public']
            : !$mediaType->isDocument();

        $targetDisk = trim(
            (string) (
                $options['disk']
                ?? ($isPublic
                    ? 'public'
                    : 'local')
            )
        );

        if ($targetDisk === '') {
            $targetDisk = $isPublic
                ? 'public'
                : 'local';
        }

        $metadata = array_merge(
            [
                'storage_mode' =>
                    'single_file',

                'variants_generated' =>
                    false,

                'uploader_type' =>
                    $uploaderType,

                'upload_source' =>
                    $options['upload_source']
                    ?? $this->detectUploadSource(),

                'module' =>
                    $module,

                'owner_type' =>
                    $owner?->getMorphClass(),

                'owner_id' =>
                    $owner?->getKey(),

                'uploaded_ip' =>
                    $this->requestIp(),

                'uploaded_user_agent' =>
                    $this->requestUserAgent(),
            ],
            $this->cleanMetadata(
                (array) (
                    $options['metadata']
                    ?? []
                )
            )
        );

        return array_merge(
            $options,
            [
                'disk' => $targetDisk,

                'is_public' => $isPublic,

                'uploaded_by' =>
                    $options['uploaded_by']
                    ?? Auth::id(),

                'metadata' => array_filter(
                    $metadata,
                    static fn (
                        mixed $value,
                    ): bool =>
                        $value !== null
                        && $value !== ''
                ),
            ]
        );
    }

    /**
     * Remove variant settings passed by legacy callers.
     *
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function cleanMetadata(
        array $metadata,
    ): array {
        unset(
            $metadata['large_size'],
            $metadata['medium_size'],
            $metadata['thumbnail_size'],
            $metadata['variant_paths'],
            $metadata['variants'],
            $metadata['preferred_variant'],
        );

        return $metadata;
    }

    private function detectUploaderType(): string
    {
        if (!Auth::check()) {
            return 'guest';
        }

        $user = Auth::user();

        if ($user === null) {
            return 'guest';
        }

        if (
            method_exists($user, 'hasRole')
            && $user->hasRole('admin')
        ) {
            return 'admin';
        }

        if (
            method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole([
                'vendor',
                'transporter',
                'partner',
            ])
        ) {
            return 'vendor';
        }

        return 'customer';
    }

    private function detectUploadSource(): string
    {
        if (app()->runningInConsole()) {
            return 'console';
        }

        $request = request();

        if (
            $request->is('admin/*')
            || $request->is('admin')
        ) {
            return 'admin_panel';
        }

        if (
            $request->is('transporter/*')
            || $request->is('vendor/*')
        ) {
            return 'vendor_panel';
        }

        if ($request->is('api/*')) {
            return 'api';
        }

        return 'website';
    }

    private function requestIp(): ?string
    {
        if (app()->runningInConsole()) {
            return null;
        }

        return request()->ip();
    }

    private function requestUserAgent(): ?string
    {
        if (app()->runningInConsole()) {
            return null;
        }

        return request()->userAgent();
    }

    /**
     * Delete the temporary upload source.
     */
    private function cleanupSourceFile(
        string $path,
        string $disk,
    ): void {
        try {
            if (is_file($path)) {
                if (!@unlink($path)) {
                    Log::warning(
                        'Temporary media source file could not be deleted.',
                        [
                            'path' => $path,
                            'disk' => $disk,
                        ]
                    );
                }

                return;
            }

            $storage = Storage::disk(
                $disk
            );

            if ($storage->exists($path)) {
                $storage->delete($path);
            }
        } catch (Throwable $exception) {
            Log::warning(
                'Unable to delete temporary media source file.',
                [
                    'path' => $path,
                    'disk' => $disk,
                    'exception' =>
                        $exception->getMessage(),
                ]
            );
        }
    }
}