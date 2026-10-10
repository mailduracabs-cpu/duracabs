<?php

namespace App\Filament\Resources\SelfDriveBookingResource\Pages;

use App\Filament\Resources\SelfDriveBookingResource;
use App\Models\SelfDriveBooking;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSelfDriveBooking extends EditRecord
{
    protected static string $resource = SelfDriveBookingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $total = max(0, (float) ($data['total_amount'] ?? 0));
        $final = max(0, (float) ($data['final_amount'] ?? $total));
        $paid = max(0, (float) ($data['paid_amount'] ?? 0));

        $data['final_amount'] = $final;
        $data['remaining_amount'] = max(0, $final - $paid);
        $data['balance_due'] = $data['remaining_amount'];

        if (($data['payment_status'] ?? null) !== 'refunded') {
            $data['payment_status'] = $paid <= 0
                ? 'pending'
                : ($paid < $final ? 'partial' : 'paid');

            $data['payment_completed_at'] =
                $data['payment_status'] === 'paid'
                    ? ($this->record->payment_completed_at ?? now())
                    : null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->refreshTripAmounts();
        $this->record->save();

        Notification::make()
            ->title('Booking Updated')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('security_refund')->label('Security Refund')->icon('heroicon-o-arrow-uturn-left')
                ->visible(fn () => ! SelfDriveBookingResource::isTransporterPanel()
                    && auth()->user()?->canUseAdminLogin() && $this->record->booking_type === 'car'
                    && (float) $this->record->security_deposit > 0)
                ->form(SelfDriveBookingResource::securityRefundForm())
                ->action(function (array $data): void {
                    abort_unless(! SelfDriveBookingResource::isTransporterPanel() && auth()->user()?->canUseAdminLogin(), 403);
                    app(\App\Services\SelfDriveSecurityRefundService::class)->record($this->record->id, $data, (int) auth()->id());
                    Notification::make()->title('Actual security refund recorded')->success()->send();
                    $this->redirect(SelfDriveBookingResource::getUrl('edit', ['record' => $this->record]));
                }),
            Actions\Action::make('admin_return_bill')->label('Admin Return & Bill')->color('warning')
                ->icon('heroicon-o-document-check')
                ->visible(fn () => ! SelfDriveBookingResource::isTransporterPanel()
                    && auth()->user()?->canUseAdminLogin()
                    && ! in_array($this->record->status, ['cancelled', 'rejected', 'failed'], true)
                    && (! $this->record->final_bill_generated_at
                        || (! $this->record->return_otp_verified_at && ! $this->record->return_admin_confirmed_at)))
                ->form(SelfDriveBookingResource::returnOverrideForm())
                ->action(function (array $data): void {
                    abort_unless(! SelfDriveBookingResource::isTransporterPanel() && auth()->user()?->canUseAdminLogin(), 403);
                    try {
                        app(\App\Services\PartnerReturnCompletionService::class)->finish($this->record->id, $data, (int) auth()->id(), true);
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        Notification::make()->title('Return could not be saved')
                            ->body(collect($e->errors())->flatten()->implode(' '))->danger()->persistent()->send();
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'mountedActionsData.0.reviewed' => collect($e->errors())->flatten()->first(),
                        ]);
                    } catch (\Throwable $e) {
                        report($e);
                        $message = 'Server error while saving return. Nothing was committed. Check the Laravel log for this attempt.';
                        Notification::make()->title('Return could not be saved')->body($message)->danger()->persistent()->send();
                        throw \Illuminate\Validation\ValidationException::withMessages(['mountedActionsData.0.reviewed' => $message]);
                    }
                    $this->redirect(SelfDriveBookingResource::getUrl('edit', ['record' => $this->record]));
                }),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Self Drive Booking')
                ->modalDescription(
                    'Ye booking permanently delete ho jayegi. Is action ko undo nahi kiya ja sakta.'
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
