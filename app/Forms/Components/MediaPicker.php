<?php

namespace App\Forms\Components;

use App\Enums\MediaType;
use App\Models\AppMedia;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class MediaPicker
{
    public static function make(
        string $name = 'app_media_id',
        MediaType|string|null $type = null,
        ?string $module = null,
    ): Select {
        $typeValue = $type instanceof MediaType
            ? $type->value
            : $type;

        return Select::make($name)
            ->label('Select Media')
            ->searchable()
            ->preload()
            ->native(false)
            ->options(
                fn (): array =>
                    self::options(
                        type: $typeValue,
                        module: $module,
                    )
            )
            ->getSearchResultsUsing(
                fn (
                    string $search
                ): array =>
                    self::options(
                        type: $typeValue,
                        module: $module,
                        search: $search,
                        limit: 50,
                    )
            )
            ->getOptionLabelUsing(
                fn (
                    mixed $value
                ): ?string =>
                    self::labelFor($value)
            )
            ->helperText(
                'Select an existing optimized file from the Media Library.'
            );
    }

    /**
     * @return array<int|string, string>
     */
    private static function options(
        ?string $type,
        ?string $module,
        ?string $search = null,
        int $limit = 100,
    ): array {
        return AppMedia::query()
            ->active()
            ->when(
                filled($type),
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'media_type',
                        $type
                    )
            )
            ->when(
                filled($module),
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'module',
                        $module
                    )
            )
            ->when(
                filled($search),
                function (
                    Builder $query
                ) use ($search): Builder {
                    return $query->where(
                        function (
                            Builder $builder
                        ) use ($search): void {
                            $builder
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'original_name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'alt_text',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->latest()
            ->limit($limit)
            ->get()
            ->mapWithKeys(
                fn (
                    AppMedia $media
                ): array => [
                    $media->id =>
                        self::formatLabel($media),
                ]
            )
            ->all();
    }

    private static function labelFor(
        mixed $value,
    ): ?string {
        if (blank($value)) {
            return null;
        }

        $media = AppMedia::query()
            ->find($value);

        return $media instanceof AppMedia
            ? self::formatLabel($media)
            : null;
    }

    private static function formatLabel(
        AppMedia $media,
    ): string {
        $type = $media->media_type
            instanceof MediaType
                ? $media->media_type->label()
                : ucfirst(
                    (string) $media->media_type
                );

        $module = $media->module
            ?: 'General';

        return sprintf(
            '%s — %s / %s',
            $media->name
                ?: $media->original_name
                ?: 'Media #' . $media->id,
            $type,
            $module,
        );
    }
}