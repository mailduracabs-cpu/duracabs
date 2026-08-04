<?php

namespace App\Services;

use App\Models\CustomerSearchActivity;
use App\Models\FleetManagement\TransporterProfile;
use App\Models\User;
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

    public function sendNewCustomerRegistration(
        User $user
    ): void {
        $user->loadMissing('roles');

        if (
            $user->hasAnyRole([
                'Admin',
                'Transporter',
                'moderator',
                'Driver',
            ])
            || filled($user->company_name)
            || filled($user->gst_number)
        ) {
            return;
        }

        try {
            $result = WhatsAppService::dispatchEvent(
                'customer.registered',
                [
                    'customer_id' => (string) $user->id,
                    'customer_name' =>
                        trim((string) $user->name)
                        ?: 'Customer',
                    'customer_mobile' =>
                        trim((string) $user->mobile)
                        ?: 'Not available',
                    'customer_email' =>
                        trim((string) $user->email)
                        ?: 'Not available',
                    'registration_time' =>
                        optional($user->created_at)
                            ?->timezone(
                                (string) config(
                                    'app.timezone',
                                    'Asia/Kolkata'
                                )
                            )
                            ?->format('d F Y, h:i A')
                        ?? now()->format('d F Y, h:i A'),
                ]
            );

            if (! ($result['status'] ?? false)) {
                Log::warning(
                    'New customer admin WhatsApp dispatch was not fully successful.',
                    [
                        'customer_id' => $user->id,
                        'result' => $result,
                    ]
                );
            }
        } catch (Throwable $exception) {
            Log::error(
                'New customer admin WhatsApp dispatch failed.',
                [
                    'customer_id' => $user->id,
                    'message' => $exception->getMessage(),
                ]
            );
        }
    }

    public function sendNewVendorRegistration(
        TransporterProfile $profile
    ): void {
        $profile->loadMissing('user');

        $vendorId = 'V' . str_pad(
            (string) $profile->id,
            4,
            '0',
            STR_PAD_LEFT
        );

        try {
            $result = WhatsAppService::dispatchEvent(
                'vendor.registered',
                [
                    'vendor_id' => $vendorId,
                    'vendor_name' => trim((string) (
                        $profile->contact_person
                        ?: $profile->user?->name
                        ?: 'Vendor'
                    )),
                    'vendor_mobile' => trim((string) (
                        $profile->mobile
                        ?: $profile->whatsapp_number
                        ?: $profile->user?->mobile
                        ?: 'Not available'
                    )),
                    'city' => trim((string) (
                        $profile->city ?: 'Not available'
                    )),
                    'business_name' => trim((string) (
                        $profile->company_name
                        ?: $profile->user?->company_name
                        ?: 'Not available'
                    )),
                ]
            );

            if (! ($result['status'] ?? false)) {
                Log::warning(
                    'New vendor admin WhatsApp dispatch was not fully successful.',
                    [
                        'vendor_profile_id' => $profile->id,
                        'result' => $result,
                    ]
                );
            }
        } catch (Throwable $exception) {
            Log::error(
                'New vendor admin WhatsApp dispatch failed.',
                [
                    'vendor_profile_id' => $profile->id,
                    'message' => $exception->getMessage(),
                ]
            );
        }
    }

    public function send(
        CustomerSearchActivity $lead,
        string $event
    ): void {
        if (! $this->shouldSend($lead, $event)) {
            return;
        }

        $eventKey = $this->notificationEventKey($event);

        try {
            $result = WhatsAppService::dispatchEvent(
                $eventKey,
                $this->leadEventData(
                    $lead,
                    $event
                )
            );

            $success = (bool) (
                $result['status']
                ?? false
            );

            $this->storeResult(
                $lead,
                $event,
                $success,
                is_array($result['results'] ?? null)
                    ? $result['results']
                    : [$result]
            );

            if (! $success) {
                Log::warning(
                    'Admin lead WhatsApp dispatch was not fully successful.',
                    [
                        'lead_id' => $lead->id,
                        'event' => $event,
                        'event_key' => $eventKey,
                        'result' => $result,
                    ]
                );
            }
        } catch (Throwable $exception) {
            $this->storeResult(
                $lead,
                $event,
                false,
                [[
                    'success' => false,
                    'error' => $exception->getMessage(),
                ]]
            );

            Log::error(
                'Admin lead WhatsApp dispatch failed.',
                [
                    'lead_id' => $lead->id,
                    'event' => $event,
                    'event_key' => $eventKey,
                    'message' => $exception->getMessage(),
                ]
            );
        }
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

    private function eventTitle(
        CustomerSearchActivity $lead,
        string $event
    ): string {
        return match ($event) {
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
    }

    private function notificationEventKey(
        string $event
    ): string {
        return match ($event) {
            self::EVENT_SEARCHED =>
                'lead.searched',

            self::EVENT_FARE_CHECKED =>
                'lead.fare_checked',

            self::EVENT_VEHICLE_SELECTED =>
                'lead.vehicle_selected',

            self::EVENT_CHECKOUT_STARTED =>
                'lead.checkout_started',

            self::EVENT_PAYMENT_STARTED =>
                'lead.payment_started',

            self::EVENT_PAYMENT_FAILED =>
                'lead.payment_failed',

            self::EVENT_ABANDONED =>
                'lead.abandoned',

            default =>
                'lead.updated',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function leadEventData(
        CustomerSearchActivity $lead,
        string $event
    ): array {
        $amount = $lead->grand_total
            ?? $lead->estimated_amount
            ?? $lead->minimum_result_price
            ?? 0;

        return [
            'event' => $this->eventTitle(
                $lead,
                $event
            ),

            'customer_name' =>
                trim((string) $lead->customer_display_name)
                ?: 'Guest Customer',

            'customer_mobile' =>
                trim((string) $lead->mobile)
                ?: 'Not available',

            'service_type' =>
                trim((string) $lead->service_label)
                ?: 'Not available',

            'route' =>
                trim((string) $lead->route_summary)
                ?: 'Not available',

            'travel_date' =>
                $lead->start_datetime
                    ? $lead->start_datetime->format(
                        'd M Y, h:i A'
                    )
                    : 'Not available',

            'vehicle_name' => trim((string) (
                $lead->vehicle_name
                ?: $lead->vehicle_category_name
                ?: 'Not selected'
            )),

            'total_amount' =>
                number_format(
                    (float) $amount,
                    2,
                    '.',
                    ''
                ),

            'lead_stage' =>
                trim((string) $lead->stage_label)
                ?: 'Not available',

            'lead_id' => (string) $lead->id,

            'lead_url' => $this->leadUrl($lead),
        ];
    }

    private function leadUrl(
        CustomerSearchActivity $lead
    ): string {
        return rtrim(
            (string) config('app.url'),
            '/'
        )
            . '/admin/customer-leads/'
            . $lead->id
            . '/edit';
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

}