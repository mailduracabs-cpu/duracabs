<?php

namespace App\Filament\Resources\TransporterProfileResource\Pages;

use App\Filament\Resources\TransporterProfileResource;
use App\Models\FleetManagement\TransporterProfile;
use Filament\Resources\Pages\CreateRecord;

class CreateTransporterProfile extends CreateRecord
{
    protected static string $resource = TransporterProfileResource::class;

    protected static ?string $title = 'Add Partner';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['partner_type'] = $data['partner_type']
            ?? TransporterProfile::TYPE_VENDOR;

        $data['verification_status'] = $data['verification_status']
            ?? TransporterProfile::VERIFICATION_PENDING;

        $data['status'] = $data['status'] ?? true;
        $data['service_radius_km'] = $data['service_radius_km'] ?? 40;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
