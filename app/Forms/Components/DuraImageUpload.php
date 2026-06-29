<?php

namespace App\Forms\Components;

use Filament\Forms\Components\FileUpload;

class DuraImageUpload
{
    public static function make(
        string $name = 'image',
        string $directory = 'uploads'
    ): FileUpload {

        return FileUpload::make($name)

            ->label('Image')

            ->directory($directory)

            ->image()

            ->imageEditor()

            ->imagePreviewHeight('180')

            ->panelAspectRatio('16:9')

            ->panelLayout('integrated')

            ->loadingIndicatorPosition('left')

            ->removeUploadedFileButtonPosition('right')

            ->uploadButtonPosition('left')

            ->uploadProgressIndicatorPosition('left')

            ->downloadable()

            ->openable()

            ->previewable(true)

            ->preserveFilenames()

            ->maxSize(4096)

            ->acceptedFileTypes([
                'image/jpeg',
                'image/png',
                'image/webp'
            ])

            ->helperText(
                'JPG, PNG, WEBP • Max 4 MB'
            );
    }
}
