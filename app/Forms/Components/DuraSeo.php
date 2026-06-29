<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class DuraSeo
{
    public static function make(): Section
    {
        return Section::make('SEO Settings')
            ->description('Website aur app SEO ke liye meta details.')
            ->collapsible()
            ->collapsed()
            ->schema([
                TextInput::make('meta_title')
                    ->label('Meta Title')
                    ->maxLength(255),

                Textarea::make('meta_description')
                    ->label('Meta Description')
                    ->rows(3)
                    ->maxLength(500),

                TextInput::make('alt')
                    ->label('Image Alt Text')
                    ->maxLength(255)
                    ->helperText('Image ke liye SEO friendly alt text.'),
            ]);
    }
}
