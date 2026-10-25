<?php

namespace App\Services;

use App\Models\Vehicle;
use Carbon\CarbonImmutable;

class VendorVehicleLifecycle
{
    public function run(): array
    {
        $delivery = app(VendorLifecycleDelivery::class);
        $expired = 0;
        $since = config('vendor_lifecycle.reminder_since');
        if (! $since) throw new \RuntimeException('Set VENDOR_LIFECYCLE_REMINDER_SINCE before enabling reminders.');
        \Illuminate\Support\Facades\Validator::make(['since' => $since], ['since' => ['required', 'date_format:Y-m-d']])->validate();
        $since = VendorLifecycleClock::date($since);
        Vehicle::query()->where('service_type', 'self_drive')->with('transporter.user')
            ->chunkById(100, function ($vehicles) use ($delivery, &$expired, $since): void {
                foreach ($vehicles as $vehicle) {
                    if ($vehicle->attachmentIsExpired() && ($vehicle->is_active || $vehicle->is_live)) {
                        // Narrow update: do not cancel bookings, change payment history or overwrite rejection workflows.
                        Vehicle::query()->whereKey($vehicle->id)->update(['is_active' => false, 'is_live' => false]);
                        $expired++;
                    }
                    if ($vehicle->getAttribute('partner_removed_at')) continue;
                    foreach ([
                        'partner_agreement_expires_on' => 'Car attachment validity', 'rc_expiry_date' => 'RC',
                        'puc_expiry_date' => 'PUC', 'insurance_expiry_date' => 'Insurance', 'gps_expiry_date' => 'GPS recharge',
                    ] as $field => $label) {
                        $date = $vehicle->getAttribute($field);
                        if (! $date) continue;
                        $date = VendorLifecycleClock::date($date->toDateString());
                        $days = config($field === 'partner_agreement_expires_on' ? 'vendor_lifecycle.agreement_reminder_days' : 'vendor_lifecycle.document_reminder_days');
                        // Catch up at most one threshold per run; old thresholds are superseded by the most urgent one.
                        $due = collect($days)->filter(fn ($lead) => ($date->gte(VendorLifecycleClock::today()) || $date->subDays($lead)->gte($since))
                            && $date->subDays($lead)->lte(VendorLifecycleClock::today()))->sort()->first();
                        if ($due === null) continue;
                        $event = "vehicle:{$vehicle->id}:{$field}:{$date->toDateString()}:{$due}";
                        $title = $vehicle->vehicle_number . ' — ' . $label;
                        $body = $label . ' expiry date: ' . $date->format('d M Y') . '. Please contact DuraCabs admin for renewal.';
                        $source = ['vehicle_id' => $vehicle->id, 'profile_id' => $vehicle->transporter_profile_id, 'expiry_field' => $field, 'expiry_date' => $date->toDateString()];
                        $delivery->admins($event, $title, $body, $source);
                        if ($field !== 'partner_agreement_expires_on') continue;
                        $profile = $vehicle->transporter;
                        if (! $profile) continue;
                        if (config('vendor_lifecycle.email_enabled')) $delivery->queue($event, 'email',
                            (string) ($profile->email ?: $profile->user?->email), ['title' => $title, 'body' => $body] + $source);
                        if (config('vendor_lifecycle.whatsapp_enabled')) $delivery->queue($event, 'whatsapp',
                            (string) ($profile->whatsapp_number ?: $profile->mobile ?: $profile->user?->mobile),
                            ['title' => $title, 'body' => $body, 'template_key' => config('vendor_lifecycle.reminder_template_key'), 'parameters' => [$title, $body]] + $source);
                    }
                }
            });
        return ['expired_vehicles' => $expired];
    }
}
