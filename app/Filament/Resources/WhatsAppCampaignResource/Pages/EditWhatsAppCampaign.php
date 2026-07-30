<?php

namespace App\Filament\Resources\WhatsAppCampaignResource\Pages;

use App\Filament\Resources\WhatsAppCampaignResource;
use App\Jobs\SendWhatsAppCampaign;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditWhatsAppCampaign extends EditRecord
{
    protected static string $resource = WhatsAppCampaignResource::class;

    protected static ?string $title = 'Edit WhatsApp Campaign';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sendTest')
                ->label('Send Test')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\TextInput::make('test_number')
                        ->label('Test WhatsApp Number')
                        ->placeholder('917088873331')
                        ->helperText(
                            'Mobile number country code ke saath enter karein.'
                        )
                        ->tel()
                        ->required()
                        ->maxLength(15),
                ])
                ->visible(
                    fn (): bool => $this->record->canBeEdited()
                )
                ->action(function (array $data): void {
                    $number = $this->normalizeMobileNumber(
                        $data['test_number'] ?? ''
                    );

                    if ($number === null) {
                        throw ValidationException::withMessages([
                            'test_number' =>
                                'Valid WhatsApp mobile number enter karein.',
                        ]);
                    }

                    DB::transaction(function () use ($number): void {
                        $recipient = $this->record
                            ->recipients()
                            ->where('mobile', $number)
                            ->first();

                        if (! $recipient) {
                            $recipient = $this->record
                                ->recipients()
                                ->create([
                                    'customer_id' => null,
                                    'name' => 'Test Customer',
                                    'mobile' => $number,
                                    'wa_id' => null,
                                    'template_name' =>
                                        $this->record->template_name,
                                    'variables' =>
                                        $this->record->template_variables,
                                    'status' =>
                                        WhatsAppCampaignRecipient::STATUS_PENDING,
                                    'retry_count' => 0,
                                ]);
                        } else {
                            $recipient->forceFill([
                                'template_name' =>
                                    $this->record->template_name,
                                'variables' =>
                                    $this->record->template_variables,
                                'status' =>
                                    WhatsAppCampaignRecipient::STATUS_PENDING,
                                'failure_reason' => null,
                                'failed_at' => null,
                                'retry_count' => 0,
                            ])->save();
                        }

                        $this->record->refreshCounters();
                    });

                    SendWhatsAppCampaign::dispatch(
                        $this->record->getKey()
                    )->onQueue('whatsapp');

                    Notification::make()
                        ->success()
                        ->title('Test message queued')
                        ->body(
                            "Test WhatsApp message {$number} ke liye queue me add ho gaya hai."
                        )
                        ->send();
                }),

            Actions\Action::make('sendNow')
                ->label('Send Now')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Send WhatsApp Campaign')
                ->modalDescription(
                    'Campaign ke sabhi pending recipients ko WhatsApp message bheja jayega.'
                )
                ->modalSubmitActionLabel('Yes, Send Now')
                ->visible(
                    fn (): bool => in_array(
                        $this->record->status,
                        [
                            WhatsAppCampaign::STATUS_DRAFT,
                            WhatsAppCampaign::STATUS_SCHEDULED,
                            WhatsAppCampaign::STATUS_FAILED,
                        ],
                        true
                    )
                )
                ->action(function (): void {
                    $pendingRecipients = $this->record
                        ->recipients()
                        ->where(function ($query): void {
                            $query
                                ->where(
                                    'status',
                                    WhatsAppCampaignRecipient::STATUS_PENDING
                                )
                                ->orWhere(function ($retryQuery): void {
                                    $retryQuery
                                        ->where(
                                            'status',
                                            WhatsAppCampaignRecipient::STATUS_FAILED
                                        )
                                        ->where('retry_count', '<', 3);
                                });
                        })
                        ->count();

                    if ($pendingRecipients <= 0) {
                        Notification::make()
                            ->warning()
                            ->title('No pending recipients')
                            ->body(
                                'Campaign me message bhejne ke liye koi pending recipient nahi hai.'
                            )
                            ->send();

                        return;
                    }

                    $this->record->forceFill([
                        'status' =>
                            WhatsAppCampaign::STATUS_PROCESSING,
                        'scheduled_at' => null,
                        'started_at' => now(),
                        'completed_at' => null,
                        'cancelled_at' => null,
                        'failure_reason' => null,
                        'updated_by' => Auth::id(),
                    ])->saveQuietly();

                    SendWhatsAppCampaign::dispatch(
                        $this->record->getKey()
                    )->onQueue('whatsapp');

                    Notification::make()
                        ->success()
                        ->title('Campaign queued')
                        ->body(
                            "{$pendingRecipients} recipients ke messages queue me add ho gaye hain."
                        )
                        ->send();

                    $this->redirect(
                        WhatsAppCampaignResource::getUrl('index')
                    );
                }),

            Actions\Action::make('retryFailed')
                ->label('Retry Failed')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Retry Failed Messages')
                ->modalDescription(
                    'Maximum 3 attempts se kam wale failed messages dobara bheje jayenge.'
                )
                ->visible(
                    fn (): bool => $this->record
                        ->recipients()
                        ->where(
                            'status',
                            WhatsAppCampaignRecipient::STATUS_FAILED
                        )
                        ->where('retry_count', '<', 3)
                        ->exists()
                )
                ->action(function (): void {
                    $retryCount = $this->record
                        ->recipients()
                        ->where(
                            'status',
                            WhatsAppCampaignRecipient::STATUS_FAILED
                        )
                        ->where('retry_count', '<', 3)
                        ->update([
                            'status' =>
                                WhatsAppCampaignRecipient::STATUS_PENDING,
                            'failure_reason' => null,
                            'failed_at' => null,
                            'updated_at' => now(),
                        ]);

                    if ($retryCount <= 0) {
                        Notification::make()
                            ->warning()
                            ->title('Nothing to retry')
                            ->body(
                                'Koi retry-eligible failed recipient nahi mila.'
                            )
                            ->send();

                        return;
                    }

                    $this->record->forceFill([
                        'status' =>
                            WhatsAppCampaign::STATUS_PROCESSING,
                        'started_at' => now(),
                        'completed_at' => null,
                        'failure_reason' => null,
                        'updated_by' => Auth::id(),
                    ])->saveQuietly();

                    $this->record->refreshCounters();

                    SendWhatsAppCampaign::dispatch(
                        $this->record->getKey()
                    )->onQueue('whatsapp');

                    Notification::make()
                        ->success()
                        ->title('Failed messages queued')
                        ->body(
                            "{$retryCount} failed messages retry queue me add ho gaye hain."
                        )
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'pending_count',
                        'failed_count',
                    ]);
                }),

            Actions\Action::make('cancelCampaign')
                ->label('Cancel Campaign')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel WhatsApp Campaign')
                ->modalDescription(
                    'Campaign cancel hone ke baad queue worker naye messages process nahi karega.'
                )
                ->visible(
                    fn (): bool => $this->record->canBeCancelled()
                        && ! $this->record->isDraft()
                )
                ->action(function (): void {
                    $this->record->forceFill([
                        'status' =>
                            WhatsAppCampaign::STATUS_CANCELLED,
                        'cancelled_at' => now(),
                        'updated_by' => Auth::id(),
                    ])->saveQuietly();

                    Notification::make()
                        ->success()
                        ->title('Campaign cancelled')
                        ->body(
                            'WhatsApp campaign successfully cancel kar diya gaya hai.'
                        )
                        ->send();

                    $this->redirect(
                        WhatsAppCampaignResource::getUrl('index')
                    );
                }),

            Actions\DeleteAction::make()
                ->visible(
                    fn (): bool => $this->record->isDraft()
                ),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (
            $this->record->audience_type
            === WhatsAppCampaign::AUDIENCE_MANUAL
        ) {
            $numbers = data_get(
                $this->record->audience_data,
                'normalized_numbers',
                []
            );

            if (! is_array($numbers) || $numbers === []) {
                $numbers = $this->record
                    ->recipients()
                    ->orderBy('id')
                    ->pluck('mobile')
                    ->all();
            }

            data_set(
                $data,
                'audience_data.manual_numbers',
                implode(PHP_EOL, $numbers)
            );
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! $this->record->canBeEdited()) {
            throw ValidationException::withMessages([
                'campaign_name' =>
                    'Processing ya completed campaign edit nahi kiya ja sakta.',
            ]);
        }

        $data['updated_by'] = Auth::id();

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

    protected function afterSave(): void
    {
        if (
            $this->record->audience_type
            !== WhatsAppCampaign::AUDIENCE_MANUAL
        ) {
            return;
        }

        $numbers = data_get(
            $this->record->audience_data,
            'normalized_numbers',
            []
        );

        if (! is_array($numbers)) {
            $numbers = $this->prepareMobileNumbers($numbers);
        }

        DB::transaction(function () use ($numbers): void {
            $this->record
                ->recipients()
                ->whereNotIn('mobile', $numbers)
                ->whereIn('status', [
                    WhatsAppCampaignRecipient::STATUS_PENDING,
                    WhatsAppCampaignRecipient::STATUS_FAILED,
                ])
                ->delete();

            foreach ($numbers as $number) {
                $recipient = $this->record
                    ->recipients()
                    ->firstOrNew([
                        'mobile' => $number,
                    ]);

                $recipient->fill([
                    'customer_id' => null,
                    'template_name' =>
                        $this->record->template_name,
                    'variables' =>
                        $this->record->template_variables,
                ]);

                if (! $recipient->exists) {
                    $recipient->status =
                        WhatsAppCampaignRecipient::STATUS_PENDING;

                    $recipient->retry_count = 0;
                }

                $recipient->save();
            }

            $this->record->refreshCounters();
        });

        if (
            $this->record->status
            === WhatsAppCampaign::STATUS_SCHEDULED
            && $this->record->scheduled_at
        ) {
            SendWhatsAppCampaign::dispatch(
                $this->record->getKey()
            )
                ->delay($this->record->scheduled_at)
                ->onQueue('whatsapp');
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Campaign updated')
            ->body(
                $this->record->isScheduled()
                    ? 'Campaign update hokar schedule ho gaya hai.'
                    : 'WhatsApp campaign successfully update ho gaya hai.'
            );
    }

    protected function getRedirectUrl(): string
    {
        return WhatsAppCampaignResource::getUrl(
            'edit',
            ['record' => $this->record]
        );
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
            $number = $this->normalizeMobileNumber(
                (string) $rawNumber
            );

            if ($number !== null) {
                $numbers[] = $number;
            }
        }

        return array_values(array_unique($numbers));
    }

    private function normalizeMobileNumber(
        string $mobile
    ): ?string {
        $number = preg_replace(
            '/\D+/',
            '',
            $mobile
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