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

        /*
         * Admin, moderator, driver and transporter accounts are not customer
         * registrations. Company-based accounts are also left for the vendor
         * registration notification.
         */
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

        $numbers = $this->adminNumbers();

        if ($numbers === []) {
            Log::warning(
                'New customer admin WhatsApp skipped because recipients are missing.',
                ['customer_id' => $user->id]
            );

            return;
        }

        $parameters = [
            (string) $user->id,
            trim((string) $user->name) ?: 'Customer',
            trim((string) $user->mobile) ?: 'Not available',
            trim((string) $user->email) ?: 'Not available',
            optional($user->created_at)
                ?->timezone(
                    (string) config('app.timezone', 'Asia/Kolkata')
                )
                ?->format('d F Y, h:i A')
                ?? now()->format('d F Y, h:i A'),
        ];

        foreach ($numbers as $number) {
            try {
                $response = WhatsAppService::sendTemplate(
                    number: $number,
                    templateName: (string) config(
                        'services.whatsapp.templates.admin_new_customer',
                        'admin_new_customer_v1'
                    ),
                    languageCode: (string) config(
                        'services.whatsapp.default_language',
                        'en'
                    ),
                    bodyParameters: $parameters
                );

                $success = (bool) (
                    $response['status']
                    ?? $response['success']
                    ?? false
                );

                if (! $success) {
                    Log::warning(
                        'New customer admin WhatsApp was not accepted.',
                        [
                            'customer_id' => $user->id,
                            'number' => $this->maskMobile($number),
                            'result' => $response,
                        ]
                    );
                }
            } catch (Throwable $exception) {
                Log::error(
                    'New customer admin WhatsApp failed.',
                    [
                        'customer_id' => $user->id,
                        'number' => $this->maskMobile($number),
                        'message' => $exception->getMessage(),
                    ]
                );
            }
        }
    }

    public function sendNewVendorRegistration(
        TransporterProfile $profile
    ): void {
        $profile->loadMissing('user');

        $numbers = $this->adminNumbers();

        if ($numbers === []) {
            Log::warning(
                'New vendor admin WhatsApp skipped because recipients are missing.',
                ['vendor_profile_id' => $profile->id]
            );

            return;
        }

        $vendorId = 'V' . str_pad(
            (string) $profile->id,
            4,
            '0',
            STR_PAD_LEFT
        );

        $vendorName = trim((string) (
            $profile->contact_person
            ?: $profile->user?->name
            ?: 'Vendor'
        ));

        $vendorMobile = trim((string) (
            $profile->mobile
            ?: $profile->whatsapp_number
            ?: $profile->user?->mobile
            ?: 'Not available'
        ));

        $city = trim((string) (
            $profile->city ?: 'Not available'
        ));

        $businessName = trim((string) (
            $profile->company_name
            ?: $profile->user?->company_name
            ?: 'Not available'
        ));

        $parameters = [
            $vendorId,
            $vendorName,
            $vendorMobile,
            $city,
            $businessName,
        ];

        foreach ($numbers as $number) {
            try {
                $response = WhatsAppService::sendTemplate(
                    number: $number,
                    templateName: (string) config(
                        'services.whatsapp.templates.admin_new_vendor_registration',
                        'admin_new_vendor_registration_v1'
                    ),
                    languageCode: (string) config(
                        'services.whatsapp.default_language',
                        'en'
                    ),
                    bodyParameters: $parameters
                );

                $success = (bool) (
                    $response['status']
                    ?? $response['success']
                    ?? false
                );

                if (! $success) {
                    Log::warning(
                        'New vendor admin WhatsApp was not accepted.',
                        [
                            'vendor_profile_id' => $profile->id,
                            'number' => $this->maskMobile($number),
                            'result' => $response,
                        ]
                    );
                }
            } catch (Throwable $exception) {
                Log::error(
                    'New vendor admin WhatsApp failed.',
                    [
                        'vendor_profile_id' => $profile->id,
                        'number' => $this->maskMobile($number),
                        'message' => $exception->getMessage(),
                    ]
                );
            }
        }
    }

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

        $templateParameters = $this->templateParameters(
            $lead,
            $event
        );

        $allSuccessful = true;
        $responses = [];

        foreach ($numbers as $number) {
            try {
                $response = WhatsAppService::sendTemplate(
                    number: $number,
                    templateName: (string) config(
                        'services.whatsapp.templates.admin_customer_enquiry',
                        'admin_customer_enquiry_v1'
                    ),
                    languageCode: (string) config(
                        'services.whatsapp.default_language',
                        'en'
                    ),
                    bodyParameters: $templateParameters
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

    /**
     * Exact variable order for admin_customer_enquiry_v1:
     * 1 event, 2 customer, 3 mobile, 4 service, 5 route,
     * 6 travel date, 7 vehicle, 8 amount, 9 stage,
     * 10 lead ID, 11 lead URL.
     *
     * @return array<int, string>
     */
    private function templateParameters(
        CustomerSearchActivity $lead,
        string $event
    ): array {
        $amount = $lead->grand_total
            ?? $lead->estimated_amount
            ?? $lead->minimum_result_price
            ?? 0;

        $travelDate = $lead->start_datetime
            ? $lead->start_datetime->format('d M Y, h:i A')
            : 'Not available';

        $vehicle = trim((string) (
            $lead->vehicle_name
            ?: $lead->vehicle_category_name
            ?: 'Not selected'
        ));

        return [
            $this->eventTitle($lead, $event),
            trim((string) $lead->customer_display_name)
                ?: 'Guest Customer',
            trim((string) $lead->mobile)
                ?: 'Not available',
            trim((string) $lead->service_label)
                ?: 'Not available',
            trim((string) $lead->route_summary)
                ?: 'Not available',
            $travelDate,
            $vehicle,
            number_format((float) $amount, 2, '.', ''),
            trim((string) $lead->stage_label)
                ?: 'Not available',
            (string) $lead->id,
            $this->leadUrl($lead),
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