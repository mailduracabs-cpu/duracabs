<?php

// CLI-only; this file must never change notification rules from a web request.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WhatsAppNotificationRule;
use App\Models\WhatsAppTemplate;
use App\Services\RegistrationSourceTemplates;
use Illuminate\Support\Facades\DB;

try {
    DB::transaction(function (): void {
        $ready = [];
        foreach (RegistrationSourceTemplates::definitions() as $event => $definition) {
            $record = WhatsAppTemplate::query()->where('template_name', $definition['template_name'])->lockForUpdate()->first();
            if (! $record || $record->meta_status !== WhatsAppTemplate::META_STATUS_APPROVED) {
                throw new RuntimeException('Template must first be approved and synced: ' . $definition['template_name']);
            }
            foreach (['template_key', 'category', 'language', 'header_type', 'body', 'footer'] as $field) {
                if ((string) $record->{$field} !== (string) $definition[$field]) {
                    throw new RuntimeException('Template content/mapping differs; review before activation: ' . $definition['template_name']);
                }
            }
            $variables = collect($record->variables)->sortBy('position')->values();
            $positions = $variables->pluck('position')->map(fn ($value) => (int) $value)->all();
            if ($positions !== range(1, count($definition['variables']))) {
                throw new RuntimeException('Template variable positions differ: ' . $definition['template_name']);
            }
            $keys = $variables->pluck('key')->all();
            if ($keys !== array_column($definition['variables'], 'key')) {
                throw new RuntimeException('Template variable order differs: ' . $definition['template_name']);
            }
            $ready[$event] = $definition;
        }
        // All three approvals are checked before any rule changes. Preserve
        // custom sales/operations/staff recipient flags on existing rules.
        foreach ($ready as $event => $definition) {
            $template = WhatsAppTemplate::query()->where('template_name', $definition['template_name'])->firstOrFail();
            $template->forceFill(['status' => WhatsAppTemplate::STATUS_ACTIVE, 'is_active' => true])->save();
            $rule = WhatsAppNotificationRule::query()->firstOrNew(['event_key' => $event]);
            if (! $rule->exists) $rule->forceFill([
                'name' => $definition['name'], 'send_customer' => false,
                'send_vendor' => false, 'send_driver' => false, 'send_sales' => false,
                'send_operations' => false, 'send_accounts' => false, 'send_support' => false,
            ]);
            $rule->forceFill(['template_key' => $definition['template_key'], 'send_admin' => true, 'is_active' => true])->save();
        }
    });
    echo "Registration source templates activated. No messages were sent by this command.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\nNo notification rules were changed.\n");
    exit(1);
}
