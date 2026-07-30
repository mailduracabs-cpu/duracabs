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

            if (
                $campaign->audience_type
                === WhatsAppCampaign::AUDIENCE_MANUAL
            ) {
                $numbers = data_get(
                    $campaign->audience_data,
                    'normalized_numbers',
                    []
                );

                if (! is_array($numbers)) {
                    $numbers = $this->prepareMobileNumbers($numbers);
                }

                $recipientRows = [];
                $now = now();

                $encodedVariables = $campaign->template_variables !== null
                    ? json_encode(
                        $campaign->template_variables,
                        JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                        | JSON_THROW_ON_ERROR
                    )
                    : null;

                foreach ($numbers as $number) {
                    $recipientRows[] = [
                        'campaign_id' => $campaign->getKey(),
                        'customer_id' => null,
                        'name' => null,
                        'mobile' => $number,
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

                if ($recipientRows !== []) {
                    WhatsAppCampaignRecipient::query()
                        ->insert($recipientRows);
                }

                $recipientCount = count($recipientRows);

                $campaign->forceFill([
                    'total_recipients' => $recipientCount,
                    'pending_count' => $recipientCount,
                ])->saveQuietly();
            }

            return $campaign->fresh();
        });
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
                    ? "{$recipientCount} recipients campaign me add kiye gaye hain."
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
            $number = preg_replace(
                '/\D+/',
                '',
                (string) $rawNumber
            ) ?? '';

            if ($number === '') {
                continue;
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
                continue;
            }

            $numbers[] = $number;
        }

        return array_values(array_unique($numbers));
    }
}