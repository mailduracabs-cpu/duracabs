<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Media\DeleteMedia;
use App\Actions\Media\ProcessMediaUpload;
use App\Actions\Media\ReplaceMedia;
use App\Enums\MediaType;
use App\Http\Controllers\Controller;
use App\Models\AppMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class MediaController extends Controller
{
    public function index(
        Request $request,
    ): JsonResponse {
        $validated = $request->validate([
            'type' => [
                'nullable',
                Rule::enum(MediaType::class),
            ],

            'module' => [
                'nullable',
                'string',
                'max:100',
            ],

            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $query = AppMedia::query()
            ->active()
            ->public()
            ->latest();

        if (filled($validated['type'] ?? null)) {
            $query->where(
                'media_type',
                $validated['type']
            );
        }

        if (filled($validated['module'] ?? null)) {
            $query->where(
                'module',
                $validated['module']
            );
        }

        if (filled($validated['search'] ?? null)) {
            $search = $validated['search'];

            $query->where(
                function ($builder) use (
                    $search
                ): void {
                    $builder
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'alt_text',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'original_name',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        $media = $query->paginate(
            (int) (
                $validated['limit'] ?? 20
            )
        );

        return response()->json([
            'success' => true,
            'data' => $media,
        ]);
    }

    public function show(
        Request $request,
        AppMedia $media,
    ): JsonResponse {
        if (
            !$media->is_public &&
            (!$request->user() ||
                (int) $media->uploaded_by !==
                (int) $request->user()->getKey())
        ) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'data' => $media,
        ]);
    }

    public function store(
        Request $request,
        ProcessMediaUpload $processMediaUpload,
    ): JsonResponse {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:51200',
            ],

            'media_type' => [
                'required',
                Rule::enum(MediaType::class),
            ],

            'module' => [
                'nullable',
                'string',
                'max:100',
            ],

            'name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'alt_text' => [
                'nullable',
                'string',
                'max:255',
            ],

            'caption' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'is_public' => [
                'nullable',
                'boolean',
            ],

            'quality' => [
                'nullable',
                'integer',
                'min:40',
                'max:100',
            ],
        ]);

        try {
            $type = MediaType::from(
                $validated['media_type']
            );

            $isPublic = array_key_exists(
                'is_public',
                $validated
            )
                ? (bool) $validated['is_public']
                : !$type->isDocument();

            $media = $processMediaUpload->upload(
                file: $request->file('file'),
                mediaType: $type,
                module:
                    $validated['module']
                    ?? null,
                options: [
                    'name' =>
                        $validated['name']
                        ?? null,

                    'alt_text' =>
                        $validated['alt_text']
                        ?? null,

                    'caption' =>
                        $validated['caption']
                        ?? null,

                    'quality' =>
                        $validated['quality']
                        ?? null,

                    'is_public' => $isPublic,

                    'uploaded_by' =>
                        $request->user()?->getKey(),

                    'uploader_type' =>
                        $request->user()
                            ? 'authenticated'
                            : 'guest',

                    'upload_source' => 'api',
                ],
            );

            return response()->json([
                'success' => true,
                'message' =>
                    'Media uploaded successfully.',
                'data' => $media,
            ], 201);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }

    public function replace(
        Request $request,
        AppMedia $media,
        ReplaceMedia $replaceMedia,
    ): JsonResponse {
        $this->authorize(
            'update',
            $media
        );

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:51200',
            ],

            'media_type' => [
                'nullable',
                Rule::enum(MediaType::class),
            ],

            'module' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $type = filled(
            $validated['media_type'] ?? null
        )
            ? MediaType::from(
                $validated['media_type']
            )
            : (
                $media->media_type
                instanceof MediaType
                    ? $media->media_type
                    : MediaType::from(
                        $media->media_type
                    )
            );

        $newMedia = $replaceMedia->handle(
            oldMedia: $media,
            newFile: $request->file('file'),
            mediaType: $type,
            module:
                $validated['module']
                ?? $media->module,
            options: [
                'uploaded_by' =>
                    $request->user()?->getKey(),

                'uploader_type' =>
                    'authenticated',

                'upload_source' => 'api',
            ],
        );

        return response()->json([
            'success' => true,
            'message' =>
                'Media replaced successfully.',
            'data' => $newMedia,
        ]);
    }

    public function destroy(
        AppMedia $media,
        DeleteMedia $deleteMedia,
    ): JsonResponse {
        $this->authorize(
            'delete',
            $media
        );

        $deleteMedia->deleteIfUnused(
            media: $media,
            force: true,
        );

        return response()->json([
            'success' => true,
            'message' =>
                'Media deleted successfully.',
        ]);
    }
}