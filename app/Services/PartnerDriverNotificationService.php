<?php

declare(strict_types=1);
namespace App\Services;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PartnerDriverNotificationService
{
    private const EVENT = 'partner_driver_assignment';
    public static function revision($order): string
    {
        return hash('sha256', json_encode([$order->id, $order->transporter_id, $order->driver_id,
            $order->vehicle_id, $order->status, $order->payment_status, $order->date, $order->dateTo,
            $order->time, $order->endTime, $order->updated_at,
            $order->partner_offer_revision ?? null, $order->partner_offer_status ?? null,
            $order->partner_offer_accepted_by ?? null]));
    }
    private function driverRevision(User $driver): string
    {
        return hash('sha256', $driver->name.'|'.$driver->mobile.'|'.$driver->updated_at);
    }
    public function latest(int $booking)
    {
        if (!Schema::hasTable('notification_delivery_logs')) return null;
        return DB::table('notification_delivery_logs')->where('notifiable_type', 'App\\Models\\Order')
            ->where('notifiable_id', $booking)->where('event', self::EVENT)->orderByDesc('id')->first();
    }
    public function currentSummary($order): ?array
    {
        $log = $this->latest($order->id);
        if (!$log) return null;
        $payload = json_decode($log->payload ?: '{}', true);
        $driver = User::query()->find($order->driver_id);
        if (!$driver || ($payload['revision'] ?? '') !== self::revision($order)
            || ($payload['driver_revision'] ?? '') !== $this->driverRevision($driver)) return null;
        return $this->summary($log);
    }
    public function summary($log): ?array
    {
        if (!$log) return null;
        return ['status' => $log->status, 'attempted_at' => $log->updated_at,
            'message' => match ($log->status) {
                'accepted' => 'WhatsApp gateway accepted the driver notification. Delivery/read confirmation is not recorded here.',
                'failed' => 'WhatsApp request failed. The assignment is saved; check WhatsApp configuration and retry.',
                'unknown' => 'Sending could not be confirmed. A manual retry may create a duplicate message.',
                'superseded' => 'The saved assignment/contact changed before sending. Refresh and retry for the current driver.',
                default => 'Driver notification is pending or being sent.',
            }];
    }
    public function prepare(int $profile, $order, User $driver, Vehicle $vehicle, bool $retry = false): int
    {
        abort_unless(Schema::hasTable('notification_delivery_logs'), 503,
            'Install the existing notification_delivery_logs migration listed in SETUP.md before assigning.');
        $latest = $this->latest($order->id);
        $old = $latest ? json_decode($latest->payload ?: '{}', true) : [];
        $fingerprint = self::revision($order);
        if ($latest && ($old['revision'] ?? '') === $fingerprint && ($old['driver_revision'] ?? '') === $this->driverRevision($driver)) {
            if (!$retry || $latest->status === 'accepted') return (int) $latest->id;
            abort_if($latest->status === 'processing' && strtotime($latest->updated_at) > time() - 300,
                409, 'The WhatsApp request is still being processed. Wait before retrying.');
            if ($latest->status === 'processing') DB::table('notification_delivery_logs')->where('id', $latest->id)
                ->update(['status' => 'unknown', 'updated_at' => now()]);
        }
        $customer = !empty($order->user_id) ? User::query()->find($order->user_id) : null;
        $address = Schema::hasTable('addresses') && Schema::hasColumn('addresses', 'order_id')
            ? DB::table('addresses')->where('order_id', $order->id)->orderByDesc('id')->first() : null;
        $text = fn ($value) => trim(str_replace(["\r", "\n"], ' ', (string) $value)) ?: 'Not recorded';
        $fields = [
            $text($order->booking_no ?: $order->id), $text($driver->name),
            $text($address->full_name ?? $customer?->name), $text($address->phone ?? $customer?->mobile),
            $text(str_replace('_', ' ', $order->ride_type ?? 'taxi')),
            $text(($order->date ?? '').' '.($order->time ?? '')),
            $text(($order->dateTo ?: $order->date).' '.($order->endTime ?? '')),
            $text($address->pickup_address ?? $order->booking_from ?? null),
            $text($address->drop_address ?? $order->booking_to ?? null),
            $text($vehicle->vehicle_number), $text($vehicle->car_company_name.' '.$vehicle->model_name),
            $text($vehicle->car_classification),
        ];
        $labels = ['Booking', 'Driver', 'Customer', 'Customer mobile', 'Trip type', 'Pickup date/time',
            'Return date/time', 'Pickup', 'Drop', 'Taxi number', 'Taxi', 'Category'];
        $message = "*DuraCabs — Booking Assignment*\n\n";
        foreach ($labels as $i => $label) $message .= $label.': '.$fields[$i]."\n";
        $message .= "\nPlease contact your Vendor for trip coordination.";
        return (int) DB::table('notification_delivery_logs')->insertGetId([
            'notifiable_type' => 'App\\Models\\Order', 'notifiable_id' => $order->id,
            'channel' => 'whatsapp', 'recipient' => $driver->mobile, 'event' => self::EVENT,
            'status' => 'pending', 'subject' => 'Driver booking assignment', 'message' => $message,
            'payload' => json_encode(['profile_id' => $profile, 'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id,
                'revision' => $fingerprint, 'driver_revision' => $this->driverRevision($driver), 'parameters' => $fields]),
            'retry_count' => $retry ? (int) ($latest->retry_count ?? 0) + 1 : 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
    public function send(int $id): array
    {
        $log = DB::transaction(function () use ($id) {
            $log = DB::table('notification_delivery_logs')->where('id', $id)->lockForUpdate()->first();
            abort_unless($log, 404);
            if ($log->status !== 'pending') return $log;
            $payload = json_decode($log->payload ?: '{}', true);
            $order = DB::table('orders')->where('id', $log->notifiable_id)->lockForUpdate()->first();
            $driver = User::query()->whereKey($payload['driver_id'] ?? 0)->lockForUpdate()->first();
            $valid = $order && PartnerBookingOfferService::accepted($order)
                && PartnerBookingOfferService::open($order) && $driver && $driver->canUseDriverLogin()
                && (int) $driver->getAttribute('partner_driver_profile_id') === (int) ($payload['profile_id'] ?? 0)
                && $driver->getAttribute('partner_driver_removed_at') === null
                && self::revision($order) === ($payload['revision'] ?? '')
                && $this->driverRevision($driver) === ($payload['driver_revision'] ?? '')
                && $driver->mobile === $log->recipient;
            DB::table('notification_delivery_logs')->where('id', $id)->update([
                'status' => $valid ? 'processing' : 'superseded', 'updated_at' => now()]);
            $log->status = $valid ? 'processing' : 'superseded';
            $log->claimed = $valid;
            return $log;
        });
        if (empty($log->claimed)) return $this->summary($log);
        $payload = json_decode($log->payload ?: '{}', true);
        try {
            $key = trim((string) config('duracabs_partner_notifications.driver_assignment_template_key', ''));
            $result = $key !== ''
                ? WhatsAppService::sendByKey($key, $log->recipient, $payload['parameters'] ?? [])
                : WhatsAppService::sendMessage($log->recipient, $log->message);
            $accepted = ($result['status'] ?? false) === true;
            DB::table('notification_delivery_logs')->where('id', $id)->update([
                'status' => $accepted ? 'accepted' : 'failed', 'sent_at' => $accepted ? now() : null,
                'failed_at' => $accepted ? null : now(), 'failure_reason' => $accepted ? null : 'WhatsApp service rejected this request. Check gateway/template configuration.',
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // A timeout may occur after provider acceptance; do not auto-retry or roll back assignment.
            DB::table('notification_delivery_logs')->where('id', $id)->update(['status' => 'unknown',
                'failure_reason' => 'WhatsApp result was not confirmed.', 'failed_at' => now(), 'updated_at' => now()]);
        }
        return $this->summary(DB::table('notification_delivery_logs')->where('id', $id)->first());
    }
}
