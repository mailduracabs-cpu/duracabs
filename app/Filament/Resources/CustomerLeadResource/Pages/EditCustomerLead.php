<?php

namespace App\Filament\Resources\CustomerLeadResource\Pages;

use App\Filament\Resources\CustomerLeadResource;
use App\Models\CustomerSearchActivity;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCustomerLead extends EditRecord
{
    protected static string $resource = CustomerLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View Lead'),

            Action::make('call')
                ->label('Call Customer')
                ->icon('heroicon-o-phone')
                ->color('success')
                ->url(
                    fn (): ?string =>
                        filled($this->record->mobile)
                            ? 'tel:' . $this->record->mobile
                            : null
                )
                ->visible(
                    fn (): bool => filled($this->record->mobile)
                ),

            Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(fn (): ?string => $this->getWhatsAppUrl())
                ->openUrlInNewTab()
                ->visible(
                    fn (): bool => filled($this->record->mobile)
                ),

            Action::make('mark_contacted')
                ->label('Mark Contacted')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Mark lead as contacted?')
                ->modalDescription(
                    'The lead status will be changed to Contacted.'
                )
                ->visible(
                    fn (): bool =>
                        $this->record->lead_status
                            !== CustomerSearchActivity::LEAD_CONTACTED
                        && ! $this->record->is_converted
                )
                ->action(function (): void {
                    $this->record->update([
                        'lead_status' =>
                            CustomerSearchActivity::LEAD_CONTACTED,
                        'last_activity_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Lead marked as contacted')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'lead_status',
                    ]);
                }),

            Action::make('schedule_follow_up')
                ->label('Schedule Follow-up')
                ->icon('heroicon-o-calendar-days')
                ->color('warning')
                ->form([
                    DateTimePicker::make('follow_up_at')
                        ->label('Follow-up Date & Time')
                        ->seconds(false)
                        ->native(false)
                        ->required(),

                    Textarea::make('lead_notes')
                        ->label('Follow-up Notes')
                        ->rows(4)
                        ->placeholder(
                            'Add call summary, customer requirement or reminder.'
                        ),
                ])
                ->fillForm(fn (): array => [
                    'follow_up_at' => $this->record->follow_up_at,
                    'lead_notes' => $this->record->lead_notes,
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'lead_status' =>
                            CustomerSearchActivity::LEAD_FOLLOW_UP,
                        'follow_up_at' => $data['follow_up_at'],
                        'lead_notes' =>
                            $data['lead_notes']
                            ?? $this->record->lead_notes,
                        'last_activity_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Follow-up scheduled')
                        ->body(
                            'The lead has been moved to Follow Up status.'
                        )
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'lead_status',
                        'follow_up_at',
                        'lead_notes',
                    ]);
                }),

            Action::make('close_lead')
                ->label('Close Lead')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Select::make('lead_status')
                        ->label('Closing Status')
                        ->options([
                            CustomerSearchActivity::LEAD_LOST => 'Lost',
                            CustomerSearchActivity::LEAD_NOT_INTERESTED =>
                                'Not Interested',
                        ])
                        ->native(false)
                        ->required(),

                    Textarea::make('lead_notes')
                        ->label('Closing Notes')
                        ->rows(4)
                        ->required()
                        ->placeholder(
                            'Mention why the customer did not proceed.'
                        ),
                ])
                ->visible(
                    fn (): bool => ! $this->record->is_converted
                )
                ->action(function (array $data): void {
                    $this->record->update([
                        'lead_status' => $data['lead_status'],
                        'lead_notes' => $data['lead_notes'],
                        'follow_up_at' => null,
                        'last_activity_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Lead closed')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'lead_status',
                        'lead_notes',
                        'follow_up_at',
                    ]);
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['last_activity_at'] = now();

        if (
            in_array(
                $data['lead_status'] ?? null,
                [
                    CustomerSearchActivity::LEAD_CONVERTED,
                    CustomerSearchActivity::LEAD_LOST,
                    CustomerSearchActivity::LEAD_NOT_INTERESTED,
                ],
                true
            )
        ) {
            $data['follow_up_at'] = null;
        }

        if (
            ($data['lead_status'] ?? null)
                === CustomerSearchActivity::LEAD_CONVERTED
        ) {
            $data['is_converted'] = true;
            $data['is_abandoned'] = false;
            $data['stage'] = CustomerSearchActivity::STAGE_CONVERTED;
            $data['search_status'] =
                CustomerSearchActivity::SEARCH_STATUS_CONVERTED;
            $data['converted_at'] =
                $this->record->converted_at ?? now();
            $data['priority'] =
                CustomerSearchActivity::PRIORITY_LOW;
        }

        return $data;
    }

    protected function handleRecordUpdate(
        Model $record,
        array $data
    ): Model {
        $record->update($data);

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Customer lead updated';
    }

    protected function afterSave(): void
    {
        $this->record->refresh();
    }

    private function getWhatsAppUrl(): ?string
    {
        if (blank($this->record->mobile)) {
            return null;
        }

        $mobile = preg_replace(
            '/\D+/',
            '',
            (string) $this->record->mobile
        );

        if (strlen($mobile) === 10) {
            $mobile = '91' . $mobile;
        }

        $message = implode("\n", [
            'Hello ' . $this->record->customer_display_name . ',',
            '',
            'This is Dura Cabs regarding your '
                . $this->record->service_label
                . ' enquiry.',
            'Route: ' . $this->record->route_summary,
            'Estimated Fare: ' . $this->record->formatted_amount,
            '',
            'Please reply here if you need any assistance.',
        ]);

        return 'https://wa.me/'
            . $mobile
            . '?text='
            . rawurlencode($message);
    }
}
