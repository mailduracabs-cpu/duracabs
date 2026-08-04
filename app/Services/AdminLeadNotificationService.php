<?php

namespace App\Services;

use App\Models\CustomerSearchActivity;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminLeadNotificationService
{
    public const EVENT_SEARCHED = 'searched';
    public const EVENT_FARE_CHECKED = 'fare_checked';
    public const EVENT_VEHICLE_SELECTED = 'vehicle_selected';
    public const EVENT_CHECKOUT_STARTED = 'checkout_started';
    public const EVENT_PAYMENT_STARTED = 'payment_started';
    public const EVENT_PAYMENT_FAILED = 'payment_failed';
    public const EVENT_ABANDONED = 'abandoned';

    public function send(
        CustomerSearchActivity $lead,
        string $event
    ): void {
        if (! $this->shouldSend($lead, $event)) {
            return;
        }

        $numbers = $this->adminNumbers();

        if ($numbers === []) {
            Log::warning(
                'Admin lead WhatsApp skipped because recipients are missing.',
                [
                    'lead_id' => $lead->id,
                    'event' => $event,
                ]
            );

            return;
        }

        $message = $this->message($lead, $event);
        $allSuccessful = true;
        $responses = [];

        foreach ($numbers as $number) {
            try {
                $response = WhatsAppService::sendMessage(
                    $number,
                    $message
                );

                $success = (bool) (
                    $response['status']
                    ?? $response['success']
                    ?? false
                );

                $allSuccessful = $allSuccessful && $success;

                $responses[] = [
                    'number' => $this->maskMobile($number),
                    'success' => $success,
                    'message_id' => $response['message_id'] ?? null,
                    'error' => $response['error'] ?? null,
                ];
            } catch (Throwable $exception) {
                $allSuccessful = false;

                $responses[] = [
                    'number' => $this->maskMobile($number),
                    'success' => false,
                    'error' => $exception->getMessage(),
                ];

                Log::error(
                    'Admin lead WhatsApp recipient failed.',
                    [
                        'lead_id' => $lead->id,
                        'event' => $event,
                        'number' => $this->maskMobile($number),
                        'message' => $exception->getMessage(),
                    ]
                );
            }
        }

        $this->storeResult(
            $lead,
            $event,
            $allSuccessful,
            $responses
        );
    }

    private function shouldSend(
        CustomerSearchActivity $lead,
        string $event
    ): bool {
        if ($lead->is_converted) {
            return false;
        }

        $metadata = is_array($lead->metadata)
            ? $lead->metadata
            : [];

        $notifications = $metadata['admin_whatsapp_notifications']
            ?? [];

        $current = $notifications[$event] ?? [];

        if (! empty($current['sent_at'])) {
            return false;
        }

        /*
         * Same event is only attempted once every 10 minutes if the previous
         * delivery failed, preventing repeated clicks from spamming admins.
         */
        if (! empty($current['attempted_at'])) {
            try {
                return now()->diffInMinutes(
                    \Illuminate\Support\Carbon::parse(
                        $current['attempted_at']
                    )
                ) >= 10;
            } catch (Throwable) {
                return true;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function adminNumbers(): array
    {
        $sources = [
            (string) config(
                'services.whatsapp.admin_number',
                env('ADMIN_MOBILE', '')
            ),
            (string) env('WHATSAPP_STAFF_NUMBERS', ''),
        ];

        return collect($sources)
            ->flatMap(
                fn (string $value): array =>
                    preg_split('/[\s,;|]+/', $value) ?: []
            )
            ->map(
                fn (string $number): string =>
                    WhatsAppService::cleanNumber($number)
            )
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function message(
        CustomerSearchActivity $lead,
        string $event
    ): string {
        $title = match ($event) {
            self::EVENT_SEARCHED => 'New Customer Search',
            self::EVENT_FARE_CHECKED => 'Fare Checked',
            self::EVENT_VEHICLE_SELECTED => 'Vehicle Selected',
            self::EVENT_CHECKOUT_STARTED => 'Checkout Started',
            self::EVENT_PAYMENT_STARTED => 'Payment Started',
            self::EVENT_PAYMENT_FAILED => 'Payment Failed',
            self::EVENT_ABANDONED =>
                $lead->checkout_status
                    === CustomerSearchActivity::CHECKOUT_ABANDONED
                    ? 'Checkout Abandoned'
                    : 'Search Abandoned',
            default => 'Customer Lead Update',
        };

        $amount = $lead->grand_total
            ?? $lead->estimated_amount
            ?? $lead->minimum_result_price;

        $lines = [
            "🔔 *{$title}*",
            '',
            '*Lead ID:* ' . $lead->id,
            '*Customer:* ' . $lead->customer_display_name,
            '*Mobile:* ' . ($lead->mobile ?: 'Not available'),
            '*Service:* ' . $lead->service_label,
            '*Route:* ' . $lead->route_summary,
            '*Stage:* ' . $lead->stage_label,
        ];

        if (filled($lead->vehicle_name)) {
            $lines[] = '*Vehicle:* ' . $lead->vehicle_name;
        }

        if ($amount !== null) {
            $lines[] = '*Amount:* INR '
                . number_format((float) $amount, 2);
        }

        if ($lead->start_datetime) {
            $lines[] = '*Travel:* '
                . $lead->start_datetime->format('d M Y, h:i A');
        }

        $lines[] = '*Source:* '
            . ucfirst(str_replace('_', ' ', (string) $lead->source));

        $adminBase = rtrim(
            (string) config('app.url'),
            '/'
        );

        $lines[] = '';
        $lines[] = 'Open Lead: '
            . $adminBase
            . '/admin/customer-leads/'
            . $lead->id;

        return implode("\n", $lines);
    }

    /**
     * @param array<int, array<string, mixed>> $responses
     */
    private function storeResult(
        CustomerSearchActivity $lead,
        string $event,
        bool $success,
        array $responses
    ): void {
        $metadata = is_array($lead->metadata)
            ? $lead->metadata
            : [];

        $current = $metadata[
            'admin_whatsapp_notifications'
        ][$event] ?? [];

        $metadata['admin_whatsapp_notifications'][$event] = [
            'attempt_count' =>
                (int) ($current['attempt_count'] ?? 0) + 1,
            'attempted_at' => now()->toIso8601String(),
            'sent_at' => $success
                ? now()->toIso8601String()
                : ($current['sent_at'] ?? null),
            'status' => $success ? 'sent' : 'failed',
            'responses' => $responses,
        ];

        $lead->forceFill([
            'metadata' => $metadata,
            'admin_notified' =>
                (bool) $lead->admin_notified || $success,
            'admin_notified_at' => $success
                ? ($lead->admin_notified_at ?? now())
                : $lead->admin_notified_at,
            'whatsapp_notified' =>
                (bool) $lead->whatsapp_notified || $success,
            'whatsapp_notified_at' => $success
                ? ($lead->whatsapp_notified_at ?? now())
                : $lead->whatsapp_notified_at,
        ])->saveQuietly();
    }

    private function maskMobile(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (strlen($digits) <= 4) {
            return $digits;
        }

        return str_repeat('*', strlen($digits) - 4)
            . substr($digits, -4);
    }
}