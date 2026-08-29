<?php

namespace App\Filament\Resources\WhatsAppCampaignResource\Pages;

use App\Filament\Resources\WhatsAppCampaignResource;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateWhatsAppCampaign extends CreateRecord
{
    protected static string $resource = WhatsAppCampaignResource::class;

    protected static ?string $title = 'Create WhatsApp Campaign';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $data['total_recipients'] = 0;
        $data['pending_count'] = 0;
        $data['sent_count'] = 0;
        $data['delivered_count'] = 0;
        $data['read_count'] = 0;
        $data['failed_count'] = 0;

        $data['started_at'] = null;
        $data['completed_at'] = null;
        $data['cancelled_at'] = null;
        $data['failure_reason'] = null;

        if (
            ($data['status'] ?? null)
            !== WhatsAppCampaign::STATUS_SCHEDULED
        ) {
            $data['status'] = WhatsAppCampaign::STATUS_DRAFT;
            $data['scheduled_at'] = null;
        }

        if (
            ($data['audience_type'] ?? null)
            === WhatsAppCampaign::AUDIENCE_MANUAL
        ) {
            $manualNumbers = data_get(
                $data,
                'audience_data.manual_numbers',
                ''
            );

            $numbers = $this->prepareMobileNumbers($manualNumbers);

            if ($numbers === []) {
                throw ValidationException::withMessages([
                    'data.audience_data.manual_numbers' =>
                        'Kam se kam ek valid WhatsApp mobile number enter karein.',
                ]);
            }

            $data['audience_data'] = [
                'manual_numbers' => implode(PHP_EOL, $numbers),
                'normalized_numbers' => $numbers,
                'recipient_count' => count($numbers),
            ];
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): WhatsAppCampaign
    {
        return DB::transaction(function () use ($data): WhatsAppCampaign {
            /** @var WhatsAppCampaign $campaign */
            $campaign = WhatsAppCampaign::query()->create($data);

            $recipients = $this->prepareAudienceRecipients($campaign);

            if ($recipients === []) {
                throw ValidationException::withMessages([
                    'data.audience_type' =>
                        'Selected audience me koi valid WhatsApp customer nahi mila.',
                ]);
            }

            $encodedVariables = $campaign->template_variables !== null
                ? json_encode(
                    $campaign->template_variables,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
                )
                : null;

            $recipientRows = [];
            $now = now();

            foreach ($recipients as $recipient) {
                $recipientRows[] = [
                    'campaign_id' => $campaign->getKey(),
                    'customer_id' => $recipient['customer_id'] ?? null,
                    'name' => $recipient['name'] ?? null,
                    'mobile' => $recipient['mobile'],
                    'wa_id' => null,
                    'template_name' => $campaign->template_name,
                    'variables' => $encodedVariables,
                    'status' =>
                        WhatsAppCampaignRecipient::STATUS_PENDING,
                    'retry_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($recipientRows, 500) as $chunk) {
                WhatsAppCampaignRecipient::query()->insert($chunk);
            }

            $recipientCount = count($recipientRows);

            $audienceData = is_array($campaign->audience_data)
                ? $campaign->audience_data
                : [];

            $audienceData['recipient_count'] = $recipientCount;
            $audienceData['prepared_at'] = now()->toDateTimeString();

            $campaign->forceFill([
                'audience_data' => $audienceData,
                'total_recipients' => $recipientCount,
                'pending_count' => $recipientCount,
            ])->saveQuietly();

            return $campaign->fresh();
        });
    }

    /**
     * Campaign audience ke hisaab se unique recipients prepare karega.
     *
     * @return array<int, array{
     *     customer_id: int|null,
     *     name: string|null,
     *     mobile: string
     * }>
     */
    private function prepareAudienceRecipients(
        WhatsAppCampaign $campaign
    ): array {
        return match ($campaign->audience_type) {
            WhatsAppCampaign::AUDIENCE_ALL_CUSTOMERS =>
                $this->getAllCustomers(),

            WhatsAppCampaign::AUDIENCE_SELF_DRIVE =>
                $this->getSelfDriveCustomers(),

            WhatsAppCampaign::AUDIENCE_TAXI =>
                $this->getTaxiCustomers(),

            WhatsAppCampaign::AUDIENCE_MANUAL =>
                $this->getManualCustomers($campaign),

            default => [],
        };
    }

    /**
     * Sabhi registered customers.
     */
    private function getAllCustomers(): array
    {
        $recipients = [];

        DB::table('users')
            ->select([
                'id',
                'name',
                'mobile',
            ])
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->orderBy('id')
            ->chunkById(
                500,
                function ($users) use (&$recipients): void {
                    foreach ($users as $user) {
                        $this->addRecipient(
                            $recipients,
                            $user->mobile,
                            $user->name,
                            $user->id
                        );
                    }
                },
                'id'
            );

        return array_values($recipients);
    }

    /**
     * Self Drive audience.
     *
     * Sources:
     * - customer_search_activities
     * - self_drive_bookings
     */
    private function getSelfDriveCustomers(): array
    {
        $recipients = [];

        /*
         * Self Drive search / inquiry customers.
         */
        DB::table('customer_search_activities')
            ->select([
                'user_id',
                'mobile',
                'customer_name',
            ])
            ->where(function ($query): void {
                $query
                    ->where('module', 'self_drive')
                    ->orWhere('service_type', 'self_drive');
            })
            ->whereNotNull('mobile')
            ->get()
            ->each(function ($row) use (&$recipients): void {
                $this->addRecipient(
                    $recipients,
                    $row->mobile,
                    $row->customer_name,
                    $row->user_id
                );
            });

        /*
         * Actual Self Drive bookings.
         */
        DB::table('self_drive_bookings as bookings')
            ->join(
                'users',
                'users.id',
                '=',
                'bookings.customer_id'
            )
            ->select([
                'users.id as customer_id',
                'users.name',
                'users.mobile',
            ])
            ->whereNotNull('users.mobile')
            ->get()
            ->each(function ($row) use (&$recipients): void {
                $this->addRecipient(
                    $recipients,
                    $row->mobile,
                    $row->name,
                    $row->customer_id
                );
            });

        return array_values($recipients);
    }

    /**
     * With Driver / Taxi audience.
     *
     * Sources:
     * - customer_search_activities
     * - customer_activities
     * - ride_inquiries
     * - orders
     */
    private function getTaxiCustomers(): array
    {
        $recipients = [];

        /*
         * Taxi searches.
         */
        DB::table('customer_search_activities')
            ->select([
                'user_id',
                'mobile',
                'customer_name',
            ])
            ->where(function ($query): void {
                $query
                    ->where('module', 'taxi')
                    ->orWhereIn('service_type', [
                        'one_way',
                        'round_trip',
                        'return',
                        'local',
                    ]);
            })
            ->whereNotNull('mobile')
            ->get()
            ->each(function ($row) use (&$recipients): void {
                $this->addRecipient(
                    $recipients,
                    $row->mobile,
                    $row->customer_name,
                    $row->user_id
                );
            });

        /*
         * Taxi customer activities.
         */
        DB::table('customer_activities')
            ->select([
                'user_id',
                'mobile',
                'customer_name',
            ])
            ->where(function ($query): void {
                $query
                    ->where('module', 'taxi')
                    ->orWhereIn('service_type', [
                        'one_way',
                        'round_trip',
                        'return',
                        'local',
                    ]);
            })
            ->whereNotNull('mobile')
            ->get()
            ->each(function ($row) use (&$recipients): void {
                $this->addRecipient(
                    $recipients,
                    $row->mobile,
                    $row->customer_name,
                    $row->user_id
                );
            });

        /*
         * Ride inquiry customers.
         */
        DB::table('ride_inquiries')
            ->select([
                'user_id',
                'mobile',
                'customer_name',
            ])
            ->whereNotNull('mobile')
            ->get()
            ->each(function ($row) use (&$recipients): void {
                $this->addRecipient(
                    $recipients,
                    $row->mobile,
                    $row->customer_name,
                    $row->user_id
                );
            });

        /*
         * Actual taxi/with-driver bookings.
         */
        DB::table('orders')
            ->join(
                'users',
                'users.id',
                '=',
                'orders.user_id'
            )
            ->select([
                'users.id as customer_id',
                'users.name',
                'users.mobile',
            ])
            ->whereNotNull('users.mobile')
            ->get()
            ->each(function ($row) use (&$recipients): void {
                $this->addRecipient(
                    $recipients,
                    $row->mobile,
                    $row->name,
                    $row->customer_id
                );
            });

        return array_values($recipients);
    }

    /**
     * Manual pasted mobile numbers.
     */
    private function getManualCustomers(
        WhatsAppCampaign $campaign
    ): array {
        $numbers = data_get(
            $campaign->audience_data,
            'normalized_numbers',
            []
        );

        if (! is_array($numbers)) {
            $numbers = $this->prepareMobileNumbers($numbers);
        }

        $recipients = [];

        foreach ($numbers as $number) {
            $this->addRecipient(
                $recipients,
                $number,
                null,
                null
            );
        }

        return array_values($recipients);
    }

    /**
     * Recipient add + normalize + duplicate remove.
     */
    private function addRecipient(
        array &$recipients,
        mixed $mobile,
        mixed $name = null,
        mixed $customerId = null
    ): void {
        $number = $this->normalizeMobile($mobile);

        if ($number === null) {
            return;
        }

        /*
         * Agar duplicate number pehle aa chuka hai to available
         * customer/name information preserve/update karenge.
         */
        if (isset($recipients[$number])) {
            if (
                empty($recipients[$number]['customer_id'])
                && ! empty($customerId)
            ) {
                $recipients[$number]['customer_id'] =
                    (int) $customerId;
            }

            if (
                empty($recipients[$number]['name'])
                && ! empty(trim((string) $name))
            ) {
                $recipients[$number]['name'] =
                    trim((string) $name);
            }

            return;
        }

        $cleanName = trim((string) $name);

        if ($cleanName === '' || $cleanName === $number) {
            $cleanName = null;
        }

        $recipients[$number] = [
            'customer_id' => ! empty($customerId)
                ? (int) $customerId
                : null,
            'name' => $cleanName,
            'mobile' => $number,
        ];
    }

    protected function getCreatedNotification(): ?Notification
    {
        $recipientCount = (int) (
            $this->record?->total_recipients ?? 0
        );

        return Notification::make()
            ->success()
            ->title('WhatsApp campaign created')
            ->body(
                $recipientCount > 0
                    ? "{$recipientCount} unique recipients campaign me add kiye gaye hain."
                    : 'Campaign save ho gaya hai.'
            )
            ->duration(6000);
    }

    protected function getRedirectUrl(): string
    {
        return WhatsAppCampaignResource::getUrl('index');
    }

    private function prepareMobileNumbers(
        array|string|null $value
    ): array {
        if (is_array($value)) {
            $rawNumbers = $value;
        } else {
            $rawNumbers = preg_split(
                '/[\r\n,;]+/',
                (string) $value
            ) ?: [];
        }

        $numbers = [];

        foreach ($rawNumbers as $rawNumber) {
            $number = $this->normalizeMobile($rawNumber);

            if ($number === null) {
                continue;
            }

            $numbers[] = $number;
        }

        return array_values(array_unique($numbers));
    }

    private function normalizeMobile(
        mixed $value
    ): ?string {
        $number = preg_replace(
            '/\D+/',
            '',
            (string) $value
        ) ?? '';

        if ($number === '') {
            return null;
        }

        if (strlen($number) === 10) {
            $number = '91' . $number;
        }

        if (
            strlen($number) === 11
            && str_starts_with($number, '0')
        ) {
            $number = '91' . substr($number, 1);
        }

        if (
            strlen($number) < 10
            || strlen($number) > 15
        ) {
            return null;
        }

        return $number;
    }
}