<?php

namespace App\Filament\Resources\MyDriverResource\Pages;

use App\Filament\Resources\MyDriverResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMyDriver extends CreateRecord
{
    protected static string $resource = MyDriverResource::class;

    protected static ?string $title = 'Add Driver';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $authenticatedUser = Auth::user();

        if ($authenticatedUser?->hasRole('Transporter')) {
            $data['created_by'] = $authenticatedUser->id;
        }

        $data['login_type'] = $data['login_type'] ?? 'admin';
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->record instanceof User) {
            return;
        }

        if (! $this->record->hasRole('Driver')) {
            $this->record->assignRole('Driver');
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
