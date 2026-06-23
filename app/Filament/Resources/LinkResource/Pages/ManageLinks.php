<?php

namespace App\Filament\Pages;

use App\Models\Link;
use Filament\Pages\Page;
use Filament\Actions\Action;

class ManageLinks extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static string $view = 'filament.pages.manage-links';

    public function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Add Link')
                ->icon('heroicon-o-plus')
                ->modalHeading('Create Link')
                ->form([
                    \Filament\Forms\Components\TextInput::make('label')
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('url')
                        ->required()
                        ->url(),
                    \Filament\Forms\Components\TextInput::make('icon')
                        ->label('Icon (optional)'),
                    \Filament\Forms\Components\Toggle::make('is_public')
                        ->label('Visible')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    Link::create($data);
                }),
        ];
    }
}
