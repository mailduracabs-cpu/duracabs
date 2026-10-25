<?php

namespace App\Services;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class VendorLifecycleDelivery
{
    public function queue(string $event, string $channel, string $recipient, array $payload): void
    {
        if (trim($recipient) === '') return;
        DB::table('vendor_delivery_logs')->insertOrIgnore([
            'event_key' => hash('sha256', $event . ':' . $channel . ':' . $recipient),
            'channel' => $channel, 'recipient' => $recipient, 'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'status' => 'pending', 'attempts' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function admins(string $event, string $title, string $body, array $source = []): void
    {
        User::query()->chunkById(100, function ($users) use ($event, $title, $body, $source): void {
            foreach ($users as $user) {
                if (! $user->canUseAdminLogin()) continue;
                $payload = ['title' => $title, 'body' => $body] + $source;
                $this->queue($event, 'admin', (string) $user->id, $payload);
                if (config('vendor_lifecycle.email_enabled')) $this->queue($event, 'email', (string) $user->email, $payload);
                if (config('vendor_lifecycle.whatsapp_enabled')) $this->queue($event, 'whatsapp', (string) $user->mobile,
                    $payload + ['template_key' => config('vendor_lifecycle.reminder_template_key'), 'parameters' => [$title, $body]]);
            }
        });
    }

    /** Success is recorded once; uncertain network outcomes require review, never blind replay. */
    public function dispatchPending(bool $retryFailed = false, int $limit = 100): int
    {
        $ids = DB::table('vendor_delivery_logs')->whereIn('status', $retryFailed ? ['pending', 'failed'] : ['pending'])
            ->orderBy('id')->limit($limit)->pluck('id');
        $sent = 0;
        foreach ($ids as $id) {
            $row = DB::transaction(function () use ($id, $retryFailed) {
                $row = DB::table('vendor_delivery_logs')->where('id', $id)->lockForUpdate()->first();
                if (! $row || ! in_array($row->status, $retryFailed ? ['pending', 'failed'] : ['pending'], true)) return null;
                DB::table('vendor_delivery_logs')->where('id', $id)->update([
                    'status' => 'processing', 'attempts' => $row->attempts + 1, 'updated_at' => now(),
                ]);
                return $row;
            });
            if (! $row) continue;
            try {
                $payload = json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR);
                if (isset($payload['expiry_field'], $payload['vehicle_id'])) {
                    $vehicle = \App\Models\Vehicle::find($payload['vehicle_id']);
                    $current = $vehicle?->getAttribute($payload['expiry_field'])?->toDateString();
                    if (! $vehicle || $vehicle->getAttribute('partner_removed_at') || $current !== $payload['expiry_date']
                        || (int) $vehicle->transporter_profile_id !== (int) $payload['profile_id']) {
                        DB::table('vendor_delivery_logs')->where('id', $id)->update(['status' => 'cancelled', 'last_error' => 'Renewed, removed or ownership changed.', 'updated_at' => now()]);
                        continue;
                    }
                }
                $providerId = null;
                if ($row->channel === 'admin') {
                    $user = User::find($row->recipient);
                    if (! $user || ! $user->canUseAdminLogin()) throw new \RuntimeException('Admin account no longer eligible.');
                    $hex = substr(hash('sha256', 'vendor-lifecycle:' . $row->event_key), 0, 32);
                    $uuid = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
                    DB::table('notifications')->insertOrIgnore([
                        'id' => $uuid, 'type' => 'Filament\\Notifications\\DatabaseNotification',
                        'notifiable_type' => $user->getMorphClass(), 'notifiable_id' => $user->id,
                        'data' => json_encode(Notification::make()->title($payload['title'])->body($payload['body'])->getDatabaseMessage()),
                        'read_at' => null, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                } elseif ($row->channel === 'email') {
                    if (! config('vendor_lifecycle.email_enabled')) { $this->resetPending($id); continue; }
                    if (! filter_var($row->recipient, FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Invalid email address.');
                    $copy = isset($payload['bill_id']) ? app(VendorMonthlyBilling::class)->billCopy((int) $payload['bill_id']) : null;
                    Mail::raw($payload['body'], function ($message) use ($row, $payload, $copy): void {
                        $message->to($row->recipient)->subject($payload['title']);
                        if ($copy) $message->attachData($copy['bytes'], $copy['filename'], ['mime' => $copy['mime']]);
                    });
                } elseif ($row->channel === 'whatsapp') {
                    if (! config('vendor_lifecycle.whatsapp_enabled')) { $this->resetPending($id); continue; }
                    if (isset($payload['bill_id'])) {
                        $payload['parameters'][4] = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                            'vendor-lifecycle.shared-bill', now()->addHours(48), ['bill' => (int) $payload['bill_id']]
                        );
                    }
                    $result = WhatsAppService::sendByKey($payload['template_key'], $row->recipient, $payload['parameters']);
                    if (! ($result['status'] ?? false)) {
                        $error = (string) ($result['error'] ?? 'provider_rejected');
                        // The wrapper may hide an HTTP timeout/ambiguous response. Only clearly unsent configuration errors are retryable.
                        $retryable = in_array($error, ['template_not_ready', 'missing_mobile', 'missing_template_key'], true);
                        DB::table('vendor_delivery_logs')->where('id', $id)->update([
                            'status' => $retryable ? 'failed' : 'review', 'last_error' => Str::limit($error, 1000), 'updated_at' => now(),
                        ]);
                        continue;
                    }
                    $providerId = $result['message_id'] ?? null;
                } else throw new \RuntimeException('Unknown notification channel.');
                DB::table('vendor_delivery_logs')->where('id', $id)->update([
                    'status' => 'sent', 'provider_id' => $providerId, 'sent_at' => now(), 'last_error' => null, 'updated_at' => now(),
                ]);
                $sent++;
            } catch (\Throwable $exception) {
                DB::table('vendor_delivery_logs')->where('id', $id)->update([
                    'status' => 'review', 'last_error' => Str::limit($exception->getMessage(), 1000), 'updated_at' => now(),
                ]);
            }
        }
        return $sent;
    }

    private function resetPending(int $id): void
    {
        DB::table('vendor_delivery_logs')->where('id', $id)->update(['status' => 'pending', 'updated_at' => now()]);
    }
}
