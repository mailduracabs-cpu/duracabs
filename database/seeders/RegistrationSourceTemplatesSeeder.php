<?php

namespace Database\Seeders;

use App\Models\WhatsAppTemplate;
use App\Services\RegistrationSourceTemplates;
use Illuminate\Database\Seeder;

class RegistrationSourceTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RegistrationSourceTemplates::definitions() as $definition) {
            // Never edit, replace or mark an existing Meta template approved.
            WhatsAppTemplate::query()->firstOrCreate([
                'template_name' => $definition['template_name'],
            ], array_merge($definition, [
                'status' => WhatsAppTemplate::STATUS_ACTIVE,
                'meta_status' => WhatsAppTemplate::META_STATUS_NOT_SUBMITTED,
                'is_active' => true,
            ]));
        }
        $this->command?->info('Three unsubmitted source templates created. Existing notification rules are unchanged.');
    }
}
