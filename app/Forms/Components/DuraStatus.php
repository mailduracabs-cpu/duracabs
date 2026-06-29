<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;

class DuraStatus
{
    public static function make(string $field = 'is_active'): Section
    {
        return Section::make('Status')
            ->schema([
                Toggle::make($field)
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
