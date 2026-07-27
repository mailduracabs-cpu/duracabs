<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppMediaResource\Pages;
use App\Filament\Resources\AppMediaResource\Schemas\AppMediaForm;
use App\Filament\Resources\AppMediaResource\Tables\AppMediaTable;
use App\Models\AppMedia;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AppMediaResource extends Resource
{
    protected static ?string $model = AppMedia::class;

    protected static ?string $navigationIcon =
        'heroicon-o-photo';

    protected static ?string $activeNavigationIcon =
        'heroicon-s-photo';

    protected static ?string $navigationLabel =
        'Media Library';

    protected static ?string $modelLabel =
        'Media';

    protected static ?string $pluralModelLabel =
        'Media Library';

    protected static ?string $navigationGroup =
        'Media';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute =
        'name';

    protected static bool $shouldRegisterNavigation =
        true;

    public static function form(Form $form): Form
    {
        return AppMediaForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return AppMediaTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'original_name',
            'alt_text',
            'caption',
            'module',
            'file_hash',
        ];
    }

    public static function getGlobalSearchResultTitle(
        mixed $record
    ): string {
        return $record->name
            ?: $record->original_name
            ?: 'Media #' . $record->getKey();
    }

    public static function getGlobalSearchResultDetails(
        mixed $record
    ): array {
        return [
            'Type' => $record->media_type?->label()
                ?? ucfirst(
                    (string) $record->media_type
                ),

            'Module' => $record->module
                ?: 'General',
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListAppMedia::route('/'),

            'create' =>
                Pages\CreateAppMedia::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditAppMedia::route(
                    '/{record}/edit'
                ),
        ];
    }
}