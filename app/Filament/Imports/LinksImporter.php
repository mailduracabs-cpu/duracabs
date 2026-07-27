<?php

namespace App\Filament\Imports;

use App\Models\Links;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class LinksImporter extends Importer
{
    protected static ?string $model = Links::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('title')
                ->rules(['max:255']),
            ImportColumn::make('url')
                ->rules(['max:255']),
            ImportColumn::make('ride_type')
                ->rules(['max:255']),
            ImportColumn::make('product_id')
                ->numeric()
                ->rules(rules: ['integer']),
        ];
    }

    public function resolveRecord(): ?Links
    {
        // return Links::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Links();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your links import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format(num: $failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
